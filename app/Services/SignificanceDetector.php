<?php

namespace App\Services;

use App\Models\CollectedArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignificanceDetector
{
    /**
     * Cheap check: does the title+description contain any trigger language
     * or notable entity at all? Used to decide whether the (still free, but
     * slower) full-article fetch is worth doing. Pure substring/regex, no
     * network, no AI.
     */
    public function quickScan(string $snippet): bool
    {
        $snippet = strtolower($snippet);

        foreach (config('blog_automation.significance.notable_entities', []) as $entity) {
            if (str_contains($snippet, strtolower($entity))) {
                return true;
            }
        }

        foreach (config('blog_automation.significance.trigger_patterns', []) as $pattern) {
            if (preg_match($pattern, $snippet)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Full check for one article. Escalates to fetching the full article
     * body (still no AI cost) only if the cheap snippet scan already found
     * something, then looks for a trigger term and a notable entity
     * co-occurring in the SAME sentence — the "context" check, done with
     * plain string/regex logic rather than an LLM call.
     */
    public function check(CollectedArticle $article): array
    {
        if (!config('blog_automation.significance.enabled', true)) {
            return ['significant' => false];
        }

        $snippet = $article->title . ' ' . ($article->description ?? '');

        if (!$this->quickScan($snippet)) {
            return ['significant' => false];
        }

        // Snippet alone already qualifies (some feeds give a full teaser)
        $hit = $this->findCoOccurrence($snippet);
        if ($hit) {
            return $hit;
        }

        if (!config('blog_automation.significance.fetch_full_article', true)) {
            return ['significant' => false];
        }

        $fullText = $this->fetchFullText($article->url);
        if (!$fullText) {
            return ['significant' => false];
        }

        $hit = $this->findCoOccurrence($fullText);

        return $hit ?? ['significant' => false];
    }

    /**
     * Split into sentences, return the first sentence where a trigger term
     * and a notable entity both appear.
     */
    protected function findCoOccurrence(string $text): ?array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];
        $entities = config('blog_automation.significance.notable_entities', []);
        $patterns = config('blog_automation.significance.trigger_patterns', []);

        foreach ($sentences as $sentence) {
            $lower = strtolower($sentence);

            $matchedEntity = null;
            foreach ($entities as $entity) {
                if (str_contains($lower, strtolower($entity))) {
                    $matchedEntity = $entity;
                    break;
                }
            }
            if (!$matchedEntity) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $sentence, $m)) {
                    return [
                        'significant' => true,
                        'matched_entity' => $matchedEntity,
                        'matched_trigger' => $m[0],
                        'matched_sentence' => trim($sentence),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Fetch the article's own page and strip it to plain text. Used only
     * transiently for this check — never persisted — to avoid storing a
     * copy of someone else's full copyrighted article in the database.
     */
    protected function fetchFullText(?string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        try {
            $timeout = (int) config('blog_automation.significance.fetch_timeout_seconds', 6);
            $response = Http::timeout($timeout)->get($url);

            if (!$response->successful()) {
                return null;
            }

            $text = strip_tags($response->body());
            $text = preg_replace('/\s+/', ' ', $text);

            return trim($text);
        } catch (\Exception $e) {
            Log::warning("SignificanceDetector: full-article fetch failed for {$url}: " . $e->getMessage());
            return null;
        }
    }
}
