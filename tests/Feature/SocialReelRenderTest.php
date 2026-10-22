<?php

namespace Tests\Feature;

use App\Mail\ReelReadyForReview;
use App\Mail\ReelRenderFailed;
use App\Models\BlogPost;
use App\Models\Category;
use App\Models\SocialDraft;
use App\Models\SocialStudioSettings;
use App\Models\User;
use App\Services\Social\ReelDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Social Studio reels, step 2: the GitHub connection, starting a render, the
 * token-guarded API the render job calls back, and stuck-render timeouts.
 */
class SocialReelRenderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');
        config(['social.reels.alert_email' => 'owner@example.com']);

        $this->admin = User::factory()->create();
    }

    private function draft(string $status = SocialDraft::STATUS_RENDERING): SocialDraft
    {
        $category = Category::firstOrCreate(['slug' => 'web-development'], ['name' => 'Web Development']);
        $post = BlogPost::create([
            'title' => 'Why I moved my queue off Redis', 'slug' => 'why-redis-' . (BlogPost::count() + 1), 'excerpt' => 'x', 'content' => 'x',
            'status' => 'published', 'published_at' => now(), 'category_id' => $category->id,
            'user_id' => $this->admin->id, 'source_type' => 'original', 'featured_image' => 'blog/redis.png',
        ]);

        $service = app(ReelDraftService::class);
        $draft = SocialDraft::create([
            'blog_post_id' => $post->id,
            'status'       => $status,
            'render_requested_at' => now(),
            'script'       => ['scenes' => $service->normaliseScenes([
                ['type' => 'hook', 'on_screen_text' => 'Redis lost 3,000 jobs', 'voice_line' => 'Redis lost three thousand of my jobs.', 'visual' => 'portrait', 'visual_note' => ''],
                ['type' => 'end', 'on_screen_text' => 'Link in bio', 'voice_line' => 'Full story in the link in my bio.', 'visual' => 'portrait', 'visual_note' => ''],
            ])],
            'captions' => ['instagram' => ['caption' => 'c', 'hashtags' => []], 'youtube' => ['title' => 't #Shorts', 'description' => 'd', 'tags' => []]],
        ]);
        foreach (SocialDraft::REEL_PLATFORMS as $platform) {
            $draft->targets()->create(['platform' => $platform]);
        }

        return $draft;
    }

    /** A connected studio: voice uploaded, GitHub token stored, render token synced. */
    private function studio(): string
    {
        Storage::disk('local')->put('social/voice/sample.m4a', 'voice-bytes');
        $settings = SocialStudioSettings::instance();
        $settings->forceFill(['github_token' => 'github_pat_test', 'voice_sample_path' => 'social/voice/sample.m4a', 'voice_sample_name' => 'me.m4a'])->save();
        $token = $settings->rotateRenderToken();
        $settings->forceFill(['render_token_synced_at' => now()])->save();

        return $token;
    }

    // ── Render API ─────────────────────────────────────────

    public function test_render_api_rejects_missing_or_wrong_tokens(): void
    {
        $draft = $this->draft();

        // No token generated yet: the API stays closed.
        $this->getJson("/api/social/render/{$draft->id}")->assertStatus(503);

        $this->studio();
        $this->getJson("/api/social/render/{$draft->id}")->assertStatus(401);
        $this->getJson("/api/social/render/{$draft->id}", ['Authorization' => 'Bearer wrong'])->assertStatus(401);
    }

    public function test_render_job_gets_the_script_assets_and_voice_sample(): void
    {
        $token = $this->studio();
        $draft = $this->draft();
        $auth = ['Authorization' => "Bearer {$token}"];

        $this->getJson("/api/social/render/{$draft->id}", $auth)
            ->assertOk()
            ->assertJsonPath('scenes.0.on_screen_text', 'Redis lost 3,000 jobs')
            ->assertJsonPath('brand.handle', 'adilsher.pro')
            ->assertJsonPath('assets.voice_sample', route('api.social.render.voice-sample'))
            ->assertJson(fn ($json) => $json->where('assets.featured_image', fn ($url) => str_ends_with($url, '/storage/blog/redis.png'))->etc());

        $this->get(route('api.social.render.voice-sample'), $auth)->assertOk();
    }

    public function test_drafts_that_are_not_rendering_are_not_served(): void
    {
        $token = $this->studio();
        $draft = $this->draft(SocialDraft::STATUS_DRAFT);

        $this->getJson("/api/social/render/{$draft->id}", ['Authorization' => "Bearer {$token}"])->assertStatus(409);
    }

    public function test_chunked_upload_completes_the_render_and_emails_the_owner(): void
    {
        $token = $this->studio();
        $draft = $this->draft();
        $auth = ['Authorization' => "Bearer {$token}"];

        $mp4 = file_get_contents(base_path('tests/fixtures/tiny.mp4'));
        $parts = str_split($mp4, (int) ceil(strlen($mp4) / 3));

        $offset = 0;
        foreach ($parts as $i => $bytes) {
            $this->post("/api/social/render/{$draft->id}/chunk", [
                'index' => $i, 'offset' => $offset, 'chunk' => UploadedFile::fake()->createWithContent("part-{$i}", $bytes),
            ], $auth)->assertOk();

            // A retried chunk is accepted without being appended twice.
            if ($i === 1) {
                $this->post("/api/social/render/{$draft->id}/chunk", [
                    'index' => $i, 'offset' => $offset, 'chunk' => UploadedFile::fake()->createWithContent("part-{$i}", $bytes),
                ], $auth)->assertOk();
            }
            $offset += strlen($bytes);
        }

        $this->post("/api/social/render/{$draft->id}/complete", [
            'cover'            => UploadedFile::fake()->image('cover.jpg', 1080, 1920),
            'duration_seconds' => 9.4,
            'scene_seconds'    => [5.2, 4.2],
        ], $auth + ['Accept' => 'application/json'])->assertOk();

        $draft->refresh();
        $this->assertSame('ready', $draft->status);
        $this->assertSame(9, $draft->duration_seconds);
        $this->assertSame([5.2, 4.2], array_column($draft->scenes(), 'duration_seconds'));
        $this->assertSame($mp4, Storage::disk('public')->get($draft->video_path));
        Storage::disk('public')->assertExists($draft->cover_path);
        Mail::assertSent(ReelReadyForReview::class, fn ($mail) => $mail->hasTo('owner@example.com'));
    }

    public function test_the_voice_report_is_stored_and_shown_for_review(): void
    {
        $token = $this->studio();
        $draft = $this->draft();

        $report = [
            'voice' => ['exaggeration' => 0.5, 'cfg_weight' => 0.5, 'temperature' => 0.7],
            'reference' => ['window_start' => 34.0, 'total_seconds' => 60.0],
            'scenes' => [[
                'scene' => 1, 'script' => 'According to dev.to, agents got pricier.', 'spoken' => 'According to dev dot to, agents got pricier.',
                'heard' => 'According to deev to agents got pricier', 'match' => 0.8, 'kept_take' => 2, 'slowed_to' => null,
                'takes' => [['take' => 1], ['take' => 2]],
                'words' => [['text' => 'According', 'ok' => true], ['text' => 'to', 'ok' => true], ['text' => 'dev.to,', 'ok' => false],
                    ['text' => 'agents', 'ok' => true], ['text' => 'got', 'ok' => true], ['text' => 'pricier.', 'ok' => true]],
            ]],
        ];

        $this->post("/api/social/render/{$draft->id}/complete", [
            'video'            => UploadedFile::fake()->createWithContent('reel.mp4', file_get_contents(base_path('tests/fixtures/tiny.mp4'))),
            'cover'            => UploadedFile::fake()->image('cover.jpg'),
            'duration_seconds' => 9,
            'report'           => json_encode($report),
        ], ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'])->assertOk();

        $this->assertSame('deev', explode(' ', $draft->fresh()->render_report['scenes'][0]['heard'])[2]);

        $this->actingAs($this->admin)->get(route('admin.social.reels.show', $draft))
            ->assertOk()
            ->assertSee('Voice check')
            ->assertSee('1 word not heard as written')
            ->assertSee('According to deev to agents got pricier')
            ->assertSee('Spoken as: According to dev dot to, agents got pricier.');
    }

    public function test_out_of_order_chunks_are_refused(): void
    {
        $token = $this->studio();
        $draft = $this->draft();
        $auth = ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];

        $this->post("/api/social/render/{$draft->id}/chunk", ['index' => 0, 'offset' => 0, 'chunk' => UploadedFile::fake()->createWithContent('a', 'aaaa')], $auth)->assertOk();
        $this->post("/api/social/render/{$draft->id}/chunk", ['index' => 2, 'offset' => 50, 'chunk' => UploadedFile::fake()->createWithContent('c', 'cccc')], $auth)->assertStatus(409);
    }

    public function test_a_failed_render_is_reported_and_emailed(): void
    {
        $token = $this->studio();
        $draft = $this->draft();

        $this->postJson("/api/social/render/{$draft->id}/failed", ['error' => 'The render job failed.'], ['Authorization' => "Bearer {$token}"])
            ->assertOk();

        $this->assertSame('render_failed', $draft->fresh()->status);
        Mail::assertSent(ReelRenderFailed::class);
    }

    // ── Starting renders ───────────────────────────────────

    public function test_render_button_sends_you_to_setup_until_the_studio_is_ready(): void
    {
        $draft = $this->draft(SocialDraft::STATUS_DRAFT);

        $this->actingAs($this->admin)->post(route('admin.social.reels.render', $draft))
            ->assertRedirect(route('admin.social.reels.settings'));

        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_render_button_starts_the_github_workflow(): void
    {
        $this->studio();
        $draft = $this->draft(SocialDraft::STATUS_READY);
        app(ReelDraftService::class)->approveTarget($draft->target('instagram'));
        Http::fake(['api.github.com/*' => Http::response(null, 204)]);

        $this->actingAs($this->admin)->post(route('admin.social.reels.render', $draft))->assertSessionHas('success');

        $draft->refresh()->load('targets');
        $this->assertSame('rendering', $draft->status);
        // A new video needs a new review.
        $this->assertSame('pending', $draft->target('instagram')->status);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_ends_with($request->url(), '/repos/adisher/portfolio-laravel/actions/workflows/render-reel.yml/dispatches')
            && $request['ref'] === 'main'
            && $request['inputs']['draft_id'] === (string) $draft->id
            && $request->hasHeader('Authorization', 'Bearer github_pat_test'));
    }

    public function test_a_github_error_leaves_the_reel_unchanged(): void
    {
        $this->studio();
        $draft = $this->draft(SocialDraft::STATUS_DRAFT);
        Http::fake(['api.github.com/*' => Http::response(['message' => 'Bad credentials'], 401)]);

        $this->actingAs($this->admin)->post(route('admin.social.reels.render', $draft))
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'invalid or expired'));

        $this->assertSame('draft', $draft->fresh()->status);
    }

    public function test_a_stuck_render_can_be_stopped_from_the_admin(): void
    {
        $token = $this->studio();
        $draft = $this->draft();

        $this->actingAs($this->admin)->post(route('admin.social.reels.stop-render', $draft))->assertSessionHas('success');

        $this->assertSame('render_failed', $draft->fresh()->status);
        Mail::assertNothingSent();

        // A late upload from the abandoned job is refused.
        $this->postJson("/api/social/render/{$draft->id}/complete", [], ['Authorization' => "Bearer {$token}"])->assertStatus(409);
    }

    public function test_renders_that_never_report_back_time_out(): void
    {
        $draft = $this->draft();
        $draft->update(['render_requested_at' => now()->subHour()]);
        $fresh = $this->draft();

        $this->artisan('social:reels-timeout')->assertSuccessful();

        $this->assertSame('render_failed', $draft->fresh()->status);
        $this->assertSame('rendering', $fresh->fresh()->status);
        Mail::assertSent(ReelRenderFailed::class, 1);
    }

    // ── Studio settings ────────────────────────────────────

    public function test_voice_upload_is_stored_privately(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.social.reels.settings.voice'), ['voice' => UploadedFile::fake()->create('me.m4a', 900, 'audio/mp4')])
            ->assertSessionHas('success');

        $settings = SocialStudioSettings::instance();
        $this->assertTrue($settings->hasVoice());
        Storage::disk('local')->assertExists($settings->voice_sample_path);
        Storage::disk('public')->assertMissing($settings->voice_sample_path);
    }

    public function test_connecting_github_saves_an_encrypted_render_secret(): void
    {
        $keypair = sodium_crypto_box_keypair();
        $saved = null;

        Http::fake(function ($request) use ($keypair, &$saved) {
            if (str_ends_with($request->url(), '/actions/secrets/public-key')) {
                return Http::response(['key_id' => 'k1', 'key' => base64_encode(sodium_crypto_box_publickey($keypair))]);
            }
            if ($request->method() === 'PUT') {
                $saved = $request->data();
                return Http::response(null, 201);
            }
            return Http::response(['full_name' => 'adisher/portfolio-laravel']);
        });

        $this->actingAs($this->admin)
            ->post(route('admin.social.reels.settings.github'), ['github_token' => 'github_pat_' . str_repeat('x', 30)])
            ->assertSessionHas('success');

        $settings = SocialStudioSettings::instance();
        $this->assertTrue($settings->hasGithub());
        $this->assertSame('k1', $saved['key_id']);
        // GitHub receives the render token sealed to the repo's key, never in plain text.
        $this->assertSame($settings->render_token, sodium_crypto_box_seal_open(base64_decode($saved['encrypted_value']), $keypair));
        // And it is encrypted in the database too.
        $this->assertStringNotContainsString($settings->render_token, (string) \DB::table('social_studio_settings')->value('render_token'));
    }

    public function test_a_token_without_secrets_permission_is_explained(): void
    {
        Http::fake([
            'api.github.com/repos/adisher/portfolio-laravel' => Http::response(['full_name' => 'x']),
            'api.github.com/*' => Http::response(['message' => 'Resource not accessible'], 403),
        ]);

        $this->actingAs($this->admin)
            ->post(route('admin.social.reels.settings.github'), ['github_token' => 'github_pat_' . str_repeat('x', 30)])
            ->assertSessionHas('error', fn ($msg) => str_contains($msg, 'missing a permission'));

        $this->assertFalse(SocialStudioSettings::instance()->hasGithub());
    }

    public function test_voice_style_and_pronunciations_reach_the_render_job(): void
    {
        $token = $this->studio();
        $draft = $this->draft();

        $this->actingAs($this->admin)->put(route('admin.social.reels.settings.voice-options'), [
            'voice_style'    => 'expressive',
            'pronunciations' => "Laravel = Lara vel\nnot a rule\nkubectl: cube control",
        ])->assertSessionHas('success');

        $this->getJson("/api/social/render/{$draft->id}", ['Authorization' => "Bearer {$token}"])
            ->assertOk()
            ->assertJsonPath('voice.exaggeration', 0.6)
            ->assertJsonPath('voice.cfg_weight', 0.42)
            ->assertJsonPath('voice.seed', $draft->id)
            ->assertJsonPath('voice.pronunciations', [['Laravel', 'Lara vel'], ['kubectl', 'cube control']]);
    }

    public function test_default_pronunciations_cover_the_site_name(): void
    {
        $list = SocialStudioSettings::instance()->pronunciationList();

        $this->assertContains(['adilsher.pro', 'Adil Sher dot pro'], $list);
        $this->assertSame('natural', SocialStudioSettings::instance()->fresh()->voice_style);
    }

    public function test_settings_page_renders(): void
    {
        $this->actingAs($this->admin)->get(route('admin.social.reels.settings'))
            ->assertOk()
            ->assertSee('Your voice')
            ->assertSee('Connect GitHub');
    }
}
