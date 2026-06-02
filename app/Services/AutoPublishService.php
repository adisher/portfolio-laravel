<?php

namespace App\Services;

use App\Models\AutoPublishSetting;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\CollectedArticle;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AutoPublishService
{
    protected AiContentService $aiContentService;
    protected AiBudgetService $budgetService;
    protected IndexNowService $indexNowService;

    public function __construct(
        AiContentService $aiContentService,
        AiBudgetService $budgetService,
        IndexNowService $indexNowService
    ) {
        $this->aiContentService = $aiContentService;
        $this->budgetService = $budgetService;
        $this->indexNowService = $indexNowService;
    }

    /**
     * Run the auto-publish process.
     */
    public function run(bool $dryRun = false): array
    {
        $settings = AutoPublishSetting::getInstance();

        if (!$settings->enabled) {
            return ['status' => 'disabled', 'message' => 'Auto-publish is disabled'];
        }

        if (!$settings->canPublishMore()) {
            return [
                'status' => 'limit_reached',
                'message' => 'Daily publish limit reached',
                'published_today' => $settings->posts_published_today,
                'max_per_day' => $settings->max_posts_per_day,
            ];
        }

        $results = [
            'status' => 'success',
            'published' => 0,
            'skipped' => 0,
            'errors' => 0,
            'posts' => [],
        ];

        // Get eligible articles
        $articles = $this->getEligibleArticles($settings);

        if ($articles->isEmpty()) {
            return [
                'status' => 'no_articles',
                'message' => 'No eligible articles to publish',
            ];
        }

        foreach ($articles as $article) {
            if (!$settings->canPublishMore()) {
                break;
            }

            try {
                if ($dryRun) {
                    $results['posts'][] = [
                        'article_id' => $article->id,
                        'title' => $article->title,
                        'score' => $article->relevance_score,
                        'category' => $article->assignedCategory?->name,
                        'action' => 'would_publish',
                    ];
                    $results['published']++;
                    continue;
                }

                $post = $this->publishArticle($article, $settings);

                if ($post) {
                    $settings->incrementPostCount();
                    $results['published']++;
                    $results['posts'][] = [
                        'article_id' => $article->id,
                        'post_id' => $post->id,
                        'title' => $post->title,
                        'slug' => $post->slug,
                        'category' => $post->category?->name,
                    ];
                } else {
                    $results['skipped']++;
                }

            } catch (\Exception $e) {
                Log::error("Auto-publish failed for article {$article->id}: " . $e->getMessage());
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Get eligible articles for publishing.
     */
    protected function getEligibleArticles(AutoPublishSetting $settings)
    {
        return CollectedArticle::where('status', 'approved')
            ->where('is_duplicate', false)
            ->whereNull('blog_post_id')
            ->whereNotNull('assigned_category_id')
            ->where('relevance_score', '>=', $settings->min_score_for_auto_publish)
            ->whereHas('rssSource', function ($query) {
                $query->where('auto_publish', true);
            })
            ->orderByDesc('relevance_score')
            ->limit($settings->remaining_posts)
            ->get();
    }

    /**
     * Publish a single article as a blog post.
     */
    public function publishArticle(CollectedArticle $article, ?AutoPublishSetting $settings = null): ?BlogPost
    {
        $settings = $settings ?? AutoPublishSetting::getInstance();

        // Generate AI content if enabled
        if ($settings->ai_enhancement_enabled && $this->aiContentService->isEnabled()) {
            $aiContent = $this->aiContentService->transformArticle($article);

            if (!$aiContent) {
                Log::warning("AI enhancement failed for article {$article->id}, publishing with basic content");
            }
        }

        // Get content (AI-generated or fallback)
        $content = $article->ai_generated_content ?? $this->generateBasicContent($article);

        // Get or create author
        $author = User::first();

        // Create the blog post
        $post = BlogPost::create([
            'title' => $content['title'] ?? $article->title,
            'slug' => Str::slug($article->title),
            'excerpt' => $content['tldr'] ?? Str::limit(strip_tags($article->description), 200),
            'content' => $content['content'] ?? $this->generateBasicContent($article)['content'],
            'category_id' => $article->assigned_category_id,
            'user_id' => $author?->id,
            'status' => 'published',
            'published_at' => now(),
            'source_type' => 'curated',
            'original_url' => $article->url,
            'original_author' => $article->author,
            'original_publication' => $article->rssSource?->name,
            'original_published_at' => $article->published_at,
            'meta_title' => $article->seo_data['meta_title'] ?? null,
            'meta_description' => $article->seo_data['meta_description'] ?? Str::limit(strip_tags($article->description), 155),
            'meta_keywords' => $article->seo_data['keywords'] ?? null,
            'reading_time' => $this->calculateReadingTime($content['content'] ?? ''),
        ]);

        // Update article status
        $article->update([
            'status' => 'published',
            'blog_post_id' => $post->id,
        ]);

        // Notify search engines via IndexNow
        try {
            $this->indexNowService->submit(route('blog.show', $post->slug));
        } catch (\Exception $e) {
            Log::warning("IndexNow submission failed for post {$post->id}: " . $e->getMessage());
        }

        Log::info("Auto-published article {$article->id} as blog post {$post->id}");

        return $post;
    }

    /**
     * Generate basic content without AI.
     */
    protected function generateBasicContent(CollectedArticle $article): array
    {
        $content = <<<MARKDOWN
## Summary

{$article->description}

## Source

This article discusses content originally published by {$article->author} at {$article->rssSource?->name}.

[Read the original article]({$article->url})
MARKDOWN;

        return [
            'title' => $article->title,
            'content' => $content,
            'tldr' => Str::limit(strip_tags($article->description), 200),
        ];
    }

    /**
     * Calculate reading time in minutes.
     */
    protected function calculateReadingTime(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));
        return max(1, (int) ceil($wordCount / 200));
    }

    /**
     * Manually publish a specific article.
     */
    public function publishSpecificArticle(CollectedArticle $article): ?BlogPost
    {
        if ($article->blog_post_id) {
            return $article->blogPost;
        }

        return $this->publishArticle($article);
    }

    /**
     * Get publishing statistics.
     */
    public function getStats(): array
    {
        $settings = AutoPublishSetting::getInstance();

        return [
            'enabled' => $settings->enabled,
            'published_today' => $settings->posts_published_today,
            'remaining_today' => $settings->remaining_posts,
            'max_per_day' => $settings->max_posts_per_day,
            'next_publish_time' => $settings->getNextPublishTime(),
            'pending_articles' => CollectedArticle::where('status', 'approved')
                ->where('is_duplicate', false)
                ->whereNull('blog_post_id')
                ->count(),
            'high_score_articles' => CollectedArticle::where('status', 'approved')
                ->where('relevance_score', '>=', $settings->min_score_for_auto_publish)
                ->where('is_duplicate', false)
                ->whereNull('blog_post_id')
                ->count(),
        ];
    }
}
