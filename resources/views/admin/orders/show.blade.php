@extends('layouts.admin')
@section('title', 'Order Details')

@section('content')
<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('admin.orders.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Order Details</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 font-mono">{{ Str::limit($order->order_token, 24) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Order Summary --}}
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Order Summary</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</p>
                        <div class="mt-1">
                            @switch($order->status)
                                @case('paid')
                                    <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">Paid</span>
                                    @break
                                @case('pending')
                                    <span class="status-badge bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">Pending</span>
                                    @break
                                @case('failed')
                                    <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">Failed</span>
                                    @break
                                @case('cancelled')
                                    <span class="status-badge bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400">Cancelled</span>
                                    @break
                                @case('refunded')
                                    <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-400">Refunded</span>
                                    @break
                            @endswitch
                        </div>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Amount</p>
                        <p class="text-2xl font-bold text-gray-800 dark:text-white mt-1">{{ $order->currency }} {{ number_format($order->amount, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Product</p>
                        <p class="font-medium text-gray-800 dark:text-white mt-1">{{ $order->project->title ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tier</p>
                        <p class="font-medium text-gray-800 dark:text-white mt-1">{{ $order->tier_name }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Created</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $order->created_at->format('M d, Y \a\t g:i A') }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Paid At</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $order->paid_at ? $order->paid_at->format('M d, Y \a\t g:i A') : '—' }}</p>
                    </div>
                </div>
            </div>

            {{-- Payment Details --}}
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Payment Details</h2>
                <div class="space-y-3">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Safepay Tracker</span>
                        <span class="font-mono text-sm text-gray-800 dark:text-gray-200">{{ $order->safepay_tracker ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Safepay Reference</span>
                        <span class="font-mono text-sm text-gray-800 dark:text-gray-200">{{ Str::limit($order->safepay_reference, 32) ?: '—' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Currency</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $order->currency }}</span>
                    </div>
                    @php
                        $charge = $order->metadata['safepay_response']['charge'] ?? null;
                        $paymentMethod = $order->metadata['safepay_response']['attempts'][0]['payment_method'] ?? null;
                    @endphp
                    @if($charge)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Safepay Fees</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $charge['fees']['currency'] ?? 'PKR' }} {{ number_format(($charge['fees']['amount'] ?? 0) / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Tax</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $charge['tax']['currency'] ?? 'PKR' }} {{ number_format(($charge['tax']['amount'] ?? 0) / 100, 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Net Amount (You Receive)</span>
                        <span class="text-sm font-bold text-green-600 dark:text-green-400">{{ $charge['net']['currency'] ?? 'PKR' }} {{ number_format(($charge['net']['amount'] ?? 0) / 100, 2) }}</span>
                    </div>
                    @endif
                    @if($paymentMethod)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100 dark:border-gray-700">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Card</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $paymentMethod['scheme'] ?? '' }} **** {{ $paymentMethod['last_four'] ?? '' }}</span>
                    </div>
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Issuer</span>
                        <span class="text-sm text-gray-800 dark:text-gray-200">{{ $paymentMethod['issuer'] ?? '—' }}</span>
                    </div>
                    @endif
                    @if(!$charge && !$paymentMethod)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-sm text-gray-500 dark:text-gray-400">Order Token</span>
                        <span class="font-mono text-xs text-gray-600 dark:text-gray-400 break-all max-w-[300px] text-right">{{ $order->order_token }}</span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Transaction Timeline --}}
            @php $timeline = $order->getTimeline(); @endphp
            @if(count($timeline) > 0)
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">
                    <svg class="w-5 h-5 inline-block mr-1 -mt-0.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Transaction Timeline
                </h2>
                <div class="relative">
                    {{-- Vertical line --}}
                    <div class="absolute left-[11px] top-2 bottom-2 w-0.5 bg-gray-200 dark:bg-gray-700"></div>

                    <div class="space-y-4">
                        @foreach($timeline as $event)
                        @php
                            $eventName = $event['event'] ?? 'unknown';
                            $timestamp = $event['timestamp'] ?? '';
                            $details = $event['details'] ?? [];

                            // Color coding based on event type
                            $dotColor = match(true) {
                                str_contains($eventName, 'paid') || str_contains($eventName, 'confirmed') || $eventName === 'status_changed' && ($details['to'] ?? '') === 'paid' => 'bg-green-500',
                                str_contains($eventName, 'failed') || str_contains($eventName, 'error') => 'bg-red-500',
                                str_contains($eventName, 'cancelled') => 'bg-orange-500',
                                str_contains($eventName, 'email') => 'bg-blue-500',
                                str_contains($eventName, 'webhook') => 'bg-purple-500',
                                str_contains($eventName, 'created') || str_contains($eventName, 'ready') => 'bg-teal-500',
                                default => 'bg-gray-400',
                            };

                            // Human-readable event label
                            $label = match($eventName) {
                                'order_created' => 'Order Created',
                                'payment_session_created' => 'Payment Session Created',
                                'checkout_ready' => 'Checkout Ready',
                                'callback_received' => 'Callback Received',
                                'callback_skipped' => 'Callback Skipped',
                                'payment_verification_started' => 'Verifying Payment',
                                'payment_verified' => 'Payment Verified',
                                'payment_not_successful' => 'Payment Not Successful',
                                'verification_error' => 'Verification Error',
                                'status_changed' => 'Status Changed: ' . ($details['from'] ?? '?') . ' → ' . ($details['to'] ?? '?'),
                                'email_dispatched' => 'Email Dispatched',
                                'email_failed' => 'Email Failed',
                                'email_skipped' => 'Email Skipped',
                                'buyer_cancelled' => 'Buyer Cancelled',
                                'webhook_received' => 'Webhook Received',
                                'webhook_skipped' => 'Webhook Skipped (Already Processed)',
                                'payment_confirmed_via_webhook' => 'Payment Confirmed (Webhook)',
                                'payment_failed_via_webhook' => 'Payment Failed (Webhook)',
                                'payment_cancelled_via_webhook' => 'Cancelled (Webhook)',
                                'webhook_unhandled' => 'Unhandled Webhook Event',
                                default => str_replace('_', ' ', ucfirst($eventName)),
                            };
                        @endphp
                        <div class="relative pl-8">
                            {{-- Dot --}}
                            <div class="absolute left-[5px] top-1.5 w-[13px] h-[13px] rounded-full {{ $dotColor }} border-2 border-white dark:border-gray-900 z-10"></div>

                            <div>
                                <div class="flex items-baseline gap-2 flex-wrap">
                                    <span class="text-sm font-medium text-gray-800 dark:text-white">{{ $label }}</span>
                                    @if($timestamp)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ \Carbon\Carbon::parse($timestamp)->format('M d, g:i:s A') }}</span>
                                    @endif
                                </div>

                                {{-- Event details --}}
                                @if(!empty($details))
                                <div class="mt-1 space-y-0.5">
                                    @foreach($details as $key => $value)
                                        @if($key === 'from' || $key === 'to')
                                            @continue
                                        @endif
                                        @if(is_array($value))
                                            @continue
                                        @endif
                                        <p class="text-xs text-gray-500 dark:text-gray-400">
                                            <span class="text-gray-400 dark:text-gray-500">{{ str_replace('_', ' ', ucfirst($key)) }}:</span>
                                            @if($key === 'error' || $key === 'note')
                                                <span class="{{ str_contains($key, 'error') || str_contains($value, 'NOT') ? 'text-red-500 dark:text-red-400' : 'text-gray-600 dark:text-gray-300' }}">{{ Str::limit($value, 120) }}</span>
                                            @else
                                                <span class="text-gray-600 dark:text-gray-300">{{ Str::limit((string) $value, 80) }}</span>
                                            @endif
                                        </p>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Raw Metadata (collapsed) --}}
            @if($order->metadata)
            <div class="admin-card p-6" x-data="{ open: false }">
                <button @click="open = !open" class="flex items-center justify-between w-full text-left">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-white">Raw Metadata</h2>
                    <svg class="w-5 h-5 text-gray-400 transition-transform" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>
                <div x-show="open" x-collapse>
                    <pre class="mt-4 bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-xs font-mono text-gray-600 dark:text-gray-300 overflow-x-auto max-h-96">{{ json_encode($order->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Customer Info --}}
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Customer</h2>
                <div class="space-y-3">
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Name</p>
                        <p class="text-sm text-gray-800 dark:text-white mt-1">{{ $order->customer_name ?: '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Email</p>
                        <p class="text-sm text-gray-800 dark:text-white mt-1">{{ $order->customer_email ?: '—' }}</p>
                    </div>
                    @if($order->metadata['ip_address'] ?? null)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 font-mono">{{ $order->metadata['ip_address'] }}</p>
                    </div>
                    @endif
                    @php $sfCustomer = $order->metadata['safepay_response']['customer'] ?? null; @endphp
                    @if($sfCustomer)
                    <div>
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Safepay Customer</p>
                        <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ $sfCustomer['first_name'] ?? '' }} {{ $sfCustomer['last_name'] ?? '' }}</p>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $sfCustomer['email'] ?? '' }} &middot; {{ $sfCustomer['phone'] ?? '' }}</p>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Email Status --}}
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Email Status</h2>
                @if($order->metadata['email_sent'] ?? false)
                    <div class="flex items-center gap-2 text-green-600 dark:text-green-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span class="text-sm font-medium">Confirmation Dispatched</span>
                    </div>
                    @php
                        $emailEvent = collect($order->getTimeline())->last(fn($e) => $e['event'] === 'email_dispatched');
                    @endphp
                    @if($emailEvent)
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                            To: {{ $emailEvent['details']['to'] ?? $order->customer_email }}<br>
                            Driver: {{ $emailEvent['details']['driver'] ?? 'unknown' }}
                            @if(!($emailEvent['details']['is_real'] ?? true))
                                <br><span class="text-orange-500">Warning: Using log driver — email NOT actually delivered</span>
                            @endif
                        </p>
                    @endif
                @else
                    <div class="flex items-center gap-2 text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/></svg>
                        <span class="text-sm">Not sent</span>
                    </div>
                @endif
            </div>

            {{-- Quick Actions --}}
            <div class="admin-card p-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Quick Actions</h2>
                <div class="space-y-2">
                    @if($order->isPaid())
                    <a href="{{ $order->getAccessUrl() }}" target="_blank"
                       class="block text-center py-2 px-4 rounded-lg bg-blue-50 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400 text-sm font-medium hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                        View Access Page
                    </a>
                    @endif
                    <a href="{{ route('products.show', $order->project->slug ?? '') }}" target="_blank"
                       class="block text-center py-2 px-4 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        View Product
                    </a>
                    @if($order->safepay_tracker)
                    <a href="https://getsafepay.com/dashboard/payments/{{ $order->safepay_tracker }}" target="_blank"
                       class="block text-center py-2 px-4 rounded-lg bg-gray-50 dark:bg-gray-800 text-gray-600 dark:text-gray-400 text-sm font-medium hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        View in Safepay Dashboard ↗
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
