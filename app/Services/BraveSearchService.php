<?php

namespace App\Services;

use App\Models\ToolUsageLog;
use App\Models\WorkItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Finds voice candidates using the Brave Search API. Brave returns real pages
 * (title, url, snippet) for queries we build from the work item; each result
 * becomes a candidate the user opens, verifies, and screenshots. No LLM, so no
 * fabrication risk.
 *
 * NOTE: Brave is PAID. Its free tier ended in Feb 2026; it is now metered at
 * roughly $5 per 1,000 queries with prepaid credits. This engine is optional and
 * stays disabled unless BRAVE_SEARCH_API_KEY is set. Claude is the default engine.
 */
class BraveSearchService
{
    protected string $apiUrl = 'https://api.search.brave.com/res/v1/web/search';

    public function isConfigured(): bool
    {
        return !empty(config('services.brave.key'));
    }

    /**
     * @return array{candidates: array, queries: array, raw: array, cost: float, note: ?string, ok: bool}
     */
    public function findVoices(WorkItem $workItem, int $maxCandidates = 8): array
    {
        $result = ['candidates' => [], 'queries' => [], 'raw' => [], 'cost' => 0.0, 'note' => null, 'ok' => false];

        if (!$this->isConfigured()) {
            $result['note'] = 'Brave Search API key is not configured (set BRAVE_SEARCH_API_KEY).';
            return $result;
        }

        $key = config('services.brave.key');
        $domains = \App\Support\VoiceFilter::domainsFor($workItem);
        $queries = $this->buildQueries($workItem, $domains);
        $result['queries'] = $queries;

        $seenUrls = [];
        $candidates = [];
        $rawAll = [];

        foreach ($queries as $i => $q) {
            if ($i > 0) {
                usleep(1_100_000); // Brave free tier: ~1 request/second
            }

            try {
                $resp = Http::withHeaders([
                    'Accept'                => 'application/json',
                    'X-Subscription-Token'  => $key,
                ])->timeout(20)->get($this->apiUrl, [
                    'q'          => $q,
                    'count'      => 5,
                    'safesearch' => 'moderate',
                ]);
            } catch (\Throwable $e) {
                Log::warning("Brave search failed for '{$q}': " . $e->getMessage());
                $rawAll[$q] = ['error' => $e->getMessage()];
                continue;
            }

            if (!$resp->successful()) {
                Log::warning("Brave search HTTP {$resp->status()} for '{$q}'");
                $rawAll[$q] = ['status' => $resp->status(), 'body' => $resp->json()];
                continue;
            }

            $rows = $resp->json('web.results') ?? [];
            $rawAll[$q] = array_map(fn($r) => ['title' => $r['title'] ?? '', 'url' => $r['url'] ?? ''], $rows);

            foreach ($rows as $r) {
                $url = $r['url'] ?? '';
                if ($url === '' || isset($seenUrls[$url])) {
                    continue;
                }
                $seenUrls[$url] = true;

                $title = $r['title'] ?? '';
                $snippet = trim(strip_tags($r['description'] ?? ''));

                // Allowlist is the gate: only community platforms survive. We do NOT
                // reject on "best/alternatives" wording here, because on Reddit/HN a
                // thread titled "best linktree alternative?" is a genuine user
                // discussion full of pain points, not a vendor listicle.
                if (!\App\Support\VoiceFilter::hostAllowed($url, $domains)) {
                    continue;
                }
                $candidates[] = [
                    'quote'       => $snippet !== '' ? $snippet : ($r['title'] ?? $url),
                    'attribution' => $this->hostLabel($url),
                    'source_url'  => $url,
                    'note'        => 'Brave search result (snippet). Open the source and copy the exact quote before approving.',
                    'confidence'  => 'unverified',
                ];

                if (count($candidates) >= $maxCandidates) {
                    break 2;
                }
            }
        }

        ToolUsageLog::record('brave', 'search', count($queries), 'queries', true, null, ['work_item_id' => $workItem->id]);

        $result['candidates'] = $candidates;
        $result['raw'] = $rawAll;
        $result['ok'] = true;
        if (empty($candidates)) {
            $result['note'] = 'Brave ran but returned no usable results. Try broadening the target keywords on this manual.';
        }

        return $result;
    }

    /**
     * Build site:-restricted queries from the manual's PAIN POINTS (user language),
     * not its commercial keywords — commercial keywords are exactly what SEO
     * listicles rank for, which is why they used to dominate the results.
     */
    protected function buildQueries(WorkItem $wi, array $domains): array
    {
        $phrases = \App\Support\VoiceFilter::phrasesFrom($wi, 3);
        $domains = array_slice($domains, 0, 3) ?: ['reddit.com'];

        $queries = [];
        foreach ($domains as $domain) {
            foreach (array_slice($phrases, 0, 2) as $phrase) {
                $queries[] = 'site:' . $domain . ' ' . $phrase;
            }
        }

        return array_slice(array_values(array_unique($queries)), 0, 5);
    }

    protected function hostLabel(string $url): string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: $url;
        $host = preg_replace('/^www\./', '', $host);
        if (str_contains($host, 'reddit.com') && preg_match('#reddit\.com/(r/[^/]+)#', $url, $m)) {
            return $m[1];
        }
        return $host;
    }
}
