<?php

namespace App\Support;

use App\Models\WorkItem;

/**
 * Shared rules for voice hunting: which community domains may produce a voice,
 * and what obviously-marketing content gets rejected. Allowlist by design, so
 * competitor blogs and SEO listicles can never appear without maintaining an
 * impossible denylist.
 */
class VoiceFilter
{
    /** Community domains allowed to produce a voice for this work item. */
    public static function domainsFor(WorkItem $wi): array
    {
        $sources = array_values(array_filter(array_map(
            fn ($d) => strtolower(trim(preg_replace('#^https?://(www\.)?#i', '', (string) $d), " \t/")),
            $wi->voice_sources ?? []
        )));

        return $sources ?: (array) config('blog_automation.voices.default_sources', []);
    }

    /** True when a title/url reads like marketing or a listicle, not a user comment. */
    public static function isMarketing(string $text): bool
    {
        $text = ' ' . strtolower($text) . ' ';
        foreach ((array) config('blog_automation.voices.reject_patterns', []) as $p) {
            if (str_contains($text, strtolower($p))) {
                return true;
            }
        }
        return false;
    }

    /** True when the URL's host is on the allowlist. */
    public static function hostAllowed(?string $url, array $domains): bool
    {
        if (empty($url)) {
            return false;
        }
        $host = strtolower(parse_url($url, PHP_URL_HOST) ?: '');
        $host = preg_replace('/^www\./', '', $host);

        foreach ($domains as $d) {
            if ($host === $d || str_ends_with($host, '.' . $d)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Short, searchable complaint phrases derived from the manual's pain points.
     * Pain points are already written in user language, unlike the commercial
     * target keywords (which is what surfaced SEO listicles).
     */
    public static function phrasesFrom(WorkItem $wi, int $max = 3): array
    {
        $stop = ['the','a','an','and','or','of','to','for','you','your','they','their','that','this','with','without',
                 'is','are','be','it','on','in','do','does','not','so','but','as','at','by','from','have','has','can',
                 'most','usually','actually','even','still','just','own','into','than','then'];

        $phrases = [];
        foreach (array_slice($wi->pain_points ?? [], 0, $max) as $pain) {
            $words = preg_split('/[^a-z0-9\-]+/i', strtolower((string) $pain), -1, PREG_SPLIT_NO_EMPTY);
            $kept = array_values(array_filter($words, fn ($w) => !in_array($w, $stop, true) && strlen($w) > 2));
            $phrase = trim(implode(' ', array_slice($kept, 0, 7)));
            if ($phrase !== '') {
                $phrases[] = $phrase;
            }
        }

        if (empty($phrases)) {
            $phrases[] = trim((string) ($wi->target_keywords[0] ?? $wi->name));
        }

        return array_values(array_unique($phrases));
    }
}
