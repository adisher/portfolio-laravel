<?php

namespace App\Services;

/**
 * Rules-based quality gate for an AI-rewritten article.
 *
 * The existing ArticleScoringService judges the SOURCE item (its title and RSS
 * description) before anything is written, so nothing has ever judged the text
 * we actually publish. This does, with plain rules: free, deterministic and
 * testable, unlike a second AI call.
 *
 * Thresholds come from measurements over the 452 published posts (2026-09-08):
 * median title similarity to the source 31%, median 5-gram body overlap 1.4%.
 * The failures are far out in the tail: 45 posts reuse the source title
 * verbatim (the parser bug), 33 exceed 50% body overlap.
 *
 * evaluate() scores a STORED post. evaluateRawResponse() adds the checks that
 * only make sense on the raw model output, before parsing.
 */
class RewriteQualityService
{
    public const PASS_MARK = 70;
    private const SOFT_FLAG_PENALTY = 10;

    /**
     * Refusals and scaffolding that mean the generation went wrong.
     *
     * NOTE: a fenced code block is NOT one of them. The prompt asks for a code
     * snippet where the topic warrants it, so ``` at the start of a line is
     * expected output. An earlier version flagged it and condemned 173 of 454
     * published posts for doing exactly what they were told. Only a fence
     * wrapping the WHOLE response (caught below, not here) is scaffolding.
     */
    private const PLACEHOLDER_PATTERNS = [
        '/\bas an ai\b/i',
        '/\bi\'m sorry\b/i',
        '/\bi cannot (?:help|assist|comply)\b/i',
        '/\bhere(?:\'s| is) the (?:blog post|article|rewritten)\b/i',
        '/```(?:markdown|md)\b/i',
    ];

    /**
     * Evaluate a published/stored article.
     *
     * $sourceText is the original article's text (RSS description plus any
     * stored content_data), used for the overlap check. Pass null to skip it.
     *
     * @return array{score:int,passed:bool,hard_failures:array<string>,soft_flags:array<string>,metrics:array<string,mixed>}
     */
    public function evaluate(
        ?string $title,
        ?string $content,
        ?string $sourceTitle,
        ?string $sourceText = null,
        ?string $excerpt = null,
        ?string $originalUrl = null
    ): array {
        $title = trim((string) $title);
        $content = (string) $content;
        $plain = trim(strip_tags($content));

        $hard = [];
        $soft = [];

        $wordCount = str_word_count($plain);
        $titleSimilarity = $this->titleSimilarity($title, (string) $sourceTitle);
        $overlap = $sourceText === null ? null : $this->sourceOverlap($sourceText, $plain);
        $headings = preg_match_all('/^##\s+\S/m', $content);
        $nonAscii = $this->nonAsciiRatio($title . ' ' . $plain);

        // ---- Hard failures: never publish ----
        if ($titleSimilarity >= 70.0) {
            $hard[] = 'title_matches_source';
        }
        if ($overlap !== null && $overlap >= 50.0) {
            $hard[] = 'body_copies_source';
        }
        if ($wordCount < 500) {
            $hard[] = 'too_short';
        }
        if (! $this->hasAttribution($content, $originalUrl)) {
            $hard[] = 'missing_attribution';
        }
        if ($this->hasPlaceholderText($content)) {
            $hard[] = 'placeholder_or_refusal';
        }
        if ($nonAscii > 0.2) {
            $hard[] = 'not_english';
        }

        // ---- Soft flags: 10 points each ----
        if ($headings < 2) {
            $soft[] = 'few_headings';
        }
        if (! $this->hasFirstPerson($plain)) {
            $soft[] = 'no_first_person';
        }
        if ($overlap !== null && $overlap >= 25.0 && $overlap < 50.0) {
            $soft[] = 'elevated_source_overlap';
        }
        if (($wordCount >= 500 && $wordCount < 600) || $wordCount > 1200) {
            $soft[] = 'word_count_off_target';
        }
        if (trim((string) $excerpt) === '') {
            $soft[] = 'no_excerpt';
        }
        $titleLength = mb_strlen($title);
        if ($titleLength < 40 || $titleLength > 100) {
            $soft[] = 'headline_length';
        }

        $score = max(0, 100 - (count($soft) * self::SOFT_FLAG_PENALTY));

        return [
            'score' => $score,
            'passed' => empty($hard) && $score >= self::PASS_MARK,
            'hard_failures' => $hard,
            'soft_flags' => $soft,
            'metrics' => [
                'word_count' => $wordCount,
                'title_similarity' => round($titleSimilarity, 1),
                'source_overlap' => $overlap === null ? null : round($overlap, 1),
                'headings' => $headings,
                'non_ascii_ratio' => round($nonAscii, 3),
            ],
        ];
    }

