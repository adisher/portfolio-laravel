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
        // CLAUDE_API_KEY is the canonical name; ANTHROPIC_API_KEY is accepted as
        // a fallback. Resolved here (not in the service) so it survives config:cache.
        'api_key' => env('CLAUDE_API_KEY') ?: env('ANTHROPIC_API_KEY'),
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
            // Review platforms: highest density of real, quotable user complaints
            // about competing products, and (unlike Reddit) fully indexed.
            'trustpilot.com',
            'g2.com',
            'capterra.com',
            'getapp.com',
            // Community platforms. NOTE: Reddit blocks non-Google crawlers via
            // robots.txt (July 2024), so site:reddit.com through a non-Google index
            // returns very little. Kept for coverage, not relied on.
            'reddit.com',
            'news.ycombinator.com',
            'indiehackers.com',
            'producthunt.com',
            'lobste.rs',
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
    | Breaking-News Significance Detection
    |--------------------------------------------------------------------------
    |
    | A cheap, deterministic (no AI cost) signal that catches major stories
    | the normal per-category keyword scorer misses because it only judges
    | keyword-density on a short RSS snippet. When a trigger word/phrase and
    | a notable entity co-occur in the SAME SENTENCE of the article, the
    | story is treated as significant regardless of its composite score,
    | auto-approved, and published immediately (not queued for the next
    | daily batch). See App\Services\SignificanceDetector.
    |
    */
    'significance' => [
        'enabled' => env('SIGNIFICANCE_DETECTION_ENABLED', true),

        // Who gets the "breaking news auto-published" email. Deliberately NOT
        // the admin User's login email (that's a placeholder used only for
        // authentication, not a real inbox) — a dedicated address, same
        // pattern as ai.budget_alert_email above.
        'alert_email' => env('BREAKING_NEWS_ALERT_EMAIL', 'adilsher973@gmail.com'),

        // Company/individual names whose presence signals a story matters.
        // Matched case-insensitively as whole words/phrases.
        // ONLY these RSS sources may trigger the breaking-news fast path.
        // Matched against rss_sources.name. Community/aggregator blogs
        // (dev.to etc.) are deliberately absent: their posts are tutorials
        // and personal essays whose boilerplate ("Buy Me a Coffee" links,
        // footers, tag lists) produced 10/10 false positives on the first
        // run. An empty array disables the allowlist (allows every source).
        'source_allowlist' => [
            'TechCrunch',
            'Ars Technica',
            'The Verge',
            'Wired',
        ],

        // Sentence sanity bounds. The "same sentence" rule is the core
        // guardrail, but on de-tagged HTML the splitter produced page-sized
        // blobs (nav + CSS + footer), silently degrading it to "same page".
        // Anything outside these bounds, or containing markup/code residue,
        // is not real prose and is skipped.
        'sentence_min_chars' => 20,
        'sentence_max_chars' => 400,

        // Matched as WHOLE WORDS (\b-delimited), never substrings — plain
        // str_contains matched "amazon" inside "amazonaws.com" and "meta"
        // inside markup, which is how an S3 image URL became breaking news.
        'notable_entities' => [
            'openai', 'anthropic', 'spacex', 'google', 'microsoft', 'meta',
            'xai', 'nvidia', 'apple', 'amazon', 'tesla', 'cursor', 'github',
            'elon musk', 'sam altman', 'dario amodei', 'daniela amodei',
            'satya nadella', 'sundar pichai', 'mark zuckerberg', 'jensen huang',
            'demis hassabis',
        ],

        // Regex patterns (case-insensitive) for trigger language. Word
        // families/synonyms, not single exact words, so phrasing variance
        // (acquisition vs acquired vs acquiring) doesn't cause a miss.
        // Acquisition/funding language specifically. Deliberately NARROW:
        // bare buy/bought/buying were removed after matching "Buy Me a
        // Coffee", "Buy a Mac" and "bought a copy of a C++ book". Real deals
        // are still caught because the money pattern fires on them
        // ("SpaceX buys Cursor for $60 billion" matches on "$60 billion").
        // NOTE: mergers? must NOT be written merger?s? — that also matches
        // the word "merge" and fired on Git merge-conflict tutorials.
        'trigger_patterns' => [
            '/\bacqui(?:re|res|red|ring|sition|sitions)\b/i',
            '/\bmergers?\b/i',
            '/\btakeovers?\b/i',
            '/\bIPO\b/i',
            '/\braises?\s+\$/i',
            '/\bfunding\s+round\b/i',
            '/\bvaluation\b/i',
            '/\$\s?\d[\d,.]*\s?(?:billion|million|trillion|B|M|T)\b/i',
        ],

        // Full-article HTTP fetch (no AI cost, just bandwidth). Default OFF:
        // every false positive in the first production run came from page
        // chrome (footers, CSS, author bios, comment sections) rather than
        // article prose, while the story this was built for (the SpaceX /
        // Cursor deal) is detectable from the RSS title and description
        // alone. Enable once the guards above are observed holding on real
        // traffic.
        'fetch_full_article' => env('SIGNIFICANCE_FETCH_FULL_ARTICLE', false),
        'fetch_timeout_seconds' => 6,
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
