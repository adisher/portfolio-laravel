<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Blog Automation Master Switch
    |--------------------------------------------------------------------------
    |
    | Enable or disable the entire blog automation system.
    |
    */
    'enabled' => env('BLOG_AUTOMATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Publishing Settings
    |--------------------------------------------------------------------------
    */
    'publishing' => [
        'max_per_day' => env('AUTO_PUBLISH_MAX_PER_DAY', 3),
        'min_score' => env('AUTO_PUBLISH_MIN_SCORE', 85),
        'require_review_below' => env('AUTO_PUBLISH_REVIEW_BELOW', 75),
        'times' => ['09:00', '13:00', '17:00'],

        // Publish at least this many posts per blog category per day
        'per_category_per_day' => env('AUTO_PUBLISH_PER_CATEGORY', 1),

        // Only auto-publish articles fetched within this many days (freshness cap)
        'freshness_days' => env('AUTO_PUBLISH_FRESHNESS_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Scoring Weights
    |--------------------------------------------------------------------------
    |
    | Weights for the article scoring algorithm (must sum to 100).
    |
    */
    'scoring' => [
        'keyword_weight' => 40,
        'source_weight' => 20,
        'recency_weight' => 15,
        'engagement_weight' => 15,
        'quality_weight' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'enabled' => env('AI_ENHANCEMENT_ENABLED', true),
        'provider' => 'anthropic',
        'model' => env('AI_MODEL', 'claude-haiku-4-5-20251001'),
        // Stronger model for flagship original articles (weekly cadence = negligible cost)
        'original_model' => env('AI_ORIGINAL_MODEL', 'claude-sonnet-4-6'),
        'api_key' => env('CLAUDE_API_KEY'),
        'budget_alert_email' => env('AI_BUDGET_ALERT_EMAIL', 'adilsher973@gmail.com'),
        'monthly_budget' => env('AI_MONTHLY_BUDGET', 1.00),

        // Per-model pricing, USD per 1,000,000 tokens. Matched by substring of the
        // model id (first match wins), so 'sonnet' covers sonnet-4-6 / sonnet-5 etc.
        // Keep in sync with https://platform.claude.com/docs/en/pricing
        'pricing' => [
            'opus'   => ['input' => 5.00, 'output' => 25.00],
            'sonnet' => ['input' => 3.00, 'output' => 15.00],
            'haiku'  => ['input' => 1.00, 'output' => 5.00],
            'default' => ['input' => 1.00, 'output' => 5.00],
        ],

        // Web search server tool: USD per search (Anthropic ~ $10 / 1000 searches).
        'web_search_cost_per_search' => env('AI_WEB_SEARCH_COST', 0.01),
    ],

    /*
    |--------------------------------------------------------------------------
    | Voice search (social proof)
    |--------------------------------------------------------------------------
    | Community platforms searched for real user comments. This is an ALLOWLIST:
    | only these domains can produce a voice candidate, which makes competitor
    | marketing blogs and SEO listicles structurally impossible. Override per
    | product via the work item's `voice_sources`.
    */
    'voices' => [
        'default_sources' => [
            'reddit.com',
            'news.ycombinator.com',
            'indiehackers.com',
            'lobste.rs',
            'producthunt.com',
            'quora.com',
        ],

        // Titles/URLs matching these are marketing/listicle content, never a voice.
        'reject_patterns' => [
            'best ', 'top ', ' vs ', 'alternatives', 'alternative to', 'review',
            'pricing', 'comparison', 'ultimate guide', 'how to choose',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Weights
    |--------------------------------------------------------------------------
    |
    | Distribution weights for automatic publishing across categories.
    | Higher weight = more posts in that category.
    |
    */
    'category_weights' => [
        'ai-machine-learning' => 30,
        'web-development' => 25,
        'tech-news' => 20,
        'programming' => 15,
        'design-ux' => 5,
        'devops-cloud' => 3,
        'career-growth' => 2,
    ],

    /*
    |--------------------------------------------------------------------------
    | RSS Fetch Settings
    |--------------------------------------------------------------------------
    */
    'rss' => [
        'timeout' => 30,
        'max_articles_per_fetch' => 50,
        'priorities' => [
            'high' => [10],           // Fetch every 15 minutes
            'medium' => [8, 9],       // Fetch every 30 minutes
            'low' => [1, 2, 3, 4, 5, 6, 7], // Fetch every hour
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Duplicate Detection
    |--------------------------------------------------------------------------
    */
    'duplicate_detection' => [
        'title_similarity_threshold' => 0.85,
        'lookback_days' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Enhancement
    |--------------------------------------------------------------------------
    */
    'content' => [
        'include_tldr' => true,
        'include_key_insights' => true,
        'include_faq' => true,
        'min_word_count' => 400,
        'max_word_count' => 800,
    ],

    /*
    |--------------------------------------------------------------------------
    | IndexNow Integration
    |--------------------------------------------------------------------------
    */
    'indexnow' => [
        'enabled' => env('INDEXNOW_ENABLED', true),
        'key' => env('INDEXNOW_KEY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    */
    'cleanup' => [
        'rejected_articles_days' => 30,
        'ai_logs_days' => 90,
    ],

];
