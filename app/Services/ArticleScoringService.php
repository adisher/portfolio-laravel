<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CollectedArticle;
use App\Models\RssSource;

class ArticleScoringService
{
    /**
     * Cached category keywords from database.
     */
    protected ?array $dbCategoryKeywords = null;
    /**
     * Category-specific keywords with weights.
     */
    protected array $categoryKeywords = [
        'ai-machine-learning' => [
            'high' => ['artificial intelligence', 'machine learning', 'deep learning', 'neural network', 'gpt', 'llm', 'transformer', 'chatgpt', 'claude', 'gemini', 'openai', 'anthropic'],
            'medium' => ['ai', 'ml', 'model', 'training', 'inference', 'nlp', 'computer vision', 'generative', 'prompt'],
            'low' => ['automation', 'algorithm', 'data science', 'prediction'],
        ],
        'web-development' => [
            'high' => ['javascript', 'react', 'vue', 'angular', 'node.js', 'typescript', 'css', 'html', 'frontend', 'backend', 'full stack'],
            'medium' => ['web', 'api', 'rest', 'graphql', 'webpack', 'vite', 'tailwind', 'bootstrap', 'sass'],
            'low' => ['browser', 'responsive', 'mobile', 'pwa'],
        ],
        'tech-news' => [
            'high' => ['announced', 'launches', 'acquired', 'funding', 'billion', 'million', 'breaking'],
            'medium' => ['update', 'release', 'new', 'industry', 'market', 'company'],
            'low' => ['report', 'analysis', 'trend'],
        ],
        'programming' => [
            'high' => ['php', 'laravel', 'python', 'java', 'golang', 'rust', 'c++', 'algorithm', 'data structure'],
            'medium' => ['code', 'programming', 'developer', 'software', 'architecture', 'design pattern', 'clean code'],
            'low' => ['debugging', 'testing', 'refactoring'],
        ],
        'design-ux' => [
            'high' => ['ui', 'ux', 'user experience', 'user interface', 'design system', 'figma', 'accessibility'],
            'medium' => ['design', 'prototype', 'wireframe', 'usability', 'a11y'],
            'low' => ['color', 'typography', 'layout'],
        ],
        'devops-cloud' => [
            'high' => ['kubernetes', 'docker', 'aws', 'azure', 'gcp', 'terraform', 'ci/cd', 'devops'],
            'medium' => ['cloud', 'container', 'infrastructure', 'deployment', 'pipeline', 'microservices'],
            'low' => ['server', 'hosting', 'monitoring'],
        ],
        'career-growth' => [
            'high' => ['career', 'salary', 'interview', 'hiring', 'job', 'remote work', 'leadership'],
            'medium' => ['productivity', 'skills', 'learning', 'mentor', 'team', 'management'],
            'low' => ['tips', 'advice', 'growth'],
        ],
    ];

    /**
     * Engagement signal keywords.
     */
    protected array $engagementSignals = [
        'high' => ['how to', 'guide', 'tutorial', 'step by step', 'complete', 'ultimate', 'best practices', 'top 10', 'top 5'],
        'medium' => ['why', 'what is', 'introduction', 'beginner', 'advanced', 'tips', 'tricks'],
        'low' => ['update', 'new', 'latest', 'review'],
    ];

    /**
     * Calculate the overall relevance score for an article.
     */
    public function calculateScore(CollectedArticle $article): float
    {
        $content = strtolower($article->title . ' ' . ($article->description ?? ''));

        // 1. Keyword Relevance (40%)
        $keywordScore = $this->calculateKeywordScore($content, $article->rssSource);

        // 2. Source Authority (20%)
        $sourceScore = $this->calculateSourceScore($article->rssSource);

        // 3. Recency (15%)
        $recencyScore = $this->calculateRecencyScore($article->published_at);

        // 4. Engagement Potential (15%)
        $engagementScore = $this->calculateEngagementScore($content);

        // 5. Content Quality (10%)
        $qualityScore = $this->calculateQualityScore($article);

        $totalScore = ($keywordScore * 0.40) +
                      ($sourceScore * 0.20) +
                      ($recencyScore * 0.15) +
                      ($engagementScore * 0.15) +
                      ($qualityScore * 0.10);

        return min(100, max(0, $totalScore));
    }

    /**
     * Calculate keyword relevance score.
     */
    protected function calculateKeywordScore(string $content, ?RssSource $source): float
    {
        $score = 0;
        $maxScore = 100;

        // Get target category keywords
        $targetCategory = $source?->targetCategory?->slug ?? null;
        $keywords = $this->categoryKeywords[$targetCategory] ?? [];

        if (empty($keywords)) {
            // Check all categories and use best match
            $bestScore = 0;
            foreach ($this->categoryKeywords as $catKeywords) {
                $catScore = $this->matchKeywords($content, $catKeywords);
                $bestScore = max($bestScore, $catScore);
            }
            return $bestScore;
        }

        return $this->matchKeywords($content, $keywords);
    }

    /**
     * Match keywords against content.
     */
    protected function matchKeywords(string $content, array $keywords): float
    {
        $score = 0;

        // High-weight keywords (10 points each, max 50)
        $highMatches = 0;
        foreach ($keywords['high'] ?? [] as $keyword) {
            if (str_contains($content, $keyword)) {
                $highMatches++;
            }
        }
        $score += min(50, $highMatches * 10);

        // Medium-weight keywords (5 points each, max 30)
        $mediumMatches = 0;
        foreach ($keywords['medium'] ?? [] as $keyword) {
            if (str_contains($content, $keyword)) {
                $mediumMatches++;
            }
        }
        $score += min(30, $mediumMatches * 5);

        // Low-weight keywords (2 points each, max 20)
        $lowMatches = 0;
        foreach ($keywords['low'] ?? [] as $keyword) {
            if (str_contains($content, $keyword)) {
                $lowMatches++;
            }
        }
        $score += min(20, $lowMatches * 2);

        return min(100, $score);
    }

