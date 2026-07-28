<?php

namespace App\Support;

/**
 * Classifies a visit into a traffic source from its referrer + UTM.
 *
 * Replaces the old getTrafficSources() heuristic, which recognised only
 * google/bing/yahoo + four social networks, mislabelled no-referrer as
 * "organic" (it is Direct), and had zero AI awareness.
 *
 * Returns [source, detail] where source is one of the SOURCES constants and
 * detail names the specific origin (e.g. "ChatGPT", "Google", "reddit.com").
 *
 * NOTE ON AI ATTRIBUTION: many AI apps strip the Referer header on click
 * (in-app browsers, privacy settings). Those arrive with a null referrer and
 * are classified Direct — indistinguishable from a typed URL. So AI-assistant
 * counts from this classifier are a FLOOR, never a complete total.
 */
class TrafficSource
{
    public const AI = 'ai_assistant';
    public const SEARCH = 'search';
    public const SOCIAL = 'social';
    public const REFERRAL = 'referral';
    public const DIRECT = 'direct';

    /**
     * AI assistants. Order matters only for readability; matching is by
     * host substring. `gemini.google.com` is checked before generic Google
     * search so it is not miscategorised as Search.
     */
    private const AI_HOSTS = [
        'chatgpt.com'            => 'ChatGPT',
        'chat.openai.com'        => 'ChatGPT',
        'openai.com'             => 'ChatGPT',
        'claude.ai'              => 'Claude',
        'perplexity.ai'          => 'Perplexity',
        'gemini.google.com'      => 'Gemini',
        'bard.google.com'        => 'Gemini',
        'copilot.microsoft.com'  => 'Copilot',
        'you.com'                => 'You.com',
        'poe.com'                => 'Poe',
        'phind.com'              => 'Phind',
    ];

    private const SEARCH_HOSTS = [
        'google.'      => 'Google',
        'bing.com'     => 'Bing',
        'duckduckgo.'  => 'DuckDuckGo',
        'yahoo.'       => 'Yahoo',
        'ecosia.org'   => 'Ecosia',
        'search.brave' => 'Brave',
        'yandex.'      => 'Yandex',
        'baidu.com'    => 'Baidu',
        'startpage.'   => 'Startpage',
    ];

    private const SOCIAL_HOSTS = [
        'x.com'         => 'X',
        'twitter.com'   => 'X',
        't.co'          => 'X',
        'linkedin.com'  => 'LinkedIn',
        'lnkd.in'       => 'LinkedIn',
        'facebook.com'  => 'Facebook',
        'instagram.com' => 'Instagram',
        'reddit.com'    => 'Reddit',
        'news.ycombinator.com' => 'Hacker News',
        'mastodon'      => 'Mastodon',
        'youtube.com'   => 'YouTube',
        't.me'          => 'Telegram',
        'threads.net'   => 'Threads',
    ];

    /**
     * @return array{0: string, 1: ?string} [source, detail]
     */
    public static function classify(?string $referrer, ?string $utmSource = null): array
    {
        $ref = strtolower(trim((string) $referrer));
        $utm = strtolower(trim((string) $utmSource));

        // Explicit UTM tagging wins when present — it is a deliberate signal.
        if ($utm !== '') {
            if (str_contains($utm, 'chatgpt') || str_contains($utm, 'openai')) return [self::AI, 'ChatGPT'];
            if (str_contains($utm, 'claude'))     return [self::AI, 'Claude'];
            if (str_contains($utm, 'perplexity')) return [self::AI, 'Perplexity'];
            if (str_contains($utm, 'gemini') || str_contains($utm, 'bard')) return [self::AI, 'Gemini'];
            if (str_contains($utm, 'social'))  return [self::SOCIAL, ucfirst($utm)];
            if (str_contains($utm, 'search'))  return [self::SEARCH, ucfirst($utm)];
            if (str_contains($utm, 'newsletter') || str_contains($utm, 'email')) return [self::REFERRAL, 'Email/Newsletter'];
        }

        // No referrer and no useful UTM = Direct (typed URL, bookmark, or a
        // referrer-stripped click we cannot attribute).
        if ($ref === '') {
            return [self::DIRECT, null];
        }

        $host = self::host($ref);

        foreach (self::AI_HOSTS as $needle => $name) {
            if (str_contains($host, $needle)) return [self::AI, $name];
        }
        foreach (self::SEARCH_HOSTS as $needle => $name) {
            if (str_contains($host, $needle)) return [self::SEARCH, $name];
        }
        foreach (self::SOCIAL_HOSTS as $needle => $name) {
            if (str_contains($host, $needle)) return [self::SOCIAL, $name];
        }

        // Self-referral (internal navigation) is not an acquisition source;
        // treat it as Direct so it doesn't inflate "referral".
        $appHost = self::host(strtolower((string) config('app.url')));
        if ($appHost !== '' && str_contains($host, $appHost)) {
            return [self::DIRECT, null];
        }

        // Any other site linking to us.
        return [self::REFERRAL, $host ?: null];
    }

    /** Bare host from a URL or host string, without scheme or leading www. */
    private static function host(string $url): string
    {
        $host = str_contains($url, '://') ? (parse_url($url, PHP_URL_HOST) ?: '') : $url;
        $host = preg_replace('#^www\.#', '', strtolower($host));
        return trim($host, '/');
    }

    /** Human-readable label for a source key (for report headers). */
    public static function label(string $source): string
    {
        return match ($source) {
            self::AI       => 'AI Assistant',
            self::SEARCH   => 'Search',
            self::SOCIAL   => 'Social',
            self::REFERRAL => 'Referral',
            self::DIRECT   => 'Direct',
            default        => ucfirst($source),
        };
    }
}
