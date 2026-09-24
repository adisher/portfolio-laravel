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
use App\Mail\QualityGateRejected;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
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


    /**
     * A rewrite that satisfies the quality gate: its own headline, enough of
     * its own prose, sections, first person and the attribution line.
     *
     * Without this the tests fall back to generateBasicContent(), which
     * publishes the SOURCE title over a two-line stub and is now (correctly)
     * blocked by the gate.
     */
    private function goodDraft(): array
    {
        $body = str_repeat('I wired this into a production queue last week and the tradeoffs surprised me. ', 40);

        return [
            'title' => 'Why I Stopped Trusting My Own Queue Metrics',
            'content' => "## Where this bites\n\n{$body}\n\n## My take\n\n{$body}\n\n"
                . '*Source: [Read the original article](https://example.com/post)*',
            'tldr' => 'A short excerpt about queue metrics.',
            'headline_missing' => false,
        ];
    }

    private function article(Category $category, array $attributes = [], ?RssSource $source = null): CollectedArticle
    {
        return CollectedArticle::create(array_merge([
            'ai_generated_content' => $this->goodDraft(),
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

    public function test_publishes_a_rewrite_that_clears_the_quality_gate(): void
    {
        $this->settings(['ai_enhancement_enabled' => false]);
        $this->article($this->blogCategory());

        $this->service->run();

        $post = BlogPost::first();
        $this->assertStringContainsString('Read the original article', $post->content);
        $this->assertGreaterThanOrEqual(1, $post->reading_time);
    }

    /**
     * The non-AI fallback publishes the SOURCE headline over a two-line stub,
     * which is exactly what the quality gate exists to stop. So when the AI
     * rewrite is unavailable (budget exhausted, API failure) the article is
     * parked and nothing is published, rather than a thin post going out under
     * someone else's headline.
     */
    public function test_the_basic_fallback_stub_is_blocked_and_parked(): void
    {
        $this->settings(['ai_enhancement_enabled' => false]);
        $category = $this->blogCategory();
        $article = $this->article($category, ['ai_generated_content' => null]);

        $this->service->run();

        $this->assertSame(0, BlogPost::count(), 'A stub under the source headline must never publish.');
        $this->assertNotNull($article->fresh()->parked_at, 'The failed draft should be parked, not left in the queue.');
    }

    public function test_every_rejection_sends_an_alert_email(): void
    {
        Mail::fake();

        $this->settings(['ai_enhancement_enabled' => false]);
        $category = $this->blogCategory();
        $article = $this->article($category, ['ai_generated_content' => null]);

        $this->service->run();

        Mail::assertSent(QualityGateRejected::class, function ($mail) use ($article) {
            return $mail->article->id === $article->id
                && in_array('title_matches_source', $mail->verdict['hard_failures'], true);
        });
    }

    public function test_a_failing_alert_email_does_not_break_the_run(): void
    {
        $this->settings(['ai_enhancement_enabled' => false]);
        $category = $this->blogCategory();
        $article = $this->article($category, ['ai_generated_content' => null]);

        config(['mail.default' => 'no-such-transport']);

        $this->service->run();

        $this->assertNotNull($article->fresh()->parked_at, 'Parking must happen even when the alert cannot be sent.');
    }

    public function test_a_failed_draft_is_retried_once_with_the_next_candidate(): void
    {
        $this->settings(['ai_enhancement_enabled' => false]);
        $category = $this->blogCategory();

        // Newest first, so this one is tried first and fails the gate.
        $this->article($category, [
            'ai_generated_content' => null,
            'created_at' => now(),
        ]);
        $good = $this->article($category, ['created_at' => now()->subHour()]);

        $this->service->run();

        $this->assertSame(1, BlogPost::count(), 'The retry should still produce the day post.');
        $this->assertSame($good->id, CollectedArticle::find($good->id)->id);
        $this->assertNotNull($good->fresh()->blog_post_id, 'The second candidate is the one that published.');
    }
}
