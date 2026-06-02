<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SafepayService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $apiSecret;
    protected string $environment;
    protected ?string $webhookSecret;

    public function __construct()
    {
        $this->baseUrl       = config('services.safepay.base_url');
        $this->apiKey        = config('services.safepay.api_key');
        $this->apiSecret     = config('services.safepay.api_secret');
        $this->environment   = config('services.safepay.environment', 'sandbox');
        $this->webhookSecret = config('services.safepay.webhook_secret');
    }

    /**
     * Default headers for Safepay API requests.
     *
     * The X-SFPY-MERCHANT-SECRET header must contain the API SECRET (not the API key).
     * This matches the official sfpy-php SDK's ApiRequestor behavior.
     */
    protected function defaultHeaders(): array
    {
        return [
            'X-SFPY-MERCHANT-SECRET' => $this->apiSecret,
            'Content-Type'           => 'application/json',
        ];
    }

    /**
     * Create a payment session on Safepay.
     *
     * Uses /order/payments/v3/ — the v3 endpoint compatible with the /embedded checkout page.
     * Amount must be in the lowest currency denomination (paisa for PKR, cents for USD).
     *
     * @return array{token: string, tracker: string}
     * @throws \Exception
     */
    public function createPayment(float $amount, string $currency = 'PKR'): array
    {
        // Convert to lowest denomination (paisa for PKR, cents for USD)
        $amountInLowest = (int) round($amount * 100);

        $response = Http::withHeaders($this->defaultHeaders())
            ->post("{$this->baseUrl}/order/payments/v3/", [
                'merchant_api_key' => $this->apiKey,
                'intent'           => 'CYBERSOURCE',
                'mode'             => 'payment',
                'currency'         => $currency,
                'amount'           => $amountInLowest,
            ]);

        if (!$response->successful()) {
            Log::error('Safepay: Failed to create payment session', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            $errorMsg = $response->json('status.message')
                ?? $response->json('message')
                ?? implode(', ', $response->json('status.errors') ?? [])
                ?: 'Unknown error';
            throw new \Exception('Failed to create Safepay payment session: ' . $errorMsg);
        }

        $data = $response->json('data');

        // v3 response format: { data: { tracker: { token: "track_xxx", ... } } }
        $tracker = $data['tracker']['token'] ?? '';

        Log::info('Safepay: Payment session created (v3)', [
            'tracker' => $tracker,
            'amount'  => $amount,
            'amount_lowest' => $amountInLowest,
        ]);

        return [
            'token'   => $tracker,
            'tracker' => $tracker,
        ];
    }

    /**
     * Get an authentication token (tbt) for checkout redirect.
     *
     * The passport endpoint requires:
     * - Header: X-SFPY-MERCHANT-SECRET = API secret
     * - Body: key = merchant API key (sec_...), secret = API secret
     *
     * @return string The auth token (tbt)
     * @throws \Exception
     */
    public function getAuthToken(): string
    {
        $response = Http::withHeaders($this->defaultHeaders())
            ->post("{$this->baseUrl}/client/passport/v1/token", [
                'key'    => $this->apiKey,
                'secret' => $this->apiSecret,
            ]);

        if (!$response->successful()) {
            Log::error('Safepay: Failed to get auth token', [
                'status'   => $response->status(),
                'response' => $response->json(),
            ]);
            throw new \Exception('Failed to get Safepay auth token');
        }

        $data = $response->json('data');

        // Response format: { data: "token_string" }
        $token = is_array($data) ? ($data['token'] ?? '') : ($data ?? '');

        Log::info('Safepay: Auth token obtained', [
            'token_length' => strlen($token),
        ]);

        return $token;
    }

    /**
     * Build the full Safepay Express Checkout redirect URL.
     *
     * Uses /embedded path with tracker + tbt auth token.
     * Matches the official SDK's Checkout::constructURL() method.
     */
    public function getCheckoutUrl(
        string $tracker,
        string $tbt,
        string $cancelUrl,
        string $successUrl,
        string $source = 'custom'
    ): string {
        $params = http_build_query([
            'environment'  => $this->environment,
            'tracker'      => $tracker,
            'tbt'          => $tbt,
            'source'       => $source,
            'redirect_url' => $successUrl,
            'cancel_url'   => $cancelUrl,
        ]);

        return "{$this->baseUrl}/embedded?{$params}";
    }

    /**
     * Verify a payment by its tracker token.
     *
     * Uses the reporter endpoint (GET-friendly) as primary, with v3 as fallback.
     * The v3 endpoint returns 405 on GET — it only supports POST for creation.
     *
     * @return array Full payment data from Safepay
     * @throws \Exception
     */
    public function verifyPayment(string $tracker): array
    {
        // Reporter endpoint is the correct one for GET verification
        $response = Http::withHeaders($this->defaultHeaders())
            ->get("{$this->baseUrl}/reporter/api/v1/payments/{$tracker}");

        if (!$response->successful()) {
            Log::warning('Safepay: reporter verify failed', [
                'tracker' => $tracker,
                'status'  => $response->status(),
            ]);
            throw new \Exception("Failed to verify Safepay payment (status: {$response->status()})");
        }

        $data = $response->json('data');

        // Reporter returns tracker data at top level
        return $data ?? $response->json() ?? [];
    }

    /**
     * Verify a webhook signature from Safepay.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            Log::warning('Safepay: Webhook secret not configured, skipping signature verification');
            return true;
        }

        $computed = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($computed, $signature);
    }
}
