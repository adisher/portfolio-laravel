@extends('layouts.app')

@section('title', $page->title . ' - ' . $product->title)
@section('description', 'Step-by-step deployment guide for ' . $product->title)

@php
    $accent = $product->color_primary ?? '#41EAD4';
    $accentSecondary = $product->color_secondary ?? '#FF6B35';
    $content = $page->content ?? [];
    $steps = $content['steps'] ?? [];
    $heading = $content['heading'] ?? 'Deployment Guide';
    $supportHeading = $content['support_heading'] ?? 'Need Help?';
    $supportMessage = $content['support_message'] ?? 'If you run into any issues, feel free to reach out.';
    $supportUrl = $content['support_url'] ?? route('contact');

    // Auto-link URLs in plain text
    if (!function_exists('autoLinkUrls')) {
        function autoLinkUrls($text) {
            return preg_replace(
                '/(https?:\/\/[^\s<]+)/',
                '<a href="$1" target="_blank" rel="noopener" class="underline hover:no-underline" style="color: inherit; font-weight: 500;">$1</a>',
                e($text)
            );
        }
    }
@endphp

@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden"
         style="background: linear-gradient(135deg, #0D1B2A 0%, {{ $accent }}08 50%, #1B3A4B 100%);">

    <div class="relative z-10 max-w-4xl mx-auto px-6 lg:px-8 py-20 pt-32">
        {{-- Breadcrumb --}}
        <nav class="flex mb-8 animate-up" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-soft/60 hover:text-teal transition-colors">Home</a></li>
                <li class="text-soft/40">/</li>
                <li><a href="{{ route('products.show', $product->slug) }}" class="text-soft/60 hover:text-teal transition-colors">{{ $product->title }}</a></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft/80">{{ $page->title }}</li>
            </ol>
        </nav>

        <div class="flex items-center gap-4 mb-4 animate-up">
            <div class="w-12 h-12 rounded-xl flex items-center justify-center" style="background: {{ $accent }}20;">
                <x-product-icon icon="rocket" class="w-6 h-6" style="color: {{ $accent }};" />
            </div>
            <h1 class="text-3xl sm:text-4xl font-black text-white">{{ $heading }}</h1>
        </div>
        <p class="text-soft/50 text-sm ml-16 animate-up">Follow these steps to deploy {{ $product->title }}</p>
    </div>
</section>

