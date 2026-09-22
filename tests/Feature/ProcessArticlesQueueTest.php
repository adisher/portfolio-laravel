<?php

namespace Tests\Feature;

use App\Models\CollectedArticle;
use App\Models\RssSource;
use App\Services\CategoryAssignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

/**
 * Pins the stall that took the blog pipeline down from roughly 2026-08-19.
 *
 * `articles:process` treated "has no category" as "not yet processed". An
 * article the keyword detector cannot classify keeps a null category forever,
 * so it never left the queue; with no ORDER BY, every hourly run re-took the
 * same 200 unusable rows and never reached the ~14,000 behind them. It was
 * invisible because re-scoring an unchanged row writes nothing at all (an
 * Eloquent update with no changed values is a no-op, so even updated_at
 * stayed put), which is why no log, lock or crash ever showed it.
 */
class ProcessArticlesQueueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response([], 200)]);
    }

    private function source(): RssSource
    {
        return RssSource::create([
            'name'         => 'Community Feed',
            'url'          => 'https://example.com/feed-' . uniqid() . '.xml',
            'category'     => 'dev',
            'active'       => true,
            'auto_publish' => true,
            // No target_category_id: classification must fall to keywords,
            // which is the case that produced the stall.
        ]);
    }

    /** An article no keyword list can match: the kind that clogged the queue. */
    private function uncategorisableArticle(string $title): CollectedArticle
    {
        return CollectedArticle::create([
            'rss_source_id'   => $this->source()->id,
            'title'           => $title,
            'description'     => 'Togetherness, we can shine together today.',
            'url'             => 'https://example.com/post-' . uniqid(),
            'author'          => 'Someone',
            'published_at'    => now()->subMonths(2),
            'relevance_score' => 42,
            'status'          => 'pending',
            'is_duplicate'    => false,
        ]);
    }

    public function test_an_uncategorisable_article_is_still_marked_processed(): void
    {
        $article = $this->uncategorisableArticle('Togetherness we can shine');

        $this->artisan('articles:process', ['--limit' => 5])->assertSuccessful();

        $article->refresh();

        $this->assertNull($article->assigned_category_id, 'Precondition: it should not be classifiable.');
        $this->assertNotNull($article->processed_at, 'It must be marked processed, or it blocks the queue forever.');
    }

    public function test_the_queue_advances_instead_of_re_taking_the_same_rows(): void
    {
        $first = $this->uncategorisableArticle('Togetherness we can shine');
        $second = $this->uncategorisableArticle('A quiet morning, nothing to report');
        $third = $this->uncategorisableArticle('Some personal reflections for today');

        // First run takes the two oldest.
        $this->artisan('articles:process', ['--limit' => 2])->assertSuccessful();

        $this->assertNotNull($first->fresh()->processed_at);
        $this->assertNotNull($second->fresh()->processed_at);
        $this->assertNull($third->fresh()->processed_at, 'Third is beyond the limit on the first pass.');

        // Second run must reach the NEXT article, not chew the same two again.
        $this->artisan('articles:process', ['--limit' => 2])->assertSuccessful();

        $this->assertNotNull($third->fresh()->processed_at, 'The queue must advance past unusable rows.');
    }

    public function test_processed_articles_are_not_picked_up_again(): void
    {
        $this->uncategorisableArticle('Togetherness we can shine');

        $this->artisan('articles:process', ['--limit' => 5])->assertSuccessful();

        $this->artisan('articles:process', ['--limit' => 5])
            ->expectsOutputToContain('No articles to process.')
            ->assertSuccessful();
    }

    public function test_force_reprocesses_articles_that_were_already_processed(): void
    {
        $article = $this->uncategorisableArticle('Togetherness we can shine');

        $this->artisan('articles:process', ['--limit' => 5])->assertSuccessful();
        $firstPass = $article->fresh()->processed_at;

        $this->travel(2)->minutes();
        $this->artisan('articles:process', ['--limit' => 5, '--force' => true])->assertSuccessful();

        $this->assertTrue(
            $article->fresh()->processed_at->greaterThan($firstPass),
            '--force must re-run articles that already carry a processed marker.'
        );
    }

    public function test_an_article_that_throws_is_still_marked_so_it_cannot_block_the_queue(): void
    {
        // A row that blows up mid-processing must not become a permanent
        // blocker for every article queued behind it.
        $article = $this->uncategorisableArticle('Togetherness we can shine');

        $this->mock(CategoryAssignmentService::class, function ($mock) {
            $mock->shouldReceive('assignCategory')->andThrow(new RuntimeException('boom'));
        });

        $this->artisan('articles:process', ['--limit' => 5])->assertSuccessful();

        $this->assertNotNull($article->fresh()->processed_at);
    }

    // -- Source-age gate (14 days by default) ---------------------------

    /** An article can be categorisable and high-scoring, yet still be stale news. */
    private function recentArticle(int $publishedDaysAgo, string $topic = 'CI/CD pipelines'): CollectedArticle
    {
        return CollectedArticle::create([
            'rss_source_id'   => $this->source()->id,
            'title'           => 'Kubernetes and Docker: a complete guide to ' . $topic,
            'description'     => 'A step by step guide to kubernetes, docker, terraform and aws deployment pipelines for devops teams.',
            'url'             => 'https://example.com/post-' . uniqid(),
            'author'          => 'Jane Doe',
            'published_at'    => now()->subDays($publishedDaysAgo),
            'relevance_score' => 0,
            'status'          => 'pending',
            'is_duplicate'    => false,
        ]);
    }

    public function test_a_stale_source_article_is_parked_not_approved(): void
    {
        $article = $this->recentArticle(20);

        $this->artisan('articles:process', ['--limit' => 5, '--auto-approve' => true])->assertSuccessful();

        $article->refresh();

        $this->assertNotNull($article->parked_at, 'Stale articles must be parked, so they stay as source material.');
        $this->assertSame('pending', $article->status, 'Parking must not approve it.');
        $this->assertNotNull($article->processed_at);
    }

    public function test_a_fresh_source_article_is_not_parked(): void
    {
        $article = $this->recentArticle(2);

        $this->artisan('articles:process', ['--limit' => 5, '--auto-approve' => true])->assertSuccessful();

        $this->assertNull($article->fresh()->parked_at, 'A 2-day-old article is well inside the 14-day window.');
    }

    public function test_the_age_gate_boundary_follows_config(): void
    {
        config(['blog_automation.publishing.max_source_age_days' => 7]);

        // Distinct topics: identical titles would trip duplicate detection
        // and be rejected before the age gate is ever reached.
        $justInside = $this->recentArticle(6, 'blue-green deploys');
        $justOutside = $this->recentArticle(8, 'canary rollouts');

        $this->artisan('articles:process', ['--limit' => 5, '--auto-approve' => true])->assertSuccessful();

        $this->assertNull($justInside->fresh()->parked_at);
        $this->assertNotNull($justOutside->fresh()->parked_at);
    }
}
