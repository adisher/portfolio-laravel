<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Services\SafepayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SafepayWebhookController extends Controller
{
    protected SafepayService $safepay;

    public function __construct(SafepayService $safepay)
    {
        $this->safepay = $safepay;
    }

    /**
     * Handle incoming Safepay webhook events.
     *
     * POST /webhook/safepay
     */
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('X-SFPY-SIGNATURE', '');

        // Verify webhook signature
        if (!$this->safepay->verifyWebhookSignature($payload, $signature)) {
            Log::warning('Safepay webhook: Invalid signature', [
                'signature' => $signature,
            ]);
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        $data = $request->all();
        $eventType = $data['type'] ?? $data['event'] ?? 'unknown';
        $tracker = $data['data']['tracker']['token']
            ?? $data['data']['token']
            ?? $data['tracker']
            ?? null;

        Log::info('Safepay webhook received', [
            'event'   => $eventType,
            'tracker' => $tracker,
        ]);

        if (!$tracker) {
            Log::warning('Safepay webhook: No tracker in payload');
            return response()->json(['status' => 'ok', 'message' => 'No tracker found']);
        }

        $order = Order::where('safepay_tracker', $tracker)->first();

        if (!$order) {
            Log::warning('Safepay webhook: Order not found for tracker', ['tracker' => $tracker]);
            return response()->json(['status' => 'ok', 'message' => 'Order not found']);
        }

        $order->logEvent('webhook_received', [
            'event_type' => $eventType,
            'tracker'    => $tracker,
            'ip_address' => $request->ip(),
        ]);

        // Skip if already in a terminal state (paid, failed, cancelled)
        if ($order->isTerminal()) {
            $order->logEvent('webhook_skipped', [
                'reason'       => "Order already in terminal state: {$order->status}",
                'event_type'   => $eventType,
            ]);
            return response()->json(['status' => 'ok', 'message' => 'Already processed']);
        }

        switch ($eventType) {
            case 'payment:created':
            case 'payment.succeeded':
            case 'payment:complete':
                $reference = $data['data']['reference'] ?? $data['data']['ref'] ?? null;
                $order->markAsPaid($reference);

                // Store webhook data in metadata
                $order->update([
                    'metadata' => array_merge($order->metadata ?? [], [
                        'webhook_event' => $data,
                    ]),
                ]);

                $order->logEvent('payment_confirmed_via_webhook', [
                    'event_type' => $eventType,
                    'reference'  => $reference,
                ]);

                // Send confirmation email (only if not already sent via callback)
                $order->refresh();
                if ($order->customer_email && !($order->metadata['email_sent'] ?? false)) {
                    $mailDriver = config('mail.default', 'log');
                    try {
                        Mail::to($order->customer_email)->send(new OrderConfirmation($order));
                        $order->update([
                            'metadata' => array_merge($order->metadata ?? [], ['email_sent' => true]),
                        ]);
                        $order->logEvent('email_dispatched', [
                            'to'     => $order->customer_email,
                            'driver' => $mailDriver,
                            'source' => 'webhook',
                        ]);
                    } catch (\Exception $e) {
                        Log::error('Safepay webhook: Email send failed', [
                            'order_id' => $order->id,
                            'error'    => $e->getMessage(),
                        ]);
                        $order->logEvent('email_failed', [
                            'to'     => $order->customer_email,
                            'error'  => $e->getMessage(),
                            'source' => 'webhook',
                        ]);
                    }
                }

                Log::info('Safepay webhook: Order marked as paid', ['order_id' => $order->id]);
                break;

            case 'payment.failed':
            case 'payment:failed':
                $order->logEvent('payment_failed_via_webhook', [
                    'event_type' => $eventType,
                    'data'       => $data['data'] ?? [],
                ]);
                $order->markAsFailed();
                Log::info('Safepay webhook: Order marked as failed', ['order_id' => $order->id]);
                break;

            case 'payment.cancelled':
            case 'payment:cancelled':
                $order->logEvent('payment_cancelled_via_webhook', [
                    'event_type' => $eventType,
                ]);
                $order->markAsCancelled();
                Log::info('Safepay webhook: Order marked as cancelled', ['order_id' => $order->id]);
                break;

            default:
                $order->logEvent('webhook_unhandled', [
                    'event_type' => $eventType,
                    'message'    => 'Unrecognized webhook event type',
                ]);
                Log::info('Safepay webhook: Unhandled event type', ['type' => $eventType]);
        }

        return response()->json(['status' => 'ok']);
    }
}
