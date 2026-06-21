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
     *
     * Publishes the newest-fetched qualifying articles, balanced so each blog
     * category receives at least the configured number of posts per day.
     *
     * @param bool     $dryRun         Simulate without publishing
     * @param int|null $perCategoryMax Override posts-per-category-per-day
     */
    public function run(bool $dryRun = false, ?int $perCategoryMax = null): array
    {
        $settings = AutoPublishSetting::getInstance();

        if (!$settings->enabled) {
            return ['status' => 'disabled', 'message' => 'Auto-publish is disabled'];
        }

        $perCategory   = $perCategoryMax ?? (int) config('blog_automation.publishing.per_category_per_day', 1);
        $freshnessDays = (int) config('blog_automation.publishing.freshness_days', 30);
        $minScore      = $settings->min_score_for_auto_publish;

        $results = [
            'status' => 'success',
            'published' => 0,
            'skipped' => 0,
            'errors' => 0,
            'posts' => [],
        ];

        $categories = Category::active()->forBlog()->get();

        foreach ($categories as $category) {
            // How many curated posts already published today in this category
            $publishedToday = BlogPost::where('category_id', $category->id)
                ->where('source_type', 'curated')
                ->whereDate('published_at', now()->toDateString())
                ->count();

            $toPublish = max(0, $perCategory - $publishedToday);
            if ($toPublish === 0) {
                continue;
            }

            // Newest-fetched qualifying articles for this category, within freshness window
            $articles = CollectedArticle::where('status', 'approved')
                ->where('is_duplicate', false)
                ->whereNull('blog_post_id')
                ->where('assigned_category_id', $category->id)
                ->where('relevance_score', '>=', $minScore)
                ->where('created_at', '>=', now()->subDays($freshnessDays))
                ->whereHas('rssSource', fn($q) => $q->where('auto_publish', true))
                ->orderByDesc('created_at')
                ->limit($toPublish)
                ->get();

            foreach ($articles as $article) {
                try {
                    if ($dryRun) {
                        $results['posts'][] = [
                            'article_id' => $article->id,
                            'title' => $article->title,
                            'score' => $article->relevance_score,
                            'category' => $category->name,
                            'action' => 'would_publish',
                        ];
                        $results['published']++;
                        continue;
                    }

                    $post = $this->publishArticle($article, $settings);

                    if ($post) {
                        $results['published']++;
                        $results['posts'][] = [
                            'article_id' => $article->id,
                            'post_id' => $post->id,
                            'title' => $post->title,
                            'slug' => $post->slug,
                            'category' => $category->name,
                        ];
                    } else {
                        $results['skipped']++;
                    }
                } catch (\Exception $e) {
                    Log::error("Auto-publish failed for article {$article->id}: " . $e->getMessage());
                    $results['errors']++;
                }
            }
        }

        if ($results['published'] === 0 && empty($results['posts'])) {
            return ['status' => 'no_articles', 'message' => 'No eligible articles to publish'];
        }

        return $results;
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
            'title' => !empty($content['title']) ? $content['title'] : trim($article->title),
            'slug' => Str::slug($article->title),
            'featured_image' => app(PexelsImageService::class)->fetchForArticle($article)
                ?? $this->categoryDefaultImage($article),
            'excerpt' => !empty($content['tldr']) ? $content['tldr'] : Str::limit(strip_tags($article->description ?? ''), 280),
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
     * Branded per-category fallback image when no usable source image exists.
     */
    protected function categoryDefaultImage(CollectedArticle $article): ?string
    {
        $slug = $article->assignedCategory?->slug;
        if (!$slug) {
            return null;
        }

        $path = 'blog-defaults/' . $slug . '.svg';
        return \Illuminate\Support\Facades\Storage::disk('public')->exists($path) ? $path : null;
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
