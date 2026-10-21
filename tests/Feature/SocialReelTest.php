<?php

namespace Tests\Feature;

use App\Mail\ReelReadyForReview;
use App\Mail\ReelRenderFailed;
use App\Models\AiBudgetSetting;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\SocialDraft;
use App\Models\User;
use App\Services\Social\ReelDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Social Studio reels, step 1: AI script + captions, owner edits, and the
 * review gate (nothing approvable before a render; email on every render).
 */
class SocialReelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'blog_automation.ai.enabled' => true,
            'blog_automation.ai.api_key' => 'test-key',
            'social.reels.alert_email'   => 'owner@example.com',
            'social.reels.ai_model'      => 'claude-sonnet-4-6',
        ]);
        AiBudgetSetting::getInstance()->update([
            'monthly_budget_usd' => 5.00, 'current_month_usage_usd' => 0, 'is_paused' => false,
        ]);

        $this->admin = User::factory()->create();
    }

    private function article(): BlogPost
    {
        $category = Category::create(['name' => 'Web Development', 'slug' => 'web-development']);

        return BlogPost::create([
            'title'        => 'Why I moved my queue off Redis',
            'slug'         => 'why-i-moved-my-queue-off-redis',
            'excerpt'      => 'A short story about a long outage.',
            'content'      => '## The problem' . "\n\n" . str_repeat('Redis kept dropping jobs under load. ', 40),
            'status'       => 'published',
            'published_at' => now()->subDay(),
            'category_id'  => $category->id,
            'user_id'      => $this->admin->id,
            'source_type'  => 'original',
            'views'        => 120,
        ]);
    }

    private function fakeClaude(?array $script = null, int $status = 200): void
    {
        $script ??= [
            'scenes' => [
                ['type' => 'hook', 'on_screen_text' => 'Redis lost 3,000 jobs', 'voice_line' => 'Redis lost three thousand of my jobs in one night — here is why.', 'duration_seconds' => 3, 'visual' => 'portrait', 'visual_note' => 'Adil looking at camera'],
                ['type' => 'context', 'on_screen_text' => 'The queue looked healthy', 'voice_line' => 'Every dashboard was green while jobs silently vanished.', 'duration_seconds' => 5, 'visual' => 'featured_image', 'visual_note' => 'Header image'],
                ['type' => 'point', 'on_screen_text' => 'Eviction ate the queue', 'voice_line' => 'The cache eviction policy was deleting queued jobs to free memory.', 'duration_seconds' => 6, 'visual' => 'code', 'visual_note' => "maxmemory-policy allkeys-lru"],
                ['type' => 'end', 'on_screen_text' => 'Full story: link in bio', 'voice_line' => 'The full breakdown is in the link in my bio.', 'duration_seconds' => 4, 'visual' => 'portrait', 'visual_note' => 'End card'],
            ],
            'instagram' => ['caption' => "Redis ate my queue.\n\nFull article: link in bio.", 'hashtags' => ['#Laravel', 'Redis', 'queues']],
            'youtube'   => ['title' => 'Redis lost 3,000 of my jobs', 'description' => 'What happened and the fix.', 'tags' => ['laravel', 'redis']],
        ];

        Http::fake([
            'api.anthropic.com/*' => Http::response($status === 200 ? [
                'content' => [['type' => 'text', 'text' => json_encode($script)]],
                'usage'   => ['input_tokens' => 1200, 'output_tokens' => 600],
            ] : ['error' => ['message' => 'overloaded']], $status),
        ]);
    }

    public function test_creating_a_reel_writes_a_script_and_pending_targets(): void
    {
        $this->fakeClaude();
        $post = $this->article();

        $response = $this->actingAs($this->admin)->post(route('admin.social.reels.store'), ['blog_post_id' => $post->id]);

        $draft = SocialDraft::firstOrFail();
        $response->assertRedirect(route('admin.social.reels.show', $draft));

        $this->assertSame('draft', $draft->status);
        $this->assertCount(4, $draft->scenes());
        $this->assertEqualsCanonicalizing(['instagram', 'youtube'], $draft->targets->pluck('platform')->all());
        $this->assertTrue($draft->targets->every(fn ($t) => $t->status === 'pending'));

        // House style: no em dashes in anything spoken or shown.
        $this->assertStringNotContainsString('—', json_encode($draft->script, JSON_UNESCAPED_UNICODE));

        // Hashtags cleaned, #Shorts added, tracked article link appended.
        $this->assertSame(['Laravel', 'Redis', 'queues'], $draft->caption('instagram', 'hashtags'));
        $this->assertStringEndsWith('#Shorts', $draft->caption('youtube', 'title'));
        $this->assertStringContainsString('utm_source=youtube', $draft->caption('youtube', 'description'));
        $this->assertStringContainsString('utm_content=reel', $draft->caption('youtube', 'description'));

        // Asked Claude for schema-constrained JSON on the configured model.
        Http::assertSent(fn ($request) => $request['model'] === 'claude-sonnet-4-6'
            && $request['output_config']['format']['type'] === 'json_schema');
    }

    public function test_a_failed_ai_call_creates_nothing(): void
    {
        $this->fakeClaude(status: 529);
        $post = $this->article();

        $this->actingAs($this->admin)
            ->post(route('admin.social.reels.store'), ['blog_post_id' => $post->id])
            ->assertSessionHas('error');

        $this->assertSame(0, SocialDraft::count());
    }

    public function test_review_page_and_list_render(): void
    {
        $this->fakeClaude();
        $draft = app(ReelDraftService::class)->createForPost($this->article());

        $this->actingAs($this->admin)->get(route('admin.social.reels.show', $draft))
            ->assertOk()
            ->assertSee('Redis lost 3,000 jobs')
            ->assertSee('Approve per platform');

        $this->actingAs($this->admin)->get(route('admin.social.reels.index'))
            ->assertOk()
            ->assertSee('Needs review (1)');
    }

    public function test_editing_the_script_saves_and_resets_approvals(): void
    {
        $this->fakeClaude();
        $service = app(ReelDraftService::class);
        $draft = $service->createForPost($this->article());
        $service->markRendered($draft, 'reels/1.mp4', 'reels/1.jpg', 18);
        $service->approveTarget($draft->target('instagram'));

        $scenes = $draft->fresh()->scenes();
        $scenes[0]['on_screen_text'] = 'A new hook';

        $this->actingAs($this->admin)->put(route('admin.social.reels.update', $draft), [
            'scenes'              => $scenes,
            'instagram_caption'   => 'Edited caption',
            'instagram_hashtags'  => '#laravel #redis',
            'youtube_title'       => 'Edited title #Shorts',
            'youtube_description' => 'Edited description',
            'youtube_tags'        => 'laravel, redis',
        ])->assertSessionHas('success');

        $draft->refresh()->load('targets');
        $this->assertSame('A new hook', $draft->scenes()[0]['on_screen_text']);
        $this->assertSame('Edited caption', $draft->caption('instagram', 'caption'));
        $this->assertSame(['laravel', 'redis'], $draft->caption('instagram', 'hashtags'));
        // The video no longer matches the script: back to draft, approvals cleared.
        $this->assertSame('draft', $draft->status);
        $this->assertSame('pending', $draft->target('instagram')->status);
    }

    public function test_targets_cannot_be_approved_before_a_render(): void
    {
        $this->fakeClaude();
        $draft = app(ReelDraftService::class)->createForPost($this->article());
        $target = $draft->target('instagram');

        $this->actingAs($this->admin)
            ->patch(route('admin.social.reels.targets.approve', [$draft, $target]))
            ->assertSessionHas('error');

        $this->assertSame('pending', $target->fresh()->status);
    }

    public function test_a_finished_render_emails_the_owner_and_unlocks_approval(): void
    {
        $this->fakeClaude();
        $service = app(ReelDraftService::class);
        $draft = $service->createForPost($this->article());

        $service->markRendered($draft, 'reels/1.mp4', 'reels/1.jpg', 18);

        Mail::assertSent(ReelReadyForReview::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        $this->assertSame('ready', $draft->fresh()->status);

        $target = $draft->target('youtube');
        $when = now()->addDay()->format('Y-m-d\TH:i');
        $this->actingAs($this->admin)
            ->patch(route('admin.social.reels.targets.approve', [$draft, $target]), ['scheduled_for' => $when])
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('approved', $target->status);
        $this->assertSame($when, $target->scheduled_for->format('Y-m-d\TH:i'));
    }

    public function test_a_failed_render_emails_the_owner(): void
    {
        $this->fakeClaude();
        $service = app(ReelDraftService::class);
        $draft = $service->createForPost($this->article());

        $service->markRenderFailed($draft, 'Voice model timed out');

        Mail::assertSent(ReelRenderFailed::class, fn ($mail) => $mail->hasTo('owner@example.com'));
        $this->assertSame('render_failed', $draft->fresh()->status);
    }
}
