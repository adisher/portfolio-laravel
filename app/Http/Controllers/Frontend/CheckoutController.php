<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Mail\OrderConfirmation;
use App\Models\Order;
use App\Models\Project;
use App\Services\SafepayService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    protected SafepayService $safepay;

    public function __construct(SafepayService $safepay)
    {
        $this->safepay = $safepay;
    }

    /**
     * Initiate checkout: create Safepay session and redirect to processing page.
     *
     * POST /checkout/{productSlug}/{tierIndex}
     */
    public function initiate(Request $request, string $productSlug, int $tierIndex)
    {
        $product = Project::published()
            ->ownProducts()
            ->where('slug', $productSlug)
            ->firstOrFail();

        $request->validate([
            'customer_email' => 'required|email|max:255',
        ]);

        $pricing = $product->product_pricing;

        if (!$pricing || !isset($pricing[$tierIndex])) {
            return back()->with('error', 'Invalid pricing tier selected.');
        }

        $tier = $pricing[$tierIndex];
        $amount = (float) $tier['price'];
        $currency = 'PKR';

        try {
            // 1. Create payment session on Safepay (v3)
            $payment = $this->safepay->createPayment($amount, $currency);

            // 2. Create order record
            $order = Order::create([
                'project_id'      => $product->id,
                'customer_email'  => $request->input('customer_email'),
                'tier_name'       => $tier['name'],
                'amount'          => $amount,
                'currency'        => $currency,
                'safepay_tracker' => $payment['tracker'],
                'status'          => 'pending',
                'metadata'        => [
                    'tier_index'    => $tierIndex,
                    'tier_features' => $tier['features'] ?? [],
                    'product_slug'  => $productSlug,
                    'ip_address'    => $request->ip(),
                ],
            ]);

            $order->logEvent('order_created', [
                'product'  => $product->title,
                'tier'     => $tier['name'],
                'amount'   => $amount,
                'currency' => $currency,
                'email'    => $request->input('customer_email'),
            ]);

            $order->logEvent('payment_session_created', [
                'tracker' => $payment['tracker'],
                'gateway' => 'safepay',
                'mode'    => 'sandbox',
            ]);

            // 3. Get auth token (tbt) for checkout redirect
            $tbt = $this->safepay->getAuthToken();

            // 4. Build checkout URL — iframe callback URLs include ?iframe=1
            $checkoutUrl = $this->safepay->getCheckoutUrl(
                tracker: $payment['tracker'],
                tbt: $tbt,
                cancelUrl: route('checkout.cancel', ['order' => $order->order_token, 'iframe' => 1]),
                successUrl: route('checkout.callback', ['order' => $order->order_token, 'iframe' => 1]),
            );

            // 5. Store checkout URL in metadata
            $order->update([
                'metadata' => array_merge($order->metadata ?? [], [
                    'checkout_url' => $checkoutUrl,
                ]),
            ]);

            $order->logEvent('checkout_ready', [
                'message' => 'Buyer redirected to Safepay checkout',
            ]);

            // 6. Redirect to processing page (POST → Redirect → GET)
            return redirect()->route('checkout.processing', $order->order_token);

        } catch (\Exception $e) {
            Log::error('Checkout initiation failed', [
                'product' => $productSlug,
                'tier'    => $tierIndex,
                'error'   => $e->getMessage(),
            ]);

            return back()->with('error', 'Unable to initiate checkout. Please try again later.');
        }
    }

    /**
     * Show the checkout processing page with Safepay embedded in an iframe.
     *
     * GET /checkout/processing/{orderToken}
     */
    public function processing(string $orderToken)
    {
        $order = Order::where('order_token', $orderToken)->with('project')->firstOrFail();

        if ($order->isPaid()) {
            return redirect()->route('checkout.success', $order->order_token);
        }

        if (in_array($order->status, ['cancelled', 'failed'])) {
            return redirect()->route('checkout.cancel', ['order' => $order->order_token]);
        }

        $checkoutUrl = $order->metadata['checkout_url'] ?? null;

        if (!$checkoutUrl) {
            return redirect()->route('products.show', $order->project->slug)
                ->with('error', 'Checkout session has expired. Please try again.');
        }

        $product = $order->project;

        return view('frontend.checkout-processing', compact('order', 'product', 'checkoutUrl'));
    }

    /**
     * Return order status as JSON (polled by the processing page).
     *
     * When the order is still pending and has been for more than 30 seconds,
     * actively calls Safepay's API to check if payment was completed. This
     * solves the auto-redirect problem when Safepay's iframe callback doesn't
     * fire (e.g. frame-busting or postMessage format mismatch).
     *
     * Verification is rate-limited to once every 10 seconds via a metadata flag.
     *
     * GET /checkout/status/{orderToken}
     */
    public function status(string $orderToken)
    {
        $order = Order::where('order_token', $orderToken)->first();

        if (!$order) {
            return response()->json(['status' => 'not_found'], 404);
        }

        // If order is still pending, try active verification with Safepay
        if ($order->status === 'pending') {
            $this->tryActiveVerification($order);
            $order->refresh();
        }

        return response()->json([
            'status' => $order->status,
            'paid'   => $order->isPaid(),
        ]);
    }

    /**
     * Actively verify a pending order with Safepay's API.
     *
     * Conditions:
     * - Order must be pending for at least 30 seconds
     * - Last verification attempt must be at least 10 seconds ago (rate limiting)
     *
     * If Safepay confirms payment, marks order as paid and sends confirmation email.
     */
    private function tryActiveVerification(Order $order): void
    {
        // Only verify if order has been pending for >15 seconds
        // (gives buyer enough time to enter card details before first API check)
        $secondsPending = (int) abs($order->created_at->diffInSeconds(now()));
        if ($secondsPending < 15) {
            return;
        }

        // Rate-limit: max one verification every 8 seconds
        // IMPORTANT: Use abs() because Carbon 3 returns signed values from diffInSeconds().
        // Without abs(), "now()->diffInSeconds(pastDate)" returns NEGATIVE, making
        // the condition "< 8" always true, permanently blocking all subsequent checks.
        $lastCheck = $order->metadata['last_active_verify'] ?? null;
        if ($lastCheck) {
            $secondsSinceLastCheck = (int) abs(now()->diffInSeconds(Carbon::parse($lastCheck)));
            if ($secondsSinceLastCheck < 8) {
                return;
            }
        }

        // Update rate-limit timestamp
        $order->update([
            'metadata' => array_merge($order->metadata ?? [], [
                'last_active_verify' => now()->toIso8601String(),
            ]),
        ]);

        try {
            $paymentData = $this->safepay->verifyPayment($order->safepay_tracker);

            $state = strtoupper($paymentData['state'] ?? $paymentData['status'] ?? 'UNKNOWN');

            if (in_array($state, ['PAID', 'DELIVERED', 'TRACKER_ENDED'])) {
                $order->logEvent('active_poll_verified', [
                    'state'   => $state,
                    'message' => 'Payment confirmed via active polling (Safepay API check)',
                ]);

                // Extract payment details
                $charge = $paymentData['charge'] ?? [];
                $customer = $paymentData['customer'] ?? [];
                $paymentMethod = $paymentData['attempts'][0]['payment_method'] ?? [];

                $order->logEvent('payment_verified', [
                    'state'          => $state,
                    'source'         => 'active_poll',
                    'charge_token'   => $charge['token'] ?? null,
                    'charge_amount'  => $charge['amount'] ?? null,
                    'charge_fees'    => $charge['fees'] ?? null,
                    'charge_net'     => $charge['net'] ?? null,
                    'customer_name'  => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    'customer_email' => $customer['email'] ?? null,
                    'card_last_four' => $paymentMethod['last_four'] ?? null,
                    'card_scheme'    => $paymentMethod['scheme'] ?? null,
                    'card_issuer'    => $paymentMethod['issuer'] ?? null,
                ]);

                $reference = $charge['signature'] ?? $paymentData['reference'] ?? null;
                $order->markAsPaid($reference);

                // Store full Safepay response
                $order->update([
                    'metadata' => array_merge($order->metadata ?? [], [
                        'safepay_response' => $paymentData,
                    ]),
                ]);

                // Update customer name from Safepay
                if (empty($order->customer_name) && !empty($customer['first_name'])) {
                    $order->update([
                        'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    ]);
                }

                // Send confirmation email
                $this->sendConfirmationEmail($order);

                Log::info('Active polling: Payment verified and marked as paid', [
                    'order_token' => $order->order_token,
                    'tracker'     => $order->safepay_tracker,
                    'state'       => $state,
                ]);
            }
        } catch (\Exception $e) {
            // Don't fail the status check — just log and let it retry on next poll
            Log::debug('Active polling: Safepay verification attempt failed (will retry)', [
                'order_token' => $order->order_token,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle callback from Safepay after payment attempt.
     *
     * GET /checkout/callback
     *
     * When loaded inside the checkout iframe (?iframe=1), returns JS to
     * redirect the parent window. Otherwise does a normal redirect.
     */
    public function callback(Request $request)
    {
        $orderToken = $request->query('order');
        $tracker    = $request->query('tracker');
        $ref        = $request->query('ref');
        $isIframe   = $request->boolean('iframe');

        $order = Order::where('order_token', $orderToken)->first();

        if (!$order) {
            Log::warning('Checkout callback: Order not found', ['order_token' => $orderToken]);
            return $this->respond($isIframe, route('home'));
        }

        $order->logEvent('callback_received', [
            'source'       => $isIframe ? 'iframe' : 'direct',
            'tracker_url'  => $tracker,
            'ref_url'      => $ref,
            'ip_address'   => $request->ip(),
            'user_agent'   => $request->userAgent(),
        ]);

        // Skip if already paid (webhook or active polling may have processed first)
        if ($order->isPaid()) {
            $order->logEvent('callback_skipped', [
                'reason' => 'Order already marked as paid (likely processed by webhook or active polling)',
            ]);
            return $this->respond($isIframe, route('checkout.success', $order->order_token));
        }

        try {
            // Verify payment with Safepay
            $order->logEvent('payment_verification_started', [
                'tracker' => $order->safepay_tracker,
                'method'  => 'reporter_api',
            ]);

            $paymentData = $this->safepay->verifyPayment($order->safepay_tracker);

            $state = strtoupper($paymentData['state'] ?? $paymentData['status'] ?? 'UNKNOWN');

            // Extract key payment details for the timeline
            $charge = $paymentData['charge'] ?? [];
            $customer = $paymentData['customer'] ?? [];
            $paymentMethod = $paymentData['attempts'][0]['payment_method'] ?? [];

            $order->logEvent('payment_verified', [
                'state'          => $state,
                'charge_token'   => $charge['token'] ?? null,
                'charge_amount'  => $charge['amount'] ?? null,
                'charge_fees'    => $charge['fees'] ?? null,
                'charge_net'     => $charge['net'] ?? null,
                'customer_name'  => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                'customer_email' => $customer['email'] ?? null,
                'card_last_four' => $paymentMethod['last_four'] ?? null,
                'card_scheme'    => $paymentMethod['scheme'] ?? null,
                'card_issuer'    => $paymentMethod['issuer'] ?? null,
            ]);

            Log::info('Checkout callback: Payment state', [
                'order'   => $orderToken,
                'tracker' => $order->safepay_tracker,
                'state'   => $state,
            ]);

            if (in_array($state, ['PAID', 'DELIVERED', 'TRACKER_ENDED'])) {
                $reference = $ref ?? ($charge['signature'] ?? $paymentData['reference'] ?? null);
                $order->markAsPaid($reference);

                // Store full Safepay response in metadata
                $order->update([
                    'metadata' => array_merge($order->metadata ?? [], [
                        'safepay_response' => $paymentData,
                    ]),
                ]);

                // Update customer name from Safepay if we don't have one
                if (empty($order->customer_name) && !empty($customer['first_name'])) {
                    $order->update([
                        'customer_name' => trim(($customer['first_name'] ?? '') . ' ' . ($customer['last_name'] ?? '')),
                    ]);
                }

                // Send confirmation email
                $this->sendConfirmationEmail($order);

                return $this->respond($isIframe, route('checkout.success', $order->order_token));
            }

            // Payment not in a successful state
            $order->logEvent('payment_not_successful', [
                'state'   => $state,
                'message' => 'Payment returned non-success state from Safepay',
            ]);
            $order->markAsFailed();

            return $this->respond(
                $isIframe,
                route('checkout.cancel', ['order' => $order->order_token]),
                'Payment was not completed. Please try again.'
            );

        } catch (\Exception $e) {
            Log::error('Checkout callback verification failed', [
                'order'   => $orderToken,
                'tracker' => $tracker,
                'error'   => $e->getMessage(),
            ]);

            $order->logEvent('verification_error', [
                'error'   => $e->getMessage(),
                'message' => 'Failed to verify payment with Safepay. Buyer may have been charged — requires manual check.',
            ]);

            return $this->respond(
                $isIframe,
                route('checkout.cancel', ['order' => $order->order_token]),
                'Unable to verify payment. Please contact support if you were charged.'
            );
        }
    }

    /**
     * Show checkout success page.
     *
     * GET /checkout/success/{orderToken}
     */
    public function success(string $orderToken)
    {
        $order = Order::where('order_token', $orderToken)
            ->where('status', 'paid')
            ->with('project')
            ->firstOrFail();

        $product = $order->project;
        $accessUrl = $order->getAccessUrl();

        return view('frontend.checkout-success', compact('order', 'product', 'accessUrl'));
    }

    /**
     * Show checkout cancellation page.
     *
     * GET /checkout/cancel
     *
     * Marks pending orders as cancelled. If loaded inside the checkout
     * iframe (?iframe=1), redirects the parent window instead.
     */
    public function cancel(Request $request)
    {
        $orderToken = $request->query('order');
        $isIframe   = $request->boolean('iframe');
        $order      = null;
        $product    = null;

        if ($orderToken) {
            $order = Order::where('order_token', $orderToken)->with('project')->first();
            $product = $order?->project;

            // Mark as cancelled if still pending
            if ($order && $order->status === 'pending') {
                $order->logEvent('buyer_cancelled', [
                    'source' => $isIframe ? 'iframe' : 'direct',
                ]);
                $order->markAsCancelled();
            }
        }

        if ($isIframe) {
            return $this->respond(false, route('checkout.cancel', ['order' => $orderToken]), null, true);
        }

        return view('frontend.checkout-cancel', compact('order', 'product'));
    }

    /**
     * Send order confirmation email with proper tracking.
     *
     * Checks if mail is configured for real delivery and logs the attempt
     * in the order timeline. Prevents duplicate emails via email_sent flag.
     */
    private function sendConfirmationEmail(Order $order): void
    {
        if (!$order->customer_email) {
            $order->logEvent('email_skipped', ['reason' => 'No customer email on order']);
            return;
        }

        // Refresh metadata to get latest (markAsPaid may have updated it)
        $order->refresh();

        if ($order->metadata['email_sent'] ?? false) {
            $order->logEvent('email_skipped', ['reason' => 'Already sent (duplicate prevention)']);
            return;
        }

        $mailDriver = config('mail.default', 'log');
        $isRealMailer = !in_array($mailDriver, ['log', 'array', 'null']);

        try {
            Mail::to($order->customer_email)->send(new OrderConfirmation($order));

            $order->update([
                'metadata' => array_merge($order->metadata ?? [], ['email_sent' => true]),
            ]);

            $order->logEvent('email_dispatched', [
                'to'       => $order->customer_email,
                'driver'   => $mailDriver,
                'is_real'  => $isRealMailer,
                'note'     => $isRealMailer
                    ? 'Email sent via ' . $mailDriver
                    : "Email logged only (driver: {$mailDriver}). Configure a real mail service for actual delivery.",
            ]);

            if (!$isRealMailer) {
                Log::warning('OrderConfirmation dispatched via log/null driver — buyer will NOT receive email', [
                    'order_id' => $order->id,
                    'email'    => $order->customer_email,
                    'driver'   => $mailDriver,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Order confirmation email failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);

            $order->logEvent('email_failed', [
                'to'    => $order->customer_email,
                'error' => $e->getMessage(),
                'note'  => 'Email delivery failed. Buyer was charged but did NOT receive confirmation.',
            ]);
        }
    }

    /**
     * Helper: return an iframe-aware redirect response.
     */
    private function respond(bool $isIframe, string $url, ?string $flashError = null, bool $forceJs = false): mixed
    {
        if ($isIframe || $forceJs) {
            $safeUrl = json_encode($url);
            return response(
                "<!DOCTYPE html><html><head>" .
                "<script>window.top.location.href = {$safeUrl};</script>" .
                "</head><body style=\"background:#0D1B2A;color:#E0E1DD;font-family:sans-serif;display:flex;align-items:center;justify-content:center;height:100vh\">" .
                "<p>Redirecting...</p></body></html>"
            );
        }

        $redirect = redirect($url);

        if ($flashError) {
            $redirect = $redirect->with('error', $flashError);
        }

        return $redirect;
    }
}
