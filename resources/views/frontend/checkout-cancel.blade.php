@extends('layouts.app')

@section('title', 'Payment Cancelled')
@section('description', 'Your payment was cancelled or could not be completed.')

@php
    $accent = $product?->color_primary ?? '#41EAD4';
    $accentSecondary = $product?->color_secondary ?? '#FF6B35';
@endphp

@section('content')

<section class="relative min-h-screen flex items-center overflow-hidden"
         style="background: linear-gradient(135deg, #0D1B2A 0%, #1B3A4B 50%, #0D1B2A 100%);">

    <div class="relative z-10 max-w-2xl mx-auto px-6 lg:px-8 py-20 pt-32 text-center">

        {{-- Cancel icon --}}
        <div class="mb-8 animate-up">
            <div class="w-24 h-24 rounded-full mx-auto flex items-center justify-center" style="background: {{ $accentSecondary }}20;">
                <div class="w-16 h-16 rounded-full flex items-center justify-center" style="background: {{ $accentSecondary }}30;">
                    <svg class="w-8 h-8" style="color: {{ $accentSecondary }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                    </svg>
                </div>
            </div>
        </div>

        {{-- Heading --}}
        <h1 class="text-3xl sm:text-4xl font-black text-white mb-3 animate-up">Payment Cancelled</h1>
        <p class="text-lg text-soft/60 mb-10 animate-up">
            @if(session('error'))
                {{ session('error') }}
            @else
                Your payment was not completed. No charges were made.
            @endif
        </p>

        {{-- Action buttons --}}
        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 animate-up">
            @if($product)
            <a href="{{ route('products.show', $product->slug) }}#pricing"
               class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="background: {{ $accent }};">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                Try Again
            </a>
            @endif

            <a href="{{ route('contact') }}"
               class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-white border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="border-color: {{ $accent }}40;">
                Contact Support
            </a>
        </div>

        {{-- Return home --}}
        <div class="mt-8 animate-up">
            <a href="{{ route('home') }}" class="text-sm text-soft/40 hover:text-soft/60 transition-colors">
                Return to Home
            </a>
        </div>
    </div>
</section>

@endsection