    /**
     * Calculate source authority score.
     */
    protected function calculateSourceScore(?RssSource $source): float
    {
        if (!$source) {
            return 50;
        }

        // Priority is 1-10, convert to 0-100 scale
        return $source->priority * 10;
    }

    /**
     * Calculate recency score (fresher = higher).
     */
    protected function calculateRecencyScore($publishedAt): float
    {
        if (!$publishedAt) {
            return 50;
        }

        $hoursAgo = now()->diffInHours($publishedAt);

        // Exponential decay
        if ($hoursAgo <= 1) return 100;
        if ($hoursAgo <= 6) return 90;
        if ($hoursAgo <= 12) return 80;
        if ($hoursAgo <= 24) return 70;
        if ($hoursAgo <= 48) return 60;
        if ($hoursAgo <= 72) return 50;
        if ($hoursAgo <= 168) return 40; // 1 week
        if ($hoursAgo <= 336) return 30; // 2 weeks

        return 20;
    }

    /**
     * Calculate engagement potential score.
     */
    protected function calculateEngagementScore(string $content): float
    {
        $score = 0;

        // High engagement signals (15 points each, max 45)
        $highMatches = 0;
        foreach ($this->engagementSignals['high'] as $signal) {
            if (str_contains($content, $signal)) {
                $highMatches++;
            }
        }
        $score += min(45, $highMatches * 15);

        // Medium engagement signals (10 points each, max 35)
        $mediumMatches = 0;
        foreach ($this->engagementSignals['medium'] as $signal) {
            if (str_contains($content, $signal)) {
                $mediumMatches++;
            }
        }
        $score += min(35, $mediumMatches * 10);

        // Low engagement signals (5 points each, max 20)
        $lowMatches = 0;
        foreach ($this->engagementSignals['low'] as $signal) {
            if (str_contains($content, $signal)) {
                $lowMatches++;
            }
        }
        $score += min(20, $lowMatches * 5);

        return min(100, $score);
    }

    /**
     * Calculate content quality score.
     */
    protected function calculateQualityScore(CollectedArticle $article): float
    {
        $score = 50; // Base score

        // Description length bonus
        $descriptionLength = strlen($article->description ?? '');
        if ($descriptionLength > 500) $score += 20;
        elseif ($descriptionLength > 200) $score += 15;
        elseif ($descriptionLength > 100) $score += 10;
        elseif ($descriptionLength > 50) $score += 5;

        // Title quality
        $titleLength = strlen($article->title);
        if ($titleLength >= 30 && $titleLength <= 100) $score += 15;
        elseif ($titleLength >= 20 && $titleLength <= 120) $score += 10;

        // Has author
        if (!empty($article->author)) {
            $score += 15;
        }

        return min(100, $score);
    }

    /**
     * Get the best matching category for an article based on keywords.
     */
    public function detectCategory(CollectedArticle $article): ?string
    {
        $content = strtolower($article->title . ' ' . ($article->description ?? ''));
        $scores = [];

        // Use database keywords first, fallback to hardcoded
        $allKeywords = $this->getCategoryKeywordsFromDb();

        foreach ($allKeywords as $category => $keywords) {
            $scores[$category] = $this->matchKeywordsFromDb($content, $keywords);
        }

        // Also check hardcoded keywords
        foreach ($this->categoryKeywords as $category => $keywords) {
            $hardcodedScore = $this->matchKeywords($content, $keywords);
            $scores[$category] = max($scores[$category] ?? 0, $hardcodedScore);
        }

        arsort($scores);
        $bestCategory = array_key_first($scores);

        // Only return if confidence is above threshold
        if ($scores[$bestCategory] >= 30) {
            return $bestCategory;
        }

        return null;
    }

    /**
     * Get category confidence score.
     */
    public function getCategoryConfidence(CollectedArticle $article, string $categorySlug): float
    {
        $content = strtolower($article->title . ' ' . ($article->description ?? ''));

        // Check database keywords
        $dbKeywords = $this->getCategoryKeywordsFromDb()[$categorySlug] ?? [];
        $dbScore = $this->matchKeywordsFromDb($content, $dbKeywords);

        // Check hardcoded keywords
        $hardcodedKeywords = $this->categoryKeywords[$categorySlug] ?? [];
        $hardcodedScore = empty($hardcodedKeywords) ? 0 : $this->matchKeywords($content, $hardcodedKeywords);

        return max($dbScore, $hardcodedScore);
    }

    /**
     * Get category keywords from the database.
     */
    protected function getCategoryKeywordsFromDb(): array
    {
        if ($this->dbCategoryKeywords !== null) {
            return $this->dbCategoryKeywords;
        }

        $this->dbCategoryKeywords = [];
        $categories = Category::active()->whereNotNull('keywords')->get();

        foreach ($categories as $category) {
            $this->dbCategoryKeywords[$category->slug] = $category->keywords ?? [];
        }

        return $this->dbCategoryKeywords;
    }

    /**
     * Match flat keywords array against content.
     */
    protected function matchKeywordsFromDb(string $content, array $keywords): float
    {
        if (empty($keywords)) {
            return 0;
        }

        $matches = 0;
        $total = count($keywords);

        foreach ($keywords as $keyword) {
            if (str_contains($content, strtolower($keyword))) {
                $matches++;
            }
        }

        // Scale to 0-100
        return min(100, ($matches / max(1, min($total, 5))) * 100);
    }
}
