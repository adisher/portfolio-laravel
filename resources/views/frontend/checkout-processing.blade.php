<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Checkout - {{ $product->title }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @keyframes spin { to { transform: rotate(360deg); } }
        @keyframes pulse-dot { 0%, 100% { opacity: 0.4; } 50% { opacity: 1; } }

        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(65, 234, 212, 0.15);
            border-top-color: #41EAD4;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }

        .pulse-dot { animation: pulse-dot 1.5s ease-in-out infinite; }
        .pulse-dot:nth-child(2) { animation-delay: 0.2s; }
        .pulse-dot:nth-child(3) { animation-delay: 0.4s; }

        /* Ensure iframe takes available space */
        .checkout-iframe-wrap {
            height: calc(100vh - 64px);
            min-height: 500px;
        }

        /* Slide-up animation for fallback bar */
        .slide-up {
            transform: translateY(100%);
            transition: transform 0.4s ease-out;
        }
        .slide-up.show {
            transform: translateY(0);
        }
    </style>
</head>

<body class="bg-[#0D1B2A] min-h-screen">

    {{-- ── Top Bar ──────────────────────────────────────────── --}}
    <div class="bg-[#1B3A4B]/60 border-b border-[#E0E1DD]/10 px-4 py-3 backdrop-blur-sm"
         style="height: 64px;">
        <div class="max-w-6xl mx-auto flex items-center justify-between h-full">
            {{-- Left: back + product info --}}
            <div class="flex items-center gap-3">
                <a href="{{ route('products.show', $product->slug) }}#pricing"
                   class="text-[#E0E1DD]/40 hover:text-white transition-colors p-1.5 rounded-lg hover:bg-white/5"
                   title="Cancel & return to product">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-white font-semibold text-sm sm:text-base leading-tight">{{ $product->title }}</h1>
                    <p class="text-[#E0E1DD]/40 text-xs">
                        {{ $order->tier_name }} &middot; {{ $order->currency }} {{ number_format($order->amount, 2) }}
                    </p>
                </div>
            </div>

            {{-- Right: secure badge + status --}}
            <div class="flex items-center gap-3">
                <div id="status-indicator" class="hidden sm:flex items-center gap-1.5 text-xs text-[#E0E1DD]/40">
                    <span class="flex gap-0.5">
                        <span class="pulse-dot w-1 h-1 rounded-full bg-[#41EAD4]"></span>
                        <span class="pulse-dot w-1 h-1 rounded-full bg-[#41EAD4]"></span>
                        <span class="pulse-dot w-1 h-1 rounded-full bg-[#41EAD4]"></span>
                    </span>
                    <span id="status-text">Awaiting payment</span>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-[#E0E1DD]/50 bg-white/5 px-2.5 py-1.5 rounded-lg">
                    <svg class="w-3.5 h-3.5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                    Secure Checkout
                </div>
            </div>
        </div>
    </div>

    {{-- ── Checkout iframe container ────────────────────────── --}}
    <div class="checkout-iframe-wrap relative">

        {{-- Loading overlay (shown while iframe loads) --}}
        <div id="loading-overlay" class="absolute inset-0 bg-[#0D1B2A] flex items-center justify-center z-10 transition-opacity duration-500">
            <div class="text-center">
                <div class="spinner mx-auto"></div>
                <p class="text-[#E0E1DD]/50 mt-4 text-sm">Loading secure checkout...</p>
            </div>
        </div>

        {{-- Safepay embedded checkout iframe --}}
        <iframe
            id="safepay-checkout"
            src="{{ $checkoutUrl }}"
            class="w-full h-full border-0"
            allow="payment"
            title="Safepay Checkout"
        ></iframe>
    </div>

    {{-- ── Fallback bar (slides up after timeout) ───────────── --}}
    <div id="fallback-bar" class="slide-up fixed bottom-0 left-0 right-0 bg-[#1B3A4B] border-t border-[#E0E1DD]/10 px-4 py-4 z-30">
        <div class="max-w-lg mx-auto text-center">
            <p class="text-[#E0E1DD]/60 text-sm mb-3">
                Already completed your payment? If you're not redirected automatically:
            </p>
            <div class="flex items-center justify-center gap-3">
                <a href="{{ route('checkout.callback', ['order' => $order->order_token]) }}"
                   class="inline-flex items-center px-5 py-2 rounded-lg text-sm font-semibold text-[#0D1B2A] bg-[#41EAD4] hover:bg-[#41EAD4]/90 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Continue to Order
                </a>
                <a href="{{ route('checkout.cancel', ['order' => $order->order_token]) }}"
                   class="text-sm text-[#E0E1DD]/40 hover:text-white underline transition-colors">
                    Cancel
                </a>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const statusUrl   = @json(route('checkout.status', $order->order_token));
            const successUrl  = @json(route('checkout.success', $order->order_token));
            const cancelUrl   = @json(route('checkout.cancel', ['order' => $order->order_token]));
            const callbackUrl = @json(route('checkout.callback', ['order' => $order->order_token]));

            const iframe      = document.getElementById('safepay-checkout');
            const loading     = document.getElementById('loading-overlay');
            const fallbackBar = document.getElementById('fallback-bar');
            const statusText  = document.getElementById('status-text');

            let resolved = false;

            // ── Hide loading overlay once iframe loads ──
            iframe.addEventListener('load', () => {
                loading.style.opacity = '0';
                setTimeout(() => { loading.style.display = 'none'; }, 500);
            });

            // ── Listen for postMessage from Safepay iframe ──
            window.addEventListener('message', (event) => {
                if (resolved) return;
                try {
                    const data = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                    if (!data) return;

                    // Check for various Safepay event formats
                    const isComplete = (
                        data.event === 'payment:complete' ||
                        data.event === 'payment_complete' ||
                        data.action === 'complete' ||
                        data.type === 'payment:complete'
                    );
                    const isCancelled = (
                        data.event === 'payment:cancelled' ||
                        data.event === 'payment_cancelled' ||
                        data.action === 'cancelled' ||
                        data.type === 'payment:cancelled'
                    );

                    if (isComplete) {
                        resolved = true;
                        if (statusText) statusText.textContent = 'Payment received! Redirecting...';
                        window.location.href = callbackUrl;
                    } else if (isCancelled) {
                        resolved = true;
                        window.location.href = cancelUrl;
                    }
                } catch (e) {
                    // Ignore parse errors from other postMessage events
                }
            });

            // ── Poll order status every 2 seconds ──
            // The server-side status endpoint actively verifies with Safepay's API
            // when the order has been pending for >15s (rate-limited to every 8s).
            // Once Safepay confirms payment, the status returns 'paid' and we redirect.
            let pollCount = 0;
            const maxPolls = 300; // ~10 minutes at 2s intervals

            const poller = setInterval(async () => {
                if (resolved) { clearInterval(poller); return; }
                pollCount++;

                try {
                    const res = await fetch(statusUrl, {
                        headers: { 'Accept': 'application/json' },
                        cache: 'no-store'
                    });
                    if (!res.ok) return;

                    const data = await res.json();

                    if (data.status === 'paid') {
                        resolved = true;
                        clearInterval(poller);
                        if (statusText) statusText.textContent = 'Payment confirmed! Redirecting...';
                        // Hide fallback bar if visible
                        fallbackBar.classList.remove('show');
                        // Brief delay so user sees the status change
                        setTimeout(() => { window.location.href = successUrl; }, 500);
                    } else if (data.status === 'failed' || data.status === 'cancelled') {
                        resolved = true;
                        clearInterval(poller);
                        window.location.href = cancelUrl;
                    }
                } catch (e) {
                    // Network error, will retry on next poll
                }

                // Show fallback bar after ~20 seconds (10 polls × 2s) as safety net
                if (pollCount >= 10 && !resolved) {
                    fallbackBar.classList.add('show');
                }

                // Stop polling after max attempts
                if (pollCount >= maxPolls) {
                    clearInterval(poller);
                }
            }, 2000);
        })();
    </script>

</body>
</html>