{{-- Steps Timeline --}}
@if(count($steps) > 0)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight"
         x-data="{ completed: {} }">
    <div class="max-w-3xl mx-auto px-6 lg:px-8">

        <div class="relative">
            {{-- Vertical timeline line --}}
            <div class="absolute left-6 top-0 bottom-0 w-0.5 opacity-20" style="background: {{ $accent }};"></div>

            <div class="space-y-12">
                @foreach($steps as $index => $step)
                <div class="relative pl-16 animate-up">
                    {{-- Step number circle --}}
                    <div class="absolute left-0 w-12 h-12 rounded-full flex items-center justify-center text-lg font-bold border-4 border-soft-light dark:border-midnight transition-colors duration-300"
                         :class="completed[{{ $index }}] ? '' : ''"
                         :style="completed[{{ $index }}]
                            ? 'background: {{ $accent }}; color: #0D1B2A;'
                            : 'background: {{ $accent }}20; color: {{ $accent }};'">
                        <span x-show="!completed[{{ $index }}]">{{ $index + 1 }}</span>
                        <svg x-show="completed[{{ $index }}]" x-cloak class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>

                    {{-- Step content card --}}
                    <div class="rounded-2xl border border-gray-200 dark:border-soft/10 bg-white dark:bg-ocean/15 p-6 transition-all duration-300"
                         :class="completed[{{ $index }}] ? 'opacity-60' : ''">

                        <h3 class="text-xl font-bold text-midnight dark:text-white mb-2">{{ $step['title'] }}</h3>

                        @if(!empty($step['description']))
                        <p class="text-sm text-soft-dark/70 dark:text-soft/50 mb-4 leading-relaxed">{!! autoLinkUrls($step['description']) !!}</p>
                        @endif

                        {{-- Checklist items --}}
                        @if(!empty($step['items']))
                        @php $items = is_array($step['items']) ? $step['items'] : explode("\n", $step['items']); @endphp
                        <div class="space-y-2 mb-4">
                            @foreach($items as $itemIndex => $item)
                            @if(trim($item))
                            <label class="flex items-start gap-3 text-sm cursor-pointer group">
                                <input type="checkbox" class="mt-1 rounded border-gray-300 dark:border-soft/30 focus:ring-0 cursor-pointer"
                                       style="accent-color: {{ $accent }};"
                                       @change="let key = '{{ $index }}_{{ $itemIndex }}'; completed[key] = $event.target.checked">
                                <span class="text-soft-dark/80 dark:text-soft/60 group-hover:text-midnight dark:group-hover:text-white transition-colors">{!! autoLinkUrls(trim($item)) !!}</span>
                            </label>
                            @endif
                            @endforeach
                        </div>
                        @endif

                        {{-- Action button --}}
                        @if(!empty($step['button_url']))
                        <a href="{{ $step['button_url'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center px-5 py-2.5 rounded-lg text-sm font-semibold text-midnight transition-all duration-300 hover:shadow-md hover:-translate-y-0.5"
                           style="background: {{ $accent }};">
                            {{ $step['button_label'] ?? 'Open' }}
                            <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                        </a>
                        @endif

                        {{-- Guidance text --}}
                        @if(!empty($step['guidance']))
                        <p class="text-xs text-soft-dark/50 dark:text-soft/30 mt-3 italic">{!! autoLinkUrls($step['guidance']) !!}</p>
                        @endif

                        {{-- Note callout --}}
                        @if(!empty($step['note']))
                        <div class="mt-4 px-4 py-3 rounded-lg text-sm border"
                             style="background: {{ $accent }}05; border-color: {{ $accent }}20; color: {{ $accent }};">
                            <strong>Note:</strong> {{ $step['note'] }}
                        </div>
                        @endif

                        {{-- Mark as done --}}
                        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-soft/5">
                            <label class="inline-flex items-center gap-2 text-xs font-medium cursor-pointer"
                                   style="color: {{ $accent }};">
                                <input type="checkbox" class="rounded border-gray-300 dark:border-soft/30 focus:ring-0"
                                       style="accent-color: {{ $accent }};"
                                       @change="completed[{{ $index }}] = $event.target.checked">
                                Mark step as complete
                            </label>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endif

{{-- Need Help? --}}
<section class="py-16 bg-white dark:bg-ocean/10">
    <div class="max-w-3xl mx-auto px-6 lg:px-8">
        <div class="rounded-2xl border border-gray-200 dark:border-soft/10 bg-gray-50 dark:bg-ocean/20 p-8 text-center animate-up">
            <div class="w-14 h-14 rounded-full flex items-center justify-center mx-auto mb-4"
                 style="background: {{ $accentSecondary }}15;">
                <svg class="w-7 h-7" style="color: {{ $accentSecondary }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
            </div>
            <h3 class="text-xl font-bold text-midnight dark:text-white mb-2">{{ $supportHeading }}</h3>
            <p class="text-sm text-soft-dark/60 dark:text-soft/50 mb-6">{{ $supportMessage }}</p>
            <a href="{{ $supportUrl }}"
               class="inline-flex items-center px-6 py-3 rounded-xl text-sm font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="background: {{ $accent }};">
                Contact Support
            </a>
        </div>

        {{-- Back to product --}}
        <div class="text-center mt-8">
            <a href="{{ route('products.show', $product->slug) }}"
               class="inline-flex items-center text-sm font-medium transition-colors hover:underline"
               style="color: {{ $accent }};">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                Back to {{ $product->title }}
            </a>
        </div>
    </div>
</section>

@endsection
