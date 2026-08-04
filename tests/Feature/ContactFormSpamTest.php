<?php

namespace Tests\Feature;

use App\Mail\ContactFormSubmitted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Contact-form spam defences. The two failure directions that matter: a real
 * submission must still save + email, and a bot submission must be silently
 * dropped (saving nothing, sending nothing) while returning the same success
 * response so the bot doesn't learn it was blocked.
 */
class ContactFormSpamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        // Isolate the honeypot/timing logic from the per-IP rate limiter so
        // repeated posts across test methods don't trip it.
        $this->withoutMiddleware(ThrottleRequests::class);
    }

    /** A token that looks like the form was rendered N seconds ago. */
    private function tokenSecondsAgo(int $seconds): string
    {
        return Crypt::encrypt(now()->subSeconds($seconds)->timestamp);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name'           => 'Jane Real',
            'email'          => 'jane@example.com',
            'subject'        => 'Project inquiry',
            'message'        => 'I would like to discuss a project with you.',
            'website'        => '',                       // honeypot empty = human
            'form_loaded_at' => $this->tokenSecondsAgo(8), // filled it in 8s
        ], $overrides);
    }

    public function test_legitimate_submission_is_saved_and_emailed(): void
    {
        $this->post('/contact', $this->payload())
            ->assertSessionHas('success');

        $this->assertDatabaseHas('contacts', ['email' => 'jane@example.com']);
        Mail::assertSent(ContactFormSubmitted::class);
    }

    public function test_honeypot_filled_is_silently_dropped(): void
    {
        // Bot filled the hidden "website" field.
        $this->post('/contact', $this->payload(['website' => 'http://spam.example']))
            ->assertSessionHas('success'); // same response a human sees

        $this->assertDatabaseCount('contacts', 0);
        Mail::assertNothingSent();
    }

    public function test_submission_faster_than_two_seconds_is_dropped(): void
    {
        $this->post('/contact', $this->payload(['form_loaded_at' => $this->tokenSecondsAgo(0)]))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('contacts', 0);
        Mail::assertNothingSent();
    }

    public function test_missing_timing_token_is_dropped(): void
    {
        $payload = $this->payload();
        unset($payload['form_loaded_at']);

        $this->post('/contact', $payload)->assertSessionHas('success');

        $this->assertDatabaseCount('contacts', 0);
        Mail::assertNothingSent();
    }

    public function test_tampered_timing_token_is_dropped(): void
    {
        // A bot that fakes the token can't produce valid ciphertext.
        $this->post('/contact', $this->payload(['form_loaded_at' => 'not-a-valid-token']))
            ->assertSessionHas('success');

        $this->assertDatabaseCount('contacts', 0);
        Mail::assertNothingSent();
    }

    public function test_real_submission_still_validates_required_fields(): void
    {
        // Traps pass, but empty required fields must still error (not silently drop).
        $this->post('/contact', $this->payload(['name' => '', 'email' => '', 'message' => '']))
            ->assertSessionHasErrors(['name', 'email', 'message']);

        $this->assertDatabaseCount('contacts', 0);
    }
}