    /**
     * Generation-time check on the raw model output, before parsing.
     *
     * The parser takes the title only from a leading "# " line and otherwise
     * silently falls back to the SOURCE article's title, which is how 45 posts
     * were published under someone else's headline. That is only detectable
     * here: parseTransformationResponse() strips the "# " line, so a stored
     * post never has one either way.
     */
    public function evaluateRawResponse(
        string $rawResponse,
        ?string $sourceTitle,
        ?string $sourceText = null,
        ?string $originalUrl = null
    ): array {
        $hasH1 = (bool) preg_match('/^#\s+\S/m', $rawResponse);

        $title = '';
        $body = $rawResponse;
        if ($hasH1 && preg_match('/^#\s+(.+)/m', $rawResponse, $m)) {
            $title = trim($m[1]);
            $body = trim(preg_replace('/^#\s+.+\n+/m', '', $rawResponse, 1));
        }

        $result = $this->evaluate($title, $body, $sourceTitle, $sourceText, 'n/a', $originalUrl);

        if (! $hasH1) {
            // Prepend so the real cause reads first: without the H1 the title
            // is not the model's at all, which makes title_matches_source a
            // symptom rather than the fault.
            array_unshift($result['hard_failures'], 'missing_h1_headline');
            $result['passed'] = false;
        }

        return $result;
    }

    /** Percentage similarity between the two headlines, case-insensitive. */
    public function titleSimilarity(string $title, string $sourceTitle): float
    {
        $title = trim(mb_strtolower($title));
        $sourceTitle = trim(mb_strtolower($sourceTitle));

        if ($title === '' || $sourceTitle === '') {
            return 0.0;
        }
        if ($title === $sourceTitle) {
            return 100.0;
        }

        similar_text($title, $sourceTitle, $percent);

        return (float) $percent;
    }

    /**
     * What share of the source's 5-word sequences survive into the article.
     * Returns null when the source is too short to measure meaningfully.
     */
    public function sourceOverlap(string $sourceText, string $articleText, int $n = 5): ?float
    {
        $source = $this->shingles($sourceText, $n);
        if (count($source) < 10) {
            return null;
        }

        $article = $this->shingles($articleText, $n);
        if (empty($article)) {
            return 0.0;
        }

        return (count(array_intersect_key($source, $article)) / count($source)) * 100;
    }

    /** @return array<string,true> */
    private function shingles(string $text, int $n): array
    {
        $words = preg_split(
            '/\s+/',
            mb_strtolower(preg_replace('/[^\p{L}\p{N} ]+/u', ' ', strip_tags($text)) ?? ''),
            -1,
            PREG_SPLIT_NO_EMPTY
        ) ?: [];

        $out = [];
        for ($i = 0; $i + $n <= count($words); $i++) {
            $out[implode(' ', array_slice($words, $i, $n))] = true;
        }

        return $out;
    }

    private function hasAttribution(string $content, ?string $originalUrl): bool
    {
        if (stripos($content, 'Read the original article') !== false) {
            return true;
        }
        if (preg_match('/^\s*\*?\s*Source:/mi', $content)) {
            return true;
        }

        return ! empty($originalUrl) && str_contains($content, $originalUrl);
    }

    private function hasPlaceholderText(string $content): bool
    {
        foreach (self::PLACEHOLDER_PATTERNS as $pattern) {
            if (preg_match($pattern, $content)) {
                return true;
            }
        }

        // A fence on the very first line means the model wrapped its entire
        // answer in a code block. Fences anywhere else are real snippets.
        return str_starts_with(ltrim($content), '```');
    }

    private function hasFirstPerson(string $plain): bool
    {
        // "I" only as a capital, so "i" inside a lowercased word never counts;
        // the possessives are case-insensitive.
        return (bool) preg_match('/\bI\b|\bI\'|\b(?:my|mine|we|our|we\'ve|i\'ve|i\'m)\b/i', $plain);
    }

    private function nonAsciiRatio(string $text): float
    {
        $length = strlen($text);
        if ($length === 0) {
            return 0.0;
        }

        $ascii = strlen(preg_replace('/[^\x00-\x7F]/', '', $text) ?? '');

        return ($length - $ascii) / $length;
    }
}
