@extends('layouts.app')

@section('title', $page->title . ' - ' . $product->title)
@section('description', 'Get started with ' . $product->title)

@php
    $accent = $product->color_primary ?? '#41EAD4';
    $accentSecondary = $product->color_secondary ?? '#FF6B35';
    $content = $page->content ?? [];
    $options = $content['options'] ?? [];
    $heading = $content['heading'] ?? 'Congratulations!';
    $message = $content['message'] ?? 'Thank you for your purchase. Choose how you\'d like to get started.';
@endphp

@section('content')

{{-- Hero --}}
<section class="relative min-h-[40vh] flex items-center overflow-hidden"
         style="background: linear-gradient(135deg, #0D1B2A 0%, {{ $accent }}12 40%, #1B3A4B 70%, {{ $accentSecondary }}08 100%);">
    <div class="absolute inset-0" style="background: radial-gradient(ellipse at 50% 30%, {{ $accent }}15, transparent 70%);"></div>

    <div class="relative z-10 max-w-4xl mx-auto px-6 lg:px-8 py-20 text-center w-full">
        {{-- Checkmark icon --}}
        <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-8 animate-up"
             style="background: {{ $accent }}20;">
            <svg class="w-10 h-10" style="color: {{ $accent }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
            </svg>
        </div>

        {{-- Breadcrumb --}}
        <nav class="flex justify-center mb-6 animate-up" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-soft/60 hover:text-teal transition-colors">Home</a></li>
                <li class="text-soft/40">/</li>
                <li><a href="{{ route('products.show', $product->slug) }}" class="text-soft/60 hover:text-teal transition-colors">{{ $product->title }}</a></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft/80">{{ $page->title }}</li>
            </ol>
        </nav>

        <h1 class="text-4xl sm:text-5xl font-black text-white mb-4 animate-up">{{ $heading }}</h1>
        <p class="text-lg text-soft/60 max-w-xl mx-auto animate-up">{{ $message }}</p>
    </div>
</section>

{{-- Option Cards --}}
@if(count($options) > 0)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-{{ min(count($options), 2) }} gap-8">
            @foreach($options as $index => $option)
            @php $isRecommended = $option['recommended'] ?? false; @endphp
            <div class="relative rounded-2xl p-8 border transition-all duration-300 animate-up
                        {{ $isRecommended
                            ? 'border-2 bg-white dark:bg-ocean/30 shadow-lg'
                            : 'border-gray-200 dark:border-soft/10 bg-white dark:bg-ocean/10 hover:shadow-md' }}"
                 style="{{ $isRecommended ? 'border-color: ' . $accent . ';' : '' }}">

                {{-- Recommended badge --}}
                @if($isRecommended)
                <div class="absolute -top-3.5 left-1/2 -translate-x-1/2">
                    <span class="px-4 py-1 text-xs font-bold uppercase tracking-wider rounded-full text-midnight"
                          style="background: {{ $accent }};">
                        Recommended
                    </span>
                </div>
                @endif

                {{-- Icon --}}
                @if(!empty($option['icon']))
                <div class="w-14 h-14 rounded-xl flex items-center justify-center mb-6"
                     style="background: {{ $accent }}10; color: {{ $accent }};">
                    <x-product-icon :icon="$option['icon']" class="w-7 h-7" />
                </div>
                @endif

                <h3 class="text-xl font-bold text-midnight dark:text-white mb-3">{{ $option['title'] }}</h3>
                <p class="text-sm text-soft-dark/70 dark:text-soft/50 leading-relaxed mb-6">{{ $option['description'] }}</p>

                @if(!empty($option['button_url']))
                @php $isExternal = str_starts_with($option['button_url'], 'http'); @endphp
                <a href="{{ $option['button_url'] }}" {{ $isExternal ? 'target="_blank" rel="noopener"' : '' }}
                   class="inline-flex items-center px-6 py-3 rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5
                          {{ $isRecommended ? 'text-midnight' : 'text-white border-2' }}"
                   style="{{ $isRecommended
                       ? 'background: ' . $accent . ';'
                       : 'border-color: ' . $accent . '60; color: ' . $accent . ';' }}">
                    {{ $option['button_label'] ?? 'Get Started' }}
                    @if($isExternal)
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    @else
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"></path></svg>
                    @endif
                </a>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Back to product --}}
<section class="py-12 bg-white dark:bg-ocean/10">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <a href="{{ route('products.show', $product->slug) }}"
           class="inline-flex items-center text-sm font-medium transition-colors hover:underline"
           style="color: {{ $accent }};">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to {{ $product->title }}
        </a>
    </div>
</section>

@endsection
