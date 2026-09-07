<?php

namespace App\Services;

use App\Models\CollectedArticle;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SignificanceDetector
{
    /**
     * Is this source allowed to trigger the breaking-news fast path?
     *
     * Community/aggregator blogs are excluded: their posts are tutorials and
     * personal essays, and their page boilerplate ("Buy Me a Coffee" links,
     * footers, tag lists) is what produced 10/10 false positives on the first
     * production run. An empty allowlist means "no restriction".
     */
    public function sourceIsEligible(?CollectedArticle $article): bool
    {
        $allowlist = config('blog_automation.significance.source_allowlist', []);

        if (empty($allowlist)) {
            return true;
        }

        $name = $article?->rssSource?->name;
        if (empty($name)) {
            return false; // unknown provenance never qualifies as breaking news
        }

        foreach ($allowlist as $allowed) {
            if (strcasecmp($name, $allowed) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Cheap check: does the text contain any trigger language or notable
     * entity at all? Used to decide whether the (still free, but slower)
     * full-article fetch is worth doing. No network, no AI.
     */
    public function quickScan(string $snippet): bool
    {
        foreach (config('blog_automation.significance.notable_entities', []) as $entity) {
            if ($this->matchesEntity($snippet, $entity)) {
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
     * Full check for one article: a trigger term and a notable entity must
     * co-occur in the SAME prose sentence. Plain string/regex logic — no
     * LLM call, no API cost.
     */
    public function check(CollectedArticle $article): array
    {
        if (!config('blog_automation.significance.enabled', true)) {
            return ['significant' => false];
        }

        if (!$this->sourceIsEligible($article)) {
            return ['significant' => false];
        }

        $snippet = $article->title . '. ' . ($article->description ?? '');

        if (!$this->quickScan($snippet)) {
            return ['significant' => false];
        }

        // The RSS title+description is clean, editor-written prose — by far
        // the most reliable place to detect this, and enough on its own for
        // real headlines ("SpaceX officially closes its Cursor acquisition").
        $hit = $this->findCoOccurrence($this->normalise($snippet));
        if ($hit) {
            return $hit;
        }

        if (!config('blog_automation.significance.fetch_full_article', false)) {
            return ['significant' => false];
        }

        $fullText = $this->fetchFullText($article->url);
        if (!$fullText) {
            return ['significant' => false];
        }

        return $this->findCoOccurrence($fullText) ?? ['significant' => false];
    }

    /**
     * Whole-word entity match. Never a bare substring: str_contains matched
     * "amazon" inside "amazonaws.com" and "meta" inside markup, which is how
     * an S3 image URL in a footer became breaking news.
     */
    protected function matchesEntity(string $text, string $entity): bool
    {
        return (bool) preg_match('/\b' . preg_quote($entity, '/') . '\b/i', $text);
    }

    /**
     * Split into sentences and return the first PROSE sentence where a
     * trigger term and a notable entity both appear.
     */
    protected function findCoOccurrence(string $text): ?array
    {
        $sentences = preg_split('/(?<=[.!?])\s+/', $text) ?: [$text];
        $entities = config('blog_automation.significance.notable_entities', []);
        $patterns = config('blog_automation.significance.trigger_patterns', []);

        foreach ($sentences as $sentence) {
            if (!$this->isProseSentence($sentence)) {
                continue;
            }

            $matchedEntity = null;
            foreach ($entities as $entity) {
                if ($this->matchesEntity($sentence, $entity)) {
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
     * Is this a real prose sentence, rather than CSS, markup residue, a nav
     * menu or a footer run? Without this the "same sentence" rule silently
     * degrades into "same page" on de-tagged HTML, which is what let a CSS
     * block and a comment-section footer qualify as breaking news.
     */
    protected function isProseSentence(string $sentence): bool
    {
        $length = mb_strlen(trim($sentence));
        $min = (int) config('blog_automation.significance.sentence_min_chars', 20);
        $max = (int) config('blog_automation.significance.sentence_max_chars', 400);

        if ($length < $min || $length > $max) {
            return false;
        }

        // Markup / stylesheet / script / URL residue: never prose.
        if (preg_match('/[{}<>]|!important|@media|https?:\/\//i', $sentence)) {
            return false;
        }

        return true;
    }

    /**
     * Collapse whitespace and decode entities so sentence splitting works on
     * real text rather than raw markup.
     */
    protected function normalise(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Fetch the article's page and reduce it to plain text. Used only
     * transiently for this check — never persisted — so no copy of someone
     * else's full article is stored.
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

            // strip_tags removes the TAGS but keeps whatever sits between
            // them, so <style>/<script> bodies survive as raw CSS/JS text
            // unless they are removed wholesale first.
            $html = preg_replace('#<(script|style|noscript)\b[^>]*>.*?</\1>#is', ' ', $response->body());

            return $this->normalise($html);
        } catch (\Exception $e) {
            Log::warning("SignificanceDetector: full-article fetch failed for {$url}: " . $e->getMessage());
            return null;
        }
    }
}
