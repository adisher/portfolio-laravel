<?php

namespace Tests\Feature;

use App\Mail\AiBudgetExhaustedMail;
use App\Mail\AiBudgetWarningMail;
use App\Models\AiBudgetSetting;
use App\Models\AiUsageLog;
use App\Services\AiBudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Covers the AI spend cap — the only thing standing between an automated
 * content pipeline and an unbounded Anthropic bill. The cases that matter are
 * the ones that stop calls: pause, exhaustion, and the alert ladder.
 */
class AiBudgetTest extends TestCase
{
    use RefreshDatabase;

    private AiBudgetService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        $this->service = app(AiBudgetService::class);
    }

    private function settings(array $attributes = []): AiBudgetSetting
    {
        $settings = AiBudgetSetting::getInstance();
        $settings->update(array_merge([
            'monthly_budget_usd'      => 1.00,
            'current_month_usage_usd' => 0.00,
            'additional_budget_usd'   => 0.00,
            'is_paused'               => false,
            'alert_email'             => 'alerts@example.com',
        ], $attributes));

        return $settings->fresh();
    }

    // ── Pricing ────────────────────────────────────────────

    public function test_resolves_price_by_model_tier(): void
    {
        $this->assertSame(['input' => 1.00, 'output' => 5.00], $this->service->priceFor('claude-haiku-4-5-20251001'));
        $this->assertSame(['input' => 3.00, 'output' => 15.00], $this->service->priceFor('claude-sonnet-4-6'));
        $this->assertSame(['input' => 5.00, 'output' => 25.00], $this->service->priceFor('claude-opus-4-8'));
    }

    public function test_falls_back_to_default_pricing_for_an_unknown_model(): void
    {
        $this->assertSame(
            ['input' => 1.00, 'output' => 5.00],
            $this->service->priceFor('some-model-we-have-never-seen')
        );
    }

    public function test_falls_back_to_default_pricing_for_a_null_model(): void
    {
        $this->assertSame(['input' => 1.00, 'output' => 5.00], $this->service->priceFor(null));
    }

    public function test_calculates_cost_per_million_tokens(): void
    {
        // 1M input + 1M output on the sonnet tier = $3 + $15
        $this->assertEqualsWithDelta(18.00, $this->service->calculateCost(1_000_000, 1_000_000, 'claude-sonnet-4-6'), 0.0001);

        // 10k input + 2k output on haiku = (10000 * 1 / 1M) + (2000 * 5 / 1M)
        $this->assertEqualsWithDelta(0.02, $this->service->calculateCost(10_000, 2_000, 'claude-haiku-4-5'), 0.0001);
    }

    public function test_zero_tokens_cost_nothing(): void
    {
        $this->assertSame(0.0, $this->service->calculateCost(0, 0, 'claude-haiku-4-5'));
    }

    // ── Gating ─────────────────────────────────────────────

    public function test_allows_api_calls_when_budget_remains(): void
    {
        $this->settings(['current_month_usage_usd' => 0.10]);

        $this->assertTrue($this->service->canMakeApiCall());
    }

    public function test_blocks_api_calls_when_paused(): void
    {
        $this->settings(['is_paused' => true]);

        $this->assertFalse($this->service->canMakeApiCall());
    }

    public function test_blocks_api_calls_when_budget_is_exhausted(): void
    {
        $this->settings(['current_month_usage_usd' => 1.00]);

        $this->assertFalse($this->service->canMakeApiCall());
    }

    public function test_top_up_reopens_an_exhausted_budget(): void
    {
        $this->settings(['current_month_usage_usd' => 1.00, 'is_paused' => true]);
        $this->assertFalse($this->service->canMakeApiCall());

        $this->service->addBudget(2.00);

        $this->assertTrue($this->service->canMakeApiCall());
    }

    // ── Usage accounting ───────────────────────────────────

    public function test_logs_usage_and_increments_spend(): void
    {
        $this->settings();

        $log = $this->service->logUsage('content_transform', 10_000, 2_000, model: 'claude-haiku-4-5');

        $this->assertInstanceOf(AiUsageLog::class, $log);
        $this->assertDatabaseCount('ai_usage_logs', 1);
        $this->assertEqualsWithDelta(0.02, (float) $log->cost_usd, 0.0001);
        $this->assertEqualsWithDelta(0.02, (float) $this->service->getSettings()->current_month_usage_usd, 0.0001);
    }

    public function test_failed_calls_are_logged_but_do_not_consume_budget(): void
    {
        $this->settings();

        $this->service->logUsage('content_transform', 10_000, 2_000, success: false, model: 'claude-haiku-4-5');

        $this->assertDatabaseCount('ai_usage_logs', 1);
        $this->assertEqualsWithDelta(0.00, (float) $this->service->getSettings()->current_month_usage_usd, 0.0001);
    }

    public function test_extra_cost_is_added_on_top_of_token_cost(): void
    {
        $this->settings();

        // e.g. a web-search server-tool charge riding along with the token spend
        $log = $this->service->logUsage('voice_search', 0, 0, model: 'claude-haiku-4-5', extraCostUsd: 0.05);

        $this->assertEqualsWithDelta(0.05, (float) $log->cost_usd, 0.0001);
    }

    public function test_reset_clears_usage_top_ups_and_pause_state(): void
    {
        $this->settings([
            'current_month_usage_usd' => 0.95,
            'additional_budget_usd'   => 3.00,
            'is_paused'               => true,
            'last_100_alert_sent_at'  => now()->subDays(2),
        ]);

        $this->service->resetMonthlyUsage();
        $settings = $this->service->getSettings();

        $this->assertEqualsWithDelta(0.00, (float) $settings->current_month_usage_usd, 0.0001);
        $this->assertEqualsWithDelta(0.00, (float) $settings->additional_budget_usd, 0.0001);
        $this->assertFalse($settings->is_paused);
        $this->assertNull($settings->last_100_alert_sent_at);
    }

    // ── Alert ladder ───────────────────────────────────────

    public function test_sends_warning_at_fifty_percent(): void
    {
        $settings = $this->settings(['current_month_usage_usd' => 0.50]);

        $this->service->checkBudgetAlerts($settings);

        Mail::assertSent(AiBudgetWarningMail::class);
        $this->assertNotNull($settings->fresh()->last_50_alert_sent_at);
    }

    public function test_sends_warning_at_eighty_percent(): void
    {
        $settings = $this->settings(['current_month_usage_usd' => 0.80]);

        $this->service->checkBudgetAlerts($settings);

        Mail::assertSent(AiBudgetWarningMail::class);
        $this->assertNotNull($settings->fresh()->last_80_alert_sent_at);
    }

    public function test_exhaustion_pauses_processing_and_alerts(): void
    {
        $settings = $this->settings(['current_month_usage_usd' => 1.00]);

        $this->service->checkBudgetAlerts($settings);
        $settings = $settings->fresh();

        Mail::assertSent(AiBudgetExhaustedMail::class);
        $this->assertTrue($settings->is_paused);
        $this->assertNotNull($settings->last_100_alert_sent_at);
        $this->assertFalse($this->service->canMakeApiCall());
    }

    public function test_does_not_resend_the_same_alert_within_24_hours(): void
    {
        $settings = $this->settings([
            'current_month_usage_usd' => 1.00,
            'last_100_alert_sent_at'  => now()->subHours(2),
        ]);

        $this->service->checkBudgetAlerts($settings);

        Mail::assertNothingSent();
    }

    public function test_resends_the_alert_after_24_hours(): void
    {
        $settings = $this->settings([
            'current_month_usage_usd' => 1.00,
            'last_100_alert_sent_at'  => now()->subHours(30),
        ]);

        $this->service->checkBudgetAlerts($settings);

        Mail::assertSent(AiBudgetExhaustedMail::class);
    }

    public function test_no_alert_is_sent_below_fifty_percent(): void
    {
        $settings = $this->settings(['current_month_usage_usd' => 0.20]);

        $this->service->checkBudgetAlerts($settings);

        Mail::assertNothingSent();
    }

    /**
     * Regression: `last_100_alert_sent_at` was missing from the model's $casts,
     * so it came back from the database as a plain string and the 24-hour
     * throttle check called ->diffInHours() on it. That raises a PHP Error,
     * which the surrounding `catch (\Exception)` does not catch — so logging a
     * single unit of usage would fatal.
     *
     * Reachable in production by hitting Resume on an exhausted budget without
     * adding funds: the pause lifts, usage is still >= 100%, and the next call
     * re-enters this branch with a non-null timestamp.
     */
    public function test_logging_usage_after_a_prior_exhaustion_alert_does_not_fatal(): void
    {
        $settings = $this->settings([
            'current_month_usage_usd' => 1.00,
            'last_100_alert_sent_at'  => now()->subHours(2),
        ]);

        $this->assertInstanceOf(
            \Illuminate\Support\Carbon::class,
            $settings->last_100_alert_sent_at,
            'last_100_alert_sent_at must be cast to a date instance'
        );

        // Simulates an admin resuming without topping up.
        $settings->update(['is_paused' => false]);

        $log = $this->service->logUsage('content_transform', 1_000, 100, model: 'claude-haiku-4-5');

        $this->assertInstanceOf(AiUsageLog::class, $log);

        // Note: is_paused is NOT re-set here — re-pausing is coupled to sending
        // the alert, which is throttled to once per 24h. Spend is still blocked,
        // because the gate checks remaining budget independently of the flag.
        $this->assertFalse($this->service->canMakeApiCall(), 'Exhausted budget must still block calls');
    }
}
