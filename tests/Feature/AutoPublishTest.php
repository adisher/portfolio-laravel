<?php

namespace Tests\Feature;

use App\Models\AutoPublishSetting;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\CollectedArticle;
use App\Models\RssSource;
use App\Models\User;
use App\Services\AutoPublishService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the auto-publish pipeline, which posts to the live blog on a daily
 * cron with nobody watching. The valuable cases are the filters — anything that
 * wrongly passes here becomes a published page.
 */
class AutoPublishTest extends TestCase
{
    use RefreshDatabase;

    private AutoPublishService $service;

    protected function setUp(): void
    {
        parent::setUp();

        // No outbound calls: Pexels images, IndexNow pings, AI transforms.
        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);

        // AI enhancement off — exercised separately; here we want the
        // non-AI fallback path so the filters are what's under test.
        config(['blog_automation.ai.enabled' => false]);

        User::factory()->create();

        $this->service = app(AutoPublishService::class);
    }

    private function settings(array $attributes = []): AutoPublishSetting
    {
        $settings = AutoPublishSetting::getInstance();
        $settings->update(array_merge([
            'enabled'                    => true,
            'min_score_for_auto_publish' => 85,
            'ai_enhancement_enabled'     => false,
        ], $attributes));

        return $settings->fresh();
    }

    private function blogCategory(string $slug = 'web-development'): Category
    {
        return Category::create([
            'name'      => ucfirst(str_replace('-', ' ', $slug)),
            'slug'      => $slug,
            'is_active' => true,
            'for_blog'  => true,
        ]);
    }

    private function source(bool $autoPublish = true): RssSource
    {
        return RssSource::create([
            'name'         => 'Test Feed',
            'url'          => 'https://example.com/feed-' . uniqid() . '.xml',
            'category'     => 'dev',
            'active'       => true,
            'auto_publish' => $autoPublish,
        ]);
    }

    private function article(Category $category, array $attributes = [], ?RssSource $source = null): CollectedArticle
    {
        return CollectedArticle::create(array_merge([
            'rss_source_id'        => ($source ?? $this->source())->id,
            'title'                => 'A Perfectly Good Article ' . uniqid(),
            'description'          => 'Something worth reading about software.',
            'url'                  => 'https://example.com/post-' . uniqid(),
            'author'               => 'Jane Doe',
            'published_at'         => now()->subDay(),
            'relevance_score'      => 90,
            'status'               => 'approved',
            'is_duplicate'         => false,
            'assigned_category_id' => $category->id,
        ], $attributes));
    }

    // ── Master switch ──────────────────────────────────────

    public function test_publishes_nothing_when_disabled(): void
    {
        $this->settings(['enabled' => false]);
        $category = $this->blogCategory();
        $this->article($category);

        $result = $this->service->run();

        $this->assertSame('disabled', $result['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    // ── Happy path ─────────────────────────────────────────

    public function test_publishes_an_eligible_article(): void
    {
        $this->settings();
        $category = $this->blogCategory();
        $article  = $this->article($category);

        $result = $this->service->run();

        $this->assertSame('success', $result['status']);
        $this->assertSame(1, $result['published']);

        $post = BlogPost::first();
        $this->assertNotNull($post);
        $this->assertSame('published', $post->status);
        $this->assertSame('curated', $post->source_type);
        $this->assertSame($category->id, $post->category_id);
        $this->assertSame($article->url, $post->original_url);
        $this->assertNotNull($post->published_at);

        // The source article is marked consumed so it cannot be picked up again.
        $article->refresh();
        $this->assertSame('published', $article->status);
        $this->assertSame($post->id, $article->blog_post_id);
    }

    public function test_dry_run_reports_without_writing(): void
    {
        $this->settings();
        $this->article($this->blogCategory());

        $result = $this->service->run(dryRun: true);

        $this->assertSame(1, $result['published']);
        $this->assertSame('would_publish', $result['posts'][0]['action']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    // ── Filters ────────────────────────────────────────────

    public function test_skips_articles_below_the_minimum_score(): void
    {
        $this->settings(['min_score_for_auto_publish' => 85]);
        $this->article($this->blogCategory(), ['relevance_score' => 84]);

        $result = $this->service->run();

        $this->assertSame('no_articles', $result['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_skips_articles_outside_the_freshness_window(): void
    {
        config(['blog_automation.publishing.freshness_days' => 30]);
        $this->settings();

        $article = $this->article($this->blogCategory());
        $article->forceFill(['created_at' => now()->subDays(31)])->save();

        $result = $this->service->run();

        $this->assertSame('no_articles', $result['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_skips_sources_not_marked_auto_publish(): void
    {
        $this->settings();
        $this->article($this->blogCategory(), [], $this->source(autoPublish: false));

        $result = $this->service->run();

        $this->assertSame('no_articles', $result['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_skips_duplicates(): void
    {
        $this->settings();
        $this->article($this->blogCategory(), ['is_duplicate' => true]);

        $this->assertSame('no_articles', $this->service->run()['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_skips_unapproved_articles(): void
    {
        $this->settings();
        $this->article($this->blogCategory(), ['status' => 'pending']);

        $this->assertSame('no_articles', $this->service->run()['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_skips_articles_already_linked_to_a_post(): void
    {
        $this->settings();
        $category = $this->blogCategory();

        $existing = BlogPost::create([
            'title'        => 'Existing',
            'slug'         => 'existing',
            'excerpt'      => 'x',
            'content'      => 'x',
            'status'       => 'published',
            'published_at' => now(),
            'category_id'  => $category->id,
            'user_id'      => User::first()->id,
        ]);

        $this->article($category, ['blog_post_id' => $existing->id]);

        $this->assertSame('no_articles', $this->service->run()['status']);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_ignores_categories_not_flagged_for_blog(): void
    {
        $this->settings();

        $projectsOnly = Category::create([
            'name'         => 'Projects',
            'slug'         => 'projects-only',
            'is_active'    => true,
            'for_blog'     => false,
            'for_projects' => true,
        ]);
        $this->article($projectsOnly);

        $this->assertSame('no_articles', $this->service->run()['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    public function test_ignores_inactive_categories(): void
    {
        $this->settings();

        $inactive = Category::create([
            'name'      => 'Retired',
            'slug'      => 'retired',
            'is_active' => false,
            'for_blog'  => true,
        ]);
        $this->article($inactive);

        $this->assertSame('no_articles', $this->service->run()['status']);
        $this->assertDatabaseCount('blog_posts', 0);
    }

    // ── Per-category quota ─────────────────────────────────

    public function test_respects_the_per_category_daily_quota(): void
    {
        $this->settings();
        $category = $this->blogCategory();
        $source   = $this->source();

        // Three candidates, quota of one.
        $this->article($category, [], $source);
        $this->article($category, [], $source);
        $this->article($category, [], $source);

        $result = $this->service->run(perCategoryMax: 1);

        $this->assertSame(1, $result['published']);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_counts_posts_already_published_today_against_the_quota(): void
    {
        $this->settings();
        $category = $this->blogCategory();

        BlogPost::create([
            'title'        => 'Published earlier today',
            'slug'         => 'published-earlier-today',
            'excerpt'      => 'x',
            'content'      => 'x',
            'status'       => 'published',
            'published_at' => now(),
            'source_type'  => 'curated',
            'category_id'  => $category->id,
            'user_id'      => User::first()->id,
        ]);

        $this->article($category);

        $result = $this->service->run(perCategoryMax: 1);

        $this->assertSame('no_articles', $result['status']);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_fills_each_blog_category_independently(): void
    {
        $this->settings();
        $web    = $this->blogCategory('web-development');
        $design = $this->blogCategory('design-ux');
        $source = $this->source();

        $this->article($web, [], $source);
        $this->article($design, [], $source);

        $result = $this->service->run(perCategoryMax: 1);

        $this->assertSame(2, $result['published']);
        $this->assertSame(1, BlogPost::where('category_id', $web->id)->count());
        $this->assertSame(1, BlogPost::where('category_id', $design->id)->count());
    }

    // ── Resilience ─────────────────────────────────────────

    public function test_a_failing_indexnow_ping_does_not_block_publishing(): void
    {
        Http::fake(['*' => Http::response('boom', 500)]);

        $this->settings();
        $this->article($this->blogCategory());

        $result = $this->service->run();

        $this->assertSame(1, $result['published']);
        $this->assertDatabaseCount('blog_posts', 1);
    }

    public function test_generates_fallback_content_when_ai_is_disabled(): void
    {
        $this->settings(['ai_enhancement_enabled' => false]);
        $this->article($this->blogCategory());

        $this->service->run();

        $post = BlogPost::first();
        $this->assertStringContainsString('Read the original article', $post->content);
        $this->assertGreaterThanOrEqual(1, $post->reading_time);
    }
}
