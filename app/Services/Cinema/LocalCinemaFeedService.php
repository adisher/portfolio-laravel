<?php

namespace App\Services\Cinema;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Reads the Local Cinema JSON Feed (https://www.jsonfeed.org/version/1.1/)
 * and turns it into a clean list of titles for the /cinema page.
 *
 * The feed is fetched server-side and cached, so visitors never wait on the
 * remote site. If a refresh fails, the last good copy keeps being served
 * instead of an empty page.
 */
class LocalCinemaFeedService
{
    /** Allowed values for the feed's `type` query param, keyed by our tab name. */
    public const TYPES = [
        'movies' => 'Movies',
        'tv' => 'TV Shows',
    ];

    public function items(string $type = 'movies'): array
    {
        if (! array_key_exists($type, self::TYPES)) {
            $type = 'movies';
        }

        $freshKey = "local_cinema:{$type}";
        $staleKey = "local_cinema:{$type}:last_good";

        $cached = Cache::get($freshKey);
        if (is_array($cached)) {
            return $cached;
        }

        $items = $this->fetch($type);

        if ($items === null) {
            // Remote is down or returned junk: fall back to the last good copy
            // and retry in a few minutes rather than on every request.
            $items = Cache::get($staleKey, []);
            Cache::put($freshKey, $items, 300);

            return $items;
        }

        Cache::put($freshKey, $items, config('services.local_cinema.cache_ttl', 1800));
        Cache::forever($staleKey, $items);

        return $items;
    }

    public function clearCache(): void
    {
        foreach (array_keys(self::TYPES) as $type) {
            Cache::forget("local_cinema:{$type}");
        }
    }

    /**
     * Fetch and normalize the feed. Returns null on any failure so the caller
     * can tell "remote broken" apart from "remote has zero items".
     */
    protected function fetch(string $type): ?array
    {
        try {
            $response = Http::timeout(config('services.local_cinema.timeout', 8))
                ->acceptJson()
                ->get(config('services.local_cinema.feed_url'), [
                    'limit' => config('services.local_cinema.limit', 12),
                    'type' => $type,
                ]);

            if (! $response->successful() || ! is_array($response->json('items'))) {
                Log::warning('Local Cinema feed: bad response', ['type' => $type, 'status' => $response->status()]);

                return null;
            }

            return collect($response->json('items'))
                ->map(fn ($item) => is_array($item) ? $this->normalize($item) : null)
                ->filter()
                ->values()
                ->all();
        } catch (Throwable $e) {
            Log::warning('Local Cinema feed: request failed', ['type' => $type, 'error' => $e->getMessage()]);

            return null;
        }
    }

    protected function normalize(array $item): ?array
    {
        $url = $this->safeUrl($item['url'] ?? null);
        $rawTitle = $this->decode($item['title'] ?? '');

        if (! $url || $rawTitle === '') {
            return null;
        }

        // Titles arrive as "Name (Year)"; split them so the year can be styled separately.
        $year = null;
        $title = $rawTitle;
        if (preg_match('/^(.*?)\s*\((\d{4})\)\s*$/u', $rawTitle, $m)) {
            $title = $m[1];
            $year = (int) $m[2];
        }

        $description = $this->decode($item['content_text'] ?? $item['summary'] ?? '');
        // Many entries only carry a generic "Watch X online on Local Cinema." blurb; hide it.
        if (Str::startsWith($description, 'Watch ') && Str::endsWith($description, 'on Local Cinema.')) {
            $description = '';
        }

        return [
            'title' => $title,
            'year' => $year,
            'url' => $url,
            'description' => Str::limit($description, 220),
            'image' => $this->safeUrl($item['image'] ?? null) ?? $this->imageFromHtml($item['content_html'] ?? ''),
            'tags' => array_values(array_filter((array) ($item['tags'] ?? []), 'is_string')),
            'published_at' => $item['date_published'] ?? null,
        ];
    }

    /** Some items have no `image` but embed the poster inside content_html. */
    protected function imageFromHtml(string $html): ?string
    {
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
            return $this->safeUrl(html_entity_decode($m[1]));
        }

        return null;
    }

    /** Only allow absolute http(s) URLs, so the feed can never inject javascript: links. */
    protected function safeUrl(mixed $url): ?string
    {
        if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true) ? $url : null;
    }

    protected function decode(mixed $text): string
    {
        return is_string($text) ? trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8')) : '';
    }
}
