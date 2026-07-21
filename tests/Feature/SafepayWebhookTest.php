<?php

namespace Tests\Feature;

use App\Mail\OrderConfirmation;
use App\Models\Category;
use App\Models\Order;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Covers the Safepay webhook — the endpoint that decides whether a customer's
 * payment is recognised. A silent failure here means money taken and no order
 * marked paid, so signature rejection and idempotency are the load-bearing cases.
 */
class SafepayWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();

        config([
            'services.safepay.webhook_secret' => self::SECRET,
            'services.safepay.api_key'        => 'test-key',
            'services.safepay.api_secret'     => 'test-secret',
            'services.safepay.base_url'       => 'https://sandbox.api.getsafepay.com',
        ]);
    }

    private function makeOrder(array $attributes = []): Order
    {
        $category = Category::create([
            'name' => 'Products',
            'slug' => 'products-' . uniqid(),
        ]);

        $project = Project::create([
            'title'             => 'Test Product',
            'slug'              => 'test-product-' . uniqid(),
            'short_description' => 'Short',
            'description'       => 'Long',
            'featured_image'    => 'img.png',
            'project_date'      => now()->toDateString(),
            'category_id'       => $category->id,
        ]);

        return Order::create(array_merge([
            'project_id'      => $project->id,
            'customer_email'  => 'buyer@example.com',
            'customer_name'   => 'Test Buyer',
            'tier_name'       => 'Pro',
            'amount'          => 4999.00,
            'currency'        => 'PKR',
            'safepay_tracker' => 'track_' . uniqid(),
            'status'          => 'pending',
        ], $attributes));
    }

    /** Post a payload with a correctly computed HMAC signature. */
    private function postSigned(array $payload)
    {
        $body = json_encode($payload);

        return $this->call(
            'POST',
            '/webhook/safepay',
            [],
            [],
            [],
            [
                'CONTENT_TYPE'         => 'application/json',
                'HTTP_X_SFPY_SIGNATURE' => hash_hmac('sha256', $body, self::SECRET),
            ],
            $body
        );
    }

    private function paymentPayload(string $tracker, string $type = 'payment:complete'): array
    {
        return [
            'type' => $type,
            'data' => [
                'tracker'   => ['token' => $tracker],
                'reference' => 'ref_12345',
            ],
        ];
    }

    public function test_rejects_payload_with_invalid_signature(): void
    {
        $order = $this->makeOrder();

        $response = $this->postJson(
            '/webhook/safepay',
            $this->paymentPayload($order->safepay_tracker),
            ['X-SFPY-SIGNATURE' => 'not-the-right-signature']
        );

        $response->assertStatus(403);
        $this->assertSame('pending', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_rejects_payload_with_missing_signature(): void
    {
        $order = $this->makeOrder();

        $response = $this->postJson(
            '/webhook/safepay',
            $this->paymentPayload($order->safepay_tracker)
        );

        $response->assertStatus(403);
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_marks_order_paid_and_sends_confirmation(): void
    {
        $order = $this->makeOrder();

        $this->postSigned($this->paymentPayload($order->safepay_tracker))
            ->assertOk()
            ->assertJson(['status' => 'ok']);

        $order->refresh();

        $this->assertSame('paid', $order->status);
        $this->assertSame('ref_12345', $order->safepay_reference);
        $this->assertNotNull($order->paid_at);
        $this->assertTrue($order->metadata['email_sent'] ?? false);

        Mail::assertSent(OrderConfirmation::class, fn ($mail) => $mail->hasTo('buyer@example.com'));
    }

    public function test_accepts_each_payment_success_event_alias(): void
    {
        foreach (['payment:created', 'payment.succeeded', 'payment:complete'] as $eventType) {
            $order = $this->makeOrder();

            $this->postSigned($this->paymentPayload($order->safepay_tracker, $eventType))->assertOk();

            $this->assertSame('paid', $order->fresh()->status, "Event {$eventType} should mark the order paid");
        }
    }

    public function test_does_not_reprocess_an_order_already_paid(): void
    {
        $order = $this->makeOrder([
            'status'   => 'paid',
            'paid_at'  => now(),
            'metadata' => ['email_sent' => true],
        ]);

        $this->postSigned($this->paymentPayload($order->safepay_tracker))
            ->assertOk()
            ->assertJson(['message' => 'Already processed']);

        Mail::assertNothingSent();

        $events = array_column($order->fresh()->getTimeline(), 'event');
        $this->assertContains('webhook_skipped', $events);
    }

    public function test_marks_order_failed(): void
    {
        $order = $this->makeOrder();

        $this->postSigned($this->paymentPayload($order->safepay_tracker, 'payment.failed'))->assertOk();

        $this->assertSame('failed', $order->fresh()->status);
        Mail::assertNothingSent();
    }

    public function test_marks_order_cancelled(): void
    {
        $order = $this->makeOrder();

        $this->postSigned($this->paymentPayload($order->safepay_tracker, 'payment.cancelled'))->assertOk();

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    public function test_unrecognised_event_leaves_status_untouched_but_is_logged(): void
    {
        $order = $this->makeOrder();

        $this->postSigned($this->paymentPayload($order->safepay_tracker, 'payment:refund_pending'))->assertOk();

        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertContains('webhook_unhandled', array_column($order->getTimeline(), 'event'));
    }

    public function test_unknown_tracker_returns_ok_without_error(): void
    {
        $this->postSigned($this->paymentPayload('track_does_not_exist'))
            ->assertOk()
            ->assertJson(['message' => 'Order not found']);
    }

    public function test_payload_without_tracker_returns_ok(): void
    {
        $this->postSigned(['type' => 'payment:complete', 'data' => []])
            ->assertOk()
            ->assertJson(['message' => 'No tracker found']);
    }

    public function test_records_an_audit_trail_entry_for_every_webhook(): void
    {
        $order = $this->makeOrder();

        $this->postSigned($this->paymentPayload($order->safepay_tracker))->assertOk();

        $events = array_column($order->fresh()->getTimeline(), 'event');

        $this->assertContains('webhook_received', $events);
        $this->assertContains('payment_confirmed_via_webhook', $events);
        $this->assertContains('email_dispatched', $events);
    }

    /**
     * Documents current behaviour: with no webhook secret configured the
     * signature check is bypassed entirely. Intentional for local/sandbox work,
     * but it means an unset SAFEPAY_WEBHOOK_SECRET in production accepts
     * unsigned webhooks from anyone.
     */
    public function test_signature_check_is_skipped_when_no_secret_is_configured(): void
    {
        config(['services.safepay.webhook_secret' => null]);

        $order = $this->makeOrder();

        $this->postJson(
            '/webhook/safepay',
            $this->paymentPayload($order->safepay_tracker),
            ['X-SFPY-SIGNATURE' => 'anything-at-all']
        )->assertOk();

        $this->assertSame('paid', $order->fresh()->status);
    }
}
