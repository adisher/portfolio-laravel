<?php

return [

    'enabled' => env('SPORTS_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cricbuzz Scraper API (Python Flask on localhost)
    |--------------------------------------------------------------------------
    |
    | The Python Flask scraper runs locally and provides two endpoints:
    |   GET /series/{id}/matches  — all matches in a series
    |   GET /match/{id}           — detailed match data (live/completed/upcoming)
    |
    */

    'cricbuzz' => [
        'base_url' => env('CRICBUZZ_API_URL', 'http://localhost:5000'),
        'series_id' => env('CRICBUZZ_SERIES_ID', ''),
        'timeout' => 15,
    ],

    'polling' => [
        'live_interval' => env('SPORTS_LIVE_POLL_SECONDS', 20),
        'upcoming_interval' => env('SPORTS_UPCOMING_POLL_MINUTES', 5),
    ],

    'cache' => [
        'series_matches_ttl' => 1800,     // 30 minutes
        'live_scores_ttl' => 15,           // 15 seconds
        'match_detail_ttl' => 30,          // 30 seconds
        'completed_match_ttl' => 3600,     // 1 hour
    ],

    'broadcasting' => [
        'driver' => env('SPORTS_BROADCAST_DRIVER', 'pusher'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Lazy Sync — Auto-fetch fresh data when the sports page is visited
    |--------------------------------------------------------------------------
    |
    | When enabled, the SportsController will check if data is stale and
    | automatically trigger a sync on page visit. This is a fallback for
    | when the Laravel scheduler is not running (e.g. local Windows/XAMPP).
    |
    */

    'lazy_sync' => [
        'enabled' => env('SPORTS_LAZY_SYNC', true),
        'ttl' => 600, // seconds between syncs (10 minutes)
    ],

];
