@extends('layouts.app')

@section('title', 'Order Confirmed - ' . $product->title)
@section('description', 'Your purchase of ' . $product->title . ' is confirmed.')

@php
    $accent = $product->color_primary ?? '#41EAD4';
    $accentSecondary = $product->color_secondary ?? '#FF6B35';
@endphp

@section('content')

<section class="relative min-h-screen flex items-center overflow-hidden"
         style="background: linear-gradient(135deg, #0D1B2A 0%, {{ $accent }}08 50%, #1B3A4B 100%);">

    {{-- Decorative glow --}}
    <div class="absolute top-1/3 left-1/2 -translate-x-1/2 w-[600px] h-[600px] rounded-full blur-3xl opacity-10" style="background: {{ $accent }};"></div>

    <div class="relative z-10 max-w-2xl mx-auto px-6 lg:px-8 py-20 pt-32 text-center">

        {{-- Success checkmark animation --}}
        <div class="mb-8 animate-up">
            <div class="w-24 h-24 rounded-full mx-auto flex items-center justify-center"
                 style="background: {{ $accent }}20; animation: pulse-ring 2s ease-in-out infinite;">
                <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background: {{ $accent }};">
                    <svg class="w-8 h-8 text-midnight" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-3 animate-up">Payment Successful!</h1>
        <p class="text-lg text-soft/60 mb-10 animate-up">Thank you for purchasing <strong class="text-white">{{ $product->title }}</strong></p>

        {{-- Order details card --}}
        <div class="rounded-2xl border border-soft/10 bg-ocean/20 p-6 mb-8 text-left animate-up">
            <h3 class="text-sm font-bold uppercase tracking-wider text-soft/40 mb-4">Order Summary</h3>

            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-soft/60 text-sm">Product</span>
                    <span class="text-white font-medium">{{ $product->title }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-soft/60 text-sm">Plan</span>
                    <span class="text-white font-medium">{{ $order->tier_name }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-soft/60 text-sm">Amount</span>
                    <span class="text-white font-bold text-lg">{{ $order->currency }} {{ number_format($order->amount, 2) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-soft/60 text-sm">Date</span>
                    <span class="text-soft/80 text-sm">{{ $order->paid_at->format('M d, Y \a\t g:i A') }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-soft/60 text-sm">Order ID</span>
                    <span class="text-soft/80 text-sm font-mono">{{ Str::limit($order->order_token, 16) }}</span>
                </div>
            </div>
        </div>

        {{-- Email notice --}}
        @if($order->customer_email)
        <p class="text-sm text-soft/40 mb-8 animate-up">
            A confirmation email with your access link has been sent to
            <strong class="text-soft/70">{{ $order->customer_email }}</strong>
        </p>
        @endif

        {{-- CTA button --}}
        <div class="animate-up">
            <a href="{{ $accessUrl }}"
               class="inline-flex items-center px-8 py-4 rounded-xl font-semibold text-midnight text-lg transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="background: {{ $accent }};">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                Get Started
            </a>
        </div>

        {{-- Secondary link --}}
        <div class="mt-6 animate-up">
            <a href="{{ route('products.show', $product->slug) }}"
               class="text-sm font-medium transition-colors hover:underline" style="color: {{ $accent }};">
                Back to {{ $product->title }}
            </a>
        </div>
    </div>
</section>

@push('styles')
<style>
    @keyframes pulse-ring {
        0%, 100% { box-shadow: 0 0 0 0 {{ $accent }}40; }
        50% { box-shadow: 0 0 0 20px {{ $accent }}00; }
    }
</style>
@endpush

@endsection
