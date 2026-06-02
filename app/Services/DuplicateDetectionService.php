<?php

namespace App\Services;

use App\Models\CollectedArticle;
use App\Models\BlogPost;
use Illuminate\Support\Str;

class DuplicateDetectionService
{
    /**
     * Title similarity threshold (0-1, where 1 is identical).
     */
    protected float $titleSimilarityThreshold = 0.85;

    /**
     * Number of days to look back for duplicates.
     */
    protected int $lookbackDays = 7;

    /**
     * Check if an article is a duplicate.
     */
    public function isDuplicate(CollectedArticle $article): bool
    {
        // 1. Check for exact URL match
        if ($this->hasExactUrlMatch($article)) {
            return true;
        }

        // 2. Check for similar title in collected articles
        if ($this->hasSimilarTitleInArticles($article)) {
            return true;
        }

        // 3. Check for similar title in published blog posts
        if ($this->hasSimilarTitleInBlogPosts($article)) {
            return true;
        }

        return false;
    }

    /**
     * Check and mark duplicate articles.
     */
    public function checkAndMark(CollectedArticle $article): bool
    {
        $duplicateOf = $this->findDuplicateOf($article);

        if ($duplicateOf) {
            $article->update([
                'is_duplicate' => true,
                'duplicate_of_id' => $duplicateOf->id,
            ]);
            return true;
        }

        $article->update([
            'is_duplicate' => false,
            'duplicate_of_id' => null,
        ]);

        return false;
    }

    /**
     * Find the original article this one is a duplicate of.
     */
    public function findDuplicateOf(CollectedArticle $article): ?CollectedArticle
    {
        // Check exact URL match first
        $urlMatch = CollectedArticle::where('url', $article->url)
            ->where('id', '!=', $article->id)
            ->first();

        if ($urlMatch) {
            return $urlMatch;
        }

        // Check title similarity
        $recentArticles = CollectedArticle::where('id', '!=', $article->id)
            ->where('created_at', '>', now()->subDays($this->lookbackDays))
            ->get();

        $normalizedTitle = $this->normalizeText($article->title);

        foreach ($recentArticles as $existing) {
            $similarity = $this->calculateSimilarity($normalizedTitle, $this->normalizeText($existing->title));
            if ($similarity >= $this->titleSimilarityThreshold) {
                return $existing;
            }
        }

        return null;
    }

    /**
     * Check for exact URL match.
     */
    protected function hasExactUrlMatch(CollectedArticle $article): bool
    {
        return CollectedArticle::where('url', $article->url)
            ->where('id', '!=', $article->id)
            ->exists();
    }

    /**
     * Check for similar title in collected articles.
     */
    protected function hasSimilarTitleInArticles(CollectedArticle $article): bool
    {
        $recentArticles = CollectedArticle::where('id', '!=', $article->id)
            ->where('created_at', '>', now()->subDays($this->lookbackDays))
            ->pluck('title');

        $normalizedTitle = $this->normalizeText($article->title);

        foreach ($recentArticles as $existingTitle) {
            $similarity = $this->calculateSimilarity($normalizedTitle, $this->normalizeText($existingTitle));
            if ($similarity >= $this->titleSimilarityThreshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for similar title in published blog posts.
     */
    protected function hasSimilarTitleInBlogPosts(CollectedArticle $article): bool
    {
        $recentPosts = BlogPost::where('created_at', '>', now()->subDays($this->lookbackDays))
            ->pluck('title');

        $normalizedTitle = $this->normalizeText($article->title);

        foreach ($recentPosts as $existingTitle) {
            $similarity = $this->calculateSimilarity($normalizedTitle, $this->normalizeText($existingTitle));
            if ($similarity >= $this->titleSimilarityThreshold) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize text for comparison.
     */
    protected function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = Str::lower($text);

        // Remove special characters
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);

        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Calculate similarity between two strings using Levenshtein distance.
     */
    protected function calculateSimilarity(string $str1, string $str2): float
    {
        if ($str1 === $str2) {
            return 1.0;
        }

        $maxLength = max(strlen($str1), strlen($str2));

        if ($maxLength === 0) {
            return 1.0;
        }

        $distance = levenshtein($str1, $str2);

        return 1 - ($distance / $maxLength);
    }

    /**
     * Get similarity score between two articles.
     */
    public function getSimilarityScore(CollectedArticle $article1, CollectedArticle $article2): float
    {
        $title1 = $this->normalizeText($article1->title);
        $title2 = $this->normalizeText($article2->title);

        return $this->calculateSimilarity($title1, $title2);
    }

    /**
     * Find all potential duplicates for an article.
     */
    public function findPotentialDuplicates(CollectedArticle $article, float $threshold = 0.7): array
    {
        $duplicates = [];

        $recentArticles = CollectedArticle::where('id', '!=', $article->id)
            ->where('created_at', '>', now()->subDays($this->lookbackDays))
            ->get();

        $normalizedTitle = $this->normalizeText($article->title);

        foreach ($recentArticles as $existing) {
            $similarity = $this->calculateSimilarity($normalizedTitle, $this->normalizeText($existing->title));
            if ($similarity >= $threshold) {
                $duplicates[] = [
                    'article' => $existing,
                    'similarity' => $similarity,
                ];
            }
        }

        // Sort by similarity descending
        usort($duplicates, fn($a, $b) => $b['similarity'] <=> $a['similarity']);

        return $duplicates;
    }

    /**
     * Bulk check duplicates for multiple articles.
     */
    public function bulkCheck(array $articleIds): array
    {
        $results = [
            'checked' => 0,
            'duplicates_found' => 0,
        ];

        $articles = CollectedArticle::whereIn('id', $articleIds)->get();

        foreach ($articles as $article) {
            $isDuplicate = $this->checkAndMark($article);
            $results['checked']++;
            if ($isDuplicate) {
                $results['duplicates_found']++;
            }
        }

        return $results;
    }
}
