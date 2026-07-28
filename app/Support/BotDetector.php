<?php

namespace App\Support;

use Jenssegers\Agent\Agent;

/**
 * Bot classification, layered over jenssegers/agent's isRobot().
 *
 * Two failure modes this fixes, both seen in real prod data (2026-07):
 *   1. isRobot()'s crawler list is stale — it misses Claude-Web and
 *      Google-Extended (they slipped through as "human"). We add an explicit,
 *      maintained AI/SEO crawler token list on top.
 *   2. isRobot() is user-agent-string-only, so it structurally cannot catch a
 *      scraper that spoofs a real browser UA (a rotating-proxy scraper put
 *      3,043 fake "human" views on one article). That is a BEHAVIOURAL signal,
 *      only visible in aggregate — see behaviouralScraperUaIds(), used by the
 *      reclassify backfill, not by per-request detection.
 *
 * reason() values (stored in visitors.bot_reason; null = human):
 *   'ai_crawler'  — a known AI assistant/training crawler (ClaudeBot, GPTBot…)
 *   'seo_crawler' — a known SEO/index crawler (AhrefsBot, SemrushBot…)
 *   'search_crawler' — a search-engine indexer (Googlebot, bingbot…)
 *   'ua_robot'    — jenssegers flagged it, none of the above matched
 *   'scraper'     — behavioural: spoofed browser UA across many IPs
 */
class BotDetector
{
    /**
     * Known crawler UA tokens → [reason, name]. Matched case-insensitively as
     * substrings of the user-agent. This is the layer that catches what
     * isRobot() misses (Claude-Web, Google-Extended) and lets us name the
     * crawler for the "AI crawler activity" report.
     */
    private const CRAWLERS = [
        // AI assistants / training crawlers
        'gptbot'          => ['ai_crawler', 'GPTBot (OpenAI)'],
        'chatgpt-user'    => ['ai_crawler', 'ChatGPT-User (OpenAI)'],
        'oai-searchbot'   => ['ai_crawler', 'OAI-SearchBot (OpenAI)'],
        'claudebot'       => ['ai_crawler', 'ClaudeBot (Anthropic)'],
        'claude-web'      => ['ai_crawler', 'Claude-Web (Anthropic)'],
        'claude-user'     => ['ai_crawler', 'Claude-User (Anthropic)'],
        'anthropic-ai'    => ['ai_crawler', 'Anthropic-AI'],
        'perplexitybot'   => ['ai_crawler', 'PerplexityBot'],
        'perplexity-user' => ['ai_crawler', 'Perplexity-User'],
        'google-extended' => ['ai_crawler', 'Google-Extended'],
        'gemini'          => ['ai_crawler', 'Google Gemini'],
        'bytespider'      => ['ai_crawler', 'Bytespider (ByteDance)'],
        'ccbot'           => ['ai_crawler', 'CCBot (Common Crawl)'],
        'cohere-ai'       => ['ai_crawler', 'Cohere'],
        'meta-externalagent' => ['ai_crawler', 'Meta-ExternalAgent'],
        'applebot-extended'  => ['ai_crawler', 'Applebot-Extended'],
        'amazonbot'       => ['ai_crawler', 'Amazonbot'],
        'youbot'          => ['ai_crawler', 'YouBot'],
        'diffbot'         => ['ai_crawler', 'Diffbot'],
        'timpibot'        => ['ai_crawler', 'Timpibot'],

        // Search engine indexers
        'googlebot'       => ['search_crawler', 'Googlebot'],
        'googleother'     => ['search_crawler', 'GoogleOther'],
        'storebot-google' => ['search_crawler', 'Storebot-Google'],
        'bingbot'         => ['search_crawler', 'Bingbot'],
        'yandexbot'       => ['search_crawler', 'YandexBot'],
        'duckduckbot'     => ['search_crawler', 'DuckDuckBot'],
        'baiduspider'     => ['search_crawler', 'Baiduspider'],
        'applebot'        => ['search_crawler', 'Applebot'],

        // SEO / analytics / misc crawlers
        'ahrefsbot'       => ['seo_crawler', 'AhrefsBot'],
        'semrushbot'      => ['seo_crawler', 'SemrushBot'],
        'mj12bot'         => ['seo_crawler', 'MJ12bot'],
        'dotbot'          => ['seo_crawler', 'DotBot'],
        'dataforseo'      => ['seo_crawler', 'DataForSeoBot'],
        'petalbot'        => ['seo_crawler', 'PetalBot'],
        'facebookexternalhit' => ['seo_crawler', 'Facebook External Hit'],
        'bytedance'       => ['seo_crawler', 'ByteDance'],
    ];

    /**
     * Classify a user-agent. Returns [is_bot, reason, crawler_name].
     * crawler_name is null unless a known crawler matched.
     *
     * @return array{0: bool, 1: ?string, 2: ?string}
     */
    public static function fromUserAgent(?string $userAgent): array
    {
        $ua = strtolower(trim((string) $userAgent));

        if ($ua === '') {
            // No UA at all is itself suspicious, but many privacy tools strip
            // it; leave it to behavioural detection rather than hard-flagging.
            return [false, null, null];
        }

        foreach (self::CRAWLERS as $token => [$reason, $name]) {
            if (str_contains($ua, $token)) {
                return [true, $reason, $name];
            }
        }

        // Fall back to jenssegers' broader (but UA-only) robot list.
        $agent = new Agent();
        $agent->setUserAgent((string) $userAgent);
        if ($agent->isRobot()) {
            return [true, 'ua_robot', null];
        }

        return [false, null, null];
    }

    /** Convenience: is this UA a bot? */
    public static function isBot(?string $userAgent): bool
    {
        return self::fromUserAgent($userAgent)[0];
    }

    /** The crawler display name for a UA, or null if not a known crawler. */
    public static function crawlerName(?string $userAgent): ?string
    {
        return self::fromUserAgent($userAgent)[2];
    }
}
