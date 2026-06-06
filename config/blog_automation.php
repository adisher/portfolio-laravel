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
        'model' => env('AI_MODEL', 'claude-3-5-haiku-20241022'),
        'api_key' => env('CLAUDE_API_KEY'),
        'budget_alert_email' => env('AI_BUDGET_ALERT_EMAIL', 'adilsher973@gmail.com'),
        'monthly_budget' => env('AI_MONTHLY_BUDGET', 1.00),
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
