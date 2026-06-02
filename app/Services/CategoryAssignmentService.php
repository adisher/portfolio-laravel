<?php

namespace App\Services;

use App\Models\Category;
use App\Models\CollectedArticle;

class CategoryAssignmentService
{
    protected ArticleScoringService $scoringService;

    /**
     * Minimum confidence threshold for auto-assignment.
     */
    protected float $autoAssignThreshold = 30;

    /**
     * Minimum confidence threshold for flagging for review.
     */
    protected float $reviewThreshold = 20;

    public function __construct(ArticleScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Assign a category to an article.
     */
    public function assignCategory(CollectedArticle $article): ?Category
    {
        // First, check if the RSS source has a target category
        if ($article->rssSource && $article->rssSource->target_category_id) {
            $category = $article->rssSource->targetCategory;
            if ($category) {
                $confidence = $this->scoringService->getCategoryConfidence($article, $category->slug);
                $article->update([
                    'assigned_category_id' => $category->id,
                    'category_confidence' => $confidence,
                ]);
                return $category;
            }
        }

        // Auto-detect category based on content
        $detectedSlug = $this->scoringService->detectCategory($article);

        if (!$detectedSlug) {
            return null;
        }

        $category = Category::where('slug', $detectedSlug)->first();

        if (!$category) {
            return null;
        }

        $confidence = $this->scoringService->getCategoryConfidence($article, $detectedSlug);

        $article->update([
            'assigned_category_id' => $category->id,
            'category_confidence' => $confidence,
        ]);

        return $category;
    }

    /**
     * Get all category scores for an article.
     */
    public function getAllCategoryScores(CollectedArticle $article): array
    {
        $categories = Category::active()->get();
        $scores = [];

        foreach ($categories as $category) {
            $scores[$category->slug] = [
                'category' => $category,
                'confidence' => $this->scoringService->getCategoryConfidence($article, $category->slug),
            ];
        }

        // Sort by confidence descending
        uasort($scores, fn($a, $b) => $b['confidence'] <=> $a['confidence']);

        return $scores;
    }

    /**
     * Check if category assignment is confident enough for auto-publishing.
     */
    public function isConfidentAssignment(CollectedArticle $article): bool
    {
        return $article->category_confidence >= $this->autoAssignThreshold;
    }

    /**
     * Check if category assignment needs manual review.
     */
    public function needsReview(CollectedArticle $article): bool
    {
        return $article->category_confidence >= $this->reviewThreshold
            && $article->category_confidence < $this->autoAssignThreshold;
    }

    /**
     * Reassign category for an article (useful after content enhancement).
     */
    public function reassignCategory(CollectedArticle $article): ?Category
    {
        // Clear existing assignment
        $article->update([
            'assigned_category_id' => null,
            'category_confidence' => 0,
        ]);

        return $this->assignCategory($article);
    }

    /**
     * Bulk assign categories to multiple articles.
     */
    public function bulkAssign(array $articleIds): array
    {
        $results = [
            'assigned' => 0,
            'skipped' => 0,
            'failed' => 0,
        ];

        $articles = CollectedArticle::whereIn('id', $articleIds)
            ->whereNull('assigned_category_id')
            ->get();

        foreach ($articles as $article) {
            try {
                $category = $this->assignCategory($article);
                if ($category) {
                    $results['assigned']++;
                } else {
                    $results['skipped']++;
                }
            } catch (\Exception $e) {
                $results['failed']++;
            }
        }

        return $results;
    }
}
