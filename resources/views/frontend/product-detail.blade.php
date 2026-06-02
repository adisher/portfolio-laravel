@extends('layouts.app')

@section('title', $product->title . ' - Products')
@section('description', $product->short_description)

@php
    $accent = $product->color_primary ?? '#41EAD4';
    $accentSecondary = $product->color_secondary ?? '#FF6B35';
    $hasScreenshots = $product->images->count() > 0;
    $hasMetrics = $product->primary_metric_value || ($product->metrics && count($product->metrics) > 0);
    $features = $product->product_features;
    $howItWorks = $product->product_how_it_works;
    $pricing = $product->product_pricing;
    $faq = $product->product_faq;
    $ctaUrl = $product->product_cta_url;
    $ctaLabel = $product->product_cta_label ?? 'Get Started';
    $ctaType = $product->product_data['cta_type'] ?? 'purchase';
    $isDemo = $ctaType === 'demo';
@endphp

@push('styles')
<style>
    /* Prose overrides for rendered markdown (same as blog-detail) */
    .prose { max-width: none; }
    .prose img { border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .prose pre { background: #1e293b; border-radius: 0.5rem; padding: 1rem; overflow-x: auto; }
    .prose code { background: rgba(110, 118, 129, 0.1); padding: 0.2em 0.4em; border-radius: 0.25rem; font-size: 0.875em; }
    .prose pre code { background: transparent; padding: 0; border-radius: 0; font-size: 0.875em; }
    .prose h2 { font-size: 1.875rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; }
    .prose h3 { font-size: 1.5rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; }
    .prose h4 { font-size: 1.25rem; font-weight: 600; margin-top: 1.25rem; margin-bottom: 0.5rem; }
    .prose blockquote { border-left: 4px solid #41EAD4; padding-left: 1rem; font-style: italic; color: #6b7280; }
    .dark .prose blockquote { color: #9ca3af; }
    .prose a { color: #41EAD4; text-decoration: underline; }
    .prose a:hover { color: #6EFCE5; }
    .prose ul, .prose ol { padding-left: 1.5rem; }
    .prose li { margin: 0.5rem 0; }
    .prose table { width: 100%; border-collapse: collapse; margin: 1rem 0; }
    .prose th, .prose td { border: 1px solid #e5e7eb; padding: 0.5rem 1rem; }
    .dark .prose th, .dark .prose td { border-color: #374151; }
    .prose th { background: #f3f4f6; font-weight: 600; }
    .dark .prose th { background: #1f2937; }
</style>
@endpush

@section('content')

{{-- Flash message for access denied redirect --}}
@if(session('error'))
<div class="bg-sunset/10 border-b border-sunset/20 px-6 py-3 text-center">
    <p class="text-sm text-sunset font-medium">{{ session('error') }}</p>
</div>
@endif

{{-- ========== SECTION 1: PRODUCT HERO ========== --}}
<section class="case-study-hero relative min-h-[50vh] flex items-end overflow-hidden"
         style="--accent: {{ $accent }}; --accent-secondary: {{ $accentSecondary }};">
    {{-- Animated gradient background --}}
    <div class="absolute inset-0" style="background: linear-gradient(135deg, #0D1B2A 0%, {{ $accent }}15 30%, #1B3A4B 60%, {{ $accentSecondary }}10 100%); background-size: 200% 200%; animation: gradientShift 8s ease infinite;"></div>
    <div class="absolute inset-0" style="background: linear-gradient(to top, #0D1B2A 0%, transparent 60%);"></div>

    {{-- Decorative accent glow --}}
    <div class="absolute top-1/4 right-1/4 w-96 h-96 rounded-full blur-3xl opacity-15" style="background: {{ $accent }};"></div>

    <div class="relative z-10 max-w-7xl mx-auto px-6 lg:px-8 pb-16 pt-32 w-full">
        {{-- Breadcrumb --}}
        <nav class="flex mb-8 animate-up" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-soft/60 hover:text-teal transition-colors">Home</a></li>
                <li class="text-soft/40">/</li>
                <li><span class="text-soft/60">Products</span></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft/80">{{ $product->title }}</li>
            </ol>
        </nav>

        {{-- Category badge --}}
        <div class="mb-6 animate-up">
            <span class="inline-block px-4 py-1.5 text-sm font-medium rounded-full border"
                  style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">
                {{ $product->category->name }}
            </span>
            @if($product->status === 'in_progress')
            <span class="inline-block px-3 py-1 text-xs font-medium bg-sunset/20 text-sunset rounded-full ml-2">
                In Development
            </span>
            @endif
        </div>

        {{-- Title --}}
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-6 max-w-4xl animate-up">
            {{ $product->title }}
        </h1>

        {{-- Short description --}}
        <p class="text-xl text-soft/70 max-w-2xl mb-8 animate-up">
            {{ $product->short_description }}
        </p>

        {{-- Hero CTA buttons + Primary metric --}}
        <div class="flex flex-wrap items-center gap-4 animate-up">
            @if($isDemo)
            {{-- Demo scheduling CTA --}}
            <button onclick="window.dispatchEvent(new CustomEvent('open-demo-modal', {detail:{plan:''}}))"
                    class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                    style="background: {{ $accent }};">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                {{ $ctaLabel }}
            </button>
            @elseif($pricing && count($pricing) > 0)
            <a href="#pricing"
               class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="background: {{ $accent }};">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"></path></svg>
                {{ $ctaLabel }}
            </a>
            @elseif($ctaUrl)
            <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="background: {{ $accent }};">
                {{ $ctaLabel }}
            </a>
            @endif
            @if($product->project_url)
            <a href="{{ $product->project_url }}" target="_blank" rel="noopener"
               class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-white border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
               style="border-color: {{ $accent }}60;">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Live Demo
            </a>
            @endif
            {{-- Primary metric in hero --}}
            @if($product->primary_metric_value)
            <div class="ml-4 pl-6 border-l border-soft/20">
                <span class="text-3xl font-black" style="color: {{ $accent }};">{{ $product->primary_metric_value }}</span>
                @if($product->primary_metric_label)
                <span class="text-sm text-soft/50 block">{{ $product->primary_metric_label }}</span>
                @endif
            </div>
            @endif
        </div>
    </div>
</section>

@feature('section.product.gallery')
{{-- ========== SECTION 2: SCREENSHOT GALLERY ========== --}}
@if($hasScreenshots)
<section class="py-16 lg:py-24 bg-midnight/95" x-data="projectShowcase">
    <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-10 animate-up">
            <h2 class="text-2xl font-bold text-white mb-2">Product Showcase</h2>
            <p class="text-soft/50 text-sm">Screenshots of the live platform</p>
        </div>

        {{-- Main Carousel --}}
        <div class="relative mb-6 animate-up">
            <div class="swiper screenshot-swiper-main rounded-xl overflow-hidden shadow-2xl" x-ref="mainSwiper">
                <div class="swiper-wrapper">
                    @foreach($product->images as $index => $image)
                    <div class="swiper-slide">
                        <div class="screenshot-slide cursor-pointer" @click="openLightbox({{ $index }})">
                            <img src="{{ Storage::url($image->image_path) }}"
                                 alt="{{ $image->alt_text ?: $product->title . ' screenshot ' . ($index + 1) }}"
                                 class="w-full object-contain bg-gray-900/50"
                                 loading="{{ $index === 0 ? 'eager' : 'lazy' }}">
                        </div>
                    </div>
                    @endforeach
                </div>

                {{-- Navigation arrows --}}
                <button x-ref="swiperPrev"
                        class="absolute left-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full flex items-center justify-center text-white transition-all hover:scale-110"
                        style="background: {{ $accent }}cc;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>
                <button x-ref="swiperNext"
                        class="absolute right-4 top-1/2 -translate-y-1/2 z-20 w-10 h-10 rounded-full flex items-center justify-center text-white transition-all hover:scale-110"
                        style="background: {{ $accent }}cc;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>

                {{-- Pagination --}}
                <div x-ref="swiperPagination" class="swiper-pagination !bottom-4"></div>
            </div>
        </div>

        {{-- Thumbnail strip --}}
        @if($product->images->count() > 1)
        <div class="swiper screenshot-swiper-thumbs animate-up" x-ref="thumbsSwiper">
            <div class="swiper-wrapper justify-center">
                @foreach($product->images as $index => $image)
                <div class="swiper-slide !w-20 !h-14 cursor-pointer">
                    <div class="screenshot-thumb w-full h-full rounded-md overflow-hidden border-2 border-transparent transition-all"
                         style="--accent: {{ $accent }};">
                        <img src="{{ Storage::url($image->image_path) }}"
                             alt="Thumbnail {{ $index + 1 }}"
                             class="w-full h-full object-cover"
                             loading="lazy">
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    {{-- Lightbox --}}
    <div x-show="lightboxOpen" x-cloak
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="screenshot-lightbox fixed inset-0 z-[100] bg-black/95 flex items-center justify-center p-4"
         @keydown.escape.window="closeLightbox()"
         @click.self="closeLightbox()">

        {{-- Close button --}}
        <button @click="closeLightbox()" class="absolute top-6 right-6 text-white/70 hover:text-white transition-colors z-10">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        </button>

        {{-- Lightbox navigation --}}
        <button @click="lightboxPrev()" class="absolute left-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors z-10">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <button @click="lightboxNext()" class="absolute right-4 top-1/2 -translate-y-1/2 text-white/70 hover:text-white transition-colors z-10">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>

        {{-- Lightbox image --}}
        @foreach($product->images as $index => $image)
        <img x-show="lightboxIndex === {{ $index }}"
             src="{{ Storage::url($image->image_path) }}"
             alt="{{ $image->alt_text ?: $product->title }}"
             class="max-w-full max-h-[90vh] object-contain rounded-lg">
        @endforeach

        {{-- Counter --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/50 text-sm">
            <span x-text="lightboxIndex + 1"></span> / {{ $product->images->count() }}
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.product.features')
{{-- ========== SECTION 3: FEATURES GRID ========== --}}
@if($features && count($features) > 0)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-14 animate-up">
            <span class="badge" style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">Features</span>
            <h2 class="section-title text-midnight dark:text-white mt-4">Everything You Need</h2>
            <p class="section-subtitle">Packed with powerful features to get you started quickly</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($features as $index => $feature)
            <div class="p-6 rounded-2xl border border-gray-200 dark:border-soft/5 bg-white dark:bg-ocean/20 hover:border-transparent hover:shadow-lg dark:hover:shadow-2xl transition-all duration-300 group animate-up">
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4 transition-colors duration-300"
                     style="background: {{ $accent }}10; color: {{ $accent }};">
                    <x-product-icon :icon="$feature['icon'] ?? 'star'" class="w-6 h-6" />
                </div>
                <h3 class="text-lg font-bold text-midnight dark:text-white mb-2">{{ $feature['title'] }}</h3>
                <p class="text-sm text-soft-dark/70 dark:text-soft/50 leading-relaxed">{{ $feature['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.product.how_it_works')
{{-- ========== SECTION 4: HOW IT WORKS ========== --}}
@if($howItWorks && count($howItWorks) > 0)
<section class="py-16 lg:py-24 bg-midnight relative overflow-hidden">
    <div class="absolute inset-0 opacity-20" style="background: radial-gradient(ellipse at 30% 50%, {{ $accent }}15, transparent 60%);"></div>

    <div class="max-w-5xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-14 animate-up">
            <span class="badge" style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">How It Works</span>
            <h2 class="section-title text-white mt-4">Get Started in {{ count($howItWorks) }} Simple Steps</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-{{ min(count($howItWorks), 4) }} gap-8">
            @foreach($howItWorks as $index => $step)
            <div class="relative text-center animate-up">
                {{-- Step number --}}
                <div class="w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-5 text-2xl font-black text-midnight"
                     style="background: {{ $accent }};">
                    {{ $index + 1 }}
                </div>

                {{-- Connector line (between steps, not after last) --}}
                @if(!$loop->last)
                <div class="hidden lg:block absolute top-8 left-[calc(50%+2.5rem)] w-[calc(100%-5rem)] h-0.5 opacity-20" style="background: {{ $accent }};"></div>
                @endif

                <h3 class="text-lg font-bold text-white mb-2">{{ $step['title'] }}</h3>
                <p class="text-sm text-soft/50 leading-relaxed">{{ $step['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endfeature

{{-- ========== SECTION 5: TECH STACK & TAGS ========== --}}
@if($product->technologies && count($product->technologies) > 0)
<section class="py-16 lg:py-20 bg-soft-light dark:bg-midnight/80">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="animate-up">
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-6" style="color: {{ $accent }};">Tech Stack</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($product->technologies as $tech)
                <span class="tech-pill-accent px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:-translate-y-0.5"
                      style="background: {{ $accent }}10; color: {{ $accent }}; border: 1px solid {{ $accent }}20;">
                    {{ $tech }}
                </span>
                @endforeach
            </div>
        </div>

        @if($product->tags->count())
        <div class="mt-8 animate-up">
            <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-soft-dark/50 dark:text-soft/40 mb-4">Tags</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($product->tags as $tag)
                <span class="px-3 py-1 text-xs font-medium rounded-full"
                      style="background: {{ $tag->color }}15; color: {{ $tag->color }}; border: 1px solid {{ $tag->color }}25;">
                    {{ $tag->name }}
                </span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</section>
@endif

@feature('section.product.pricing')
{{-- ========== SECTION 6: PRICING ========== --}}
@if($pricing && count($pricing) > 0)
<section id="pricing" class="py-16 lg:py-24 bg-midnight relative overflow-hidden">
    <div class="absolute inset-0 opacity-15" style="background: radial-gradient(ellipse at 70% 30%, {{ $accentSecondary }}20, transparent 60%);"></div>

    <div class="max-w-5xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-14 animate-up">
            <span class="badge" style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">Pricing</span>
            <h2 class="section-title text-white mt-4">Choose Your Plan</h2>
            <p class="section-subtitle">Simple, transparent pricing. No surprises.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-{{ min(count($pricing), 3) }} gap-8 max-w-4xl mx-auto">
            @foreach($pricing as $tier)
            @php $isHighlighted = $tier['highlighted'] ?? false; @endphp
            <div class="relative rounded-2xl p-8 transition-all duration-300 animate-up
                        {{ $isHighlighted
                            ? 'border-2 bg-ocean/30 shadow-xl scale-[1.02]'
                            : 'border border-soft/10 bg-ocean/10 hover:border-soft/20' }}"
                 style="{{ $isHighlighted ? 'border-color: ' . $accent . ';' : '' }}">

                {{-- Popular badge --}}
                @if($isHighlighted)
                <div class="absolute -top-4 left-1/2 -translate-x-1/2">
                    <span class="px-4 py-1.5 text-xs font-bold uppercase tracking-wider rounded-full text-midnight"
                          style="background: {{ $accent }};">
                        Most Popular
                    </span>
                </div>
                @endif

                <div class="text-center mb-8">
                    <h3 class="text-xl font-bold text-white mb-2">{{ $tier['name'] }}</h3>
                    @if(!empty($tier['description']))
                    <p class="text-sm text-soft/50 mb-4">{{ $tier['description'] }}</p>
                    @endif
                    <div class="flex items-baseline justify-center gap-1">
                        @if(!empty($tier['price']) && $tier['price'] !== null)
                        <span class="text-5xl font-black" style="color: {{ $isHighlighted ? $accent : 'white' }};">
                            ${{ $tier['price'] }}
                        </span>
                        <span class="text-soft/50 text-sm">{{ $tier['billing_period'] ?? '' }}</span>
                        @else
                        <span class="text-4xl font-black" style="color: {{ $isHighlighted ? $accent : 'white' }};">Custom</span>
                        @endif
                    </div>
                </div>

                {{-- Features list --}}
                @if(!empty($tier['features']))
                <ul class="space-y-3 mb-8">
                    @foreach($tier['features'] as $pricingFeature)
                    <li class="flex items-start gap-3 text-sm">
                        <svg class="w-5 h-5 shrink-0 mt-0.5" style="color: {{ $accent }};" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <span class="text-soft/70">{{ $pricingFeature }}</span>
                    </li>
                    @endforeach
                </ul>
                @endif

                {{-- CTA — demo scheduling OR Safepay checkout depending on cta_type --}}
                @if($isDemo)
                <button onclick="window.dispatchEvent(new CustomEvent('open-demo-modal', {detail:{plan:'{{ addslashes($tier['name']) }}'}}))"
                        class="block w-full text-center py-3.5 rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 cursor-pointer
                               {{ $isHighlighted ? 'text-midnight' : 'text-white border-2' }}"
                        style="{{ $isHighlighted ? 'background: ' . $accent . ';' : 'border-color: ' . $accent . '60;' }}">
                    <svg class="w-4 h-4 inline -mt-0.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $tier['cta_label'] ?? $ctaLabel }}
                </button>
                @else
                <form action="{{ route('checkout.initiate', [$product->slug, $loop->index]) }}" method="POST">
                    @csrf
                    <input type="email" name="customer_email" required
                           class="w-full mb-3 px-4 py-2.5 rounded-lg text-sm bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 focus:outline-none"
                           placeholder="Enter your email to purchase">
                    <button type="submit"
                       class="block w-full text-center py-3.5 rounded-xl font-semibold transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5 cursor-pointer
                              {{ $isHighlighted ? 'text-midnight' : 'text-white border-2' }}"
                       style="{{ $isHighlighted ? 'background: ' . $accent . ';' : 'border-color: ' . $accent . '60;' }}">
                        {{ $tier['cta_label'] ?? $ctaLabel }}
                    </button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endfeature

{{-- ========== SECTION 7: DESCRIPTION / OVERVIEW ========== --}}
@if($product->description && !$features)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="animate-up">
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-8" style="color: {{ $accent }};">Features & Overview</h2>
            <div class="prose prose-lg dark:prose-invert max-w-none">
                {!! $product->rendered_description !!}
            </div>
        </div>
    </div>
</section>
@endif

@feature('section.product.metrics')
{{-- ========== SECTION 8: KEY METRICS ========== --}}
@if($hasMetrics)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight/80 relative overflow-hidden">
    <div class="absolute inset-0 opacity-30" style="background: radial-gradient(ellipse at 50% 50%, {{ $accent }}10, transparent 70%);"></div>

    <div class="max-w-5xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 animate-up">
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-3" style="color: {{ $accent }};">Impact</h2>
            <p class="text-2xl font-bold text-midnight dark:text-white">Key Metrics</p>
        </div>

        {{-- Primary metric --}}
        @if($product->primary_metric_value)
        <div class="text-center mb-12 animate-up" x-data="metricCountUp" data-value="{{ $product->primary_metric_value }}">
            <div class="metric-hero-value" x-text="displayValue">
                {{ $product->primary_metric_value }}
            </div>
            @if($product->primary_metric_label)
            <p class="text-sm font-semibold uppercase tracking-[0.15em] text-soft-dark/50 dark:text-soft/50 mt-2">{{ $product->primary_metric_label }}</p>
            @endif
        </div>
        @endif

        {{-- Secondary metrics grid --}}
        @if($product->metrics && count($product->metrics) > 0)
        <div class="grid grid-cols-2 md:grid-cols-{{ min(count($product->metrics), 4) }} gap-6">
            @foreach($product->metrics as $index => $metric)
            @php
                $valLen  = mb_strlen($metric['value']);
                $valSize = $valLen <= 8  ? 'text-3xl lg:text-4xl'
                         : ($valLen <= 20 ? 'text-xl lg:text-2xl'
                         : 'text-sm lg:text-base');
            @endphp
            <div class="text-center p-6 rounded-xl border border-gray-200 dark:border-soft/5 bg-white dark:bg-ocean/20 animate-up"
                 x-data="metricCountUp" data-value="{{ $metric['value'] }}">
                <div class="font-black mb-2 leading-snug {{ $valSize }} {{ $index % 2 === 0 ? 'text-teal' : 'text-sunset' }}" x-text="displayValue">{{ $metric['value'] }}</div>
                <p class="text-xs font-semibold uppercase tracking-wider text-soft-dark dark:text-soft/60 mt-1">{{ $metric['label'] }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif
@endfeature

@feature('section.product.faq')
{{-- ========== SECTION 9: FAQ ========== --}}
@if($faq && count($faq) > 0)
<section class="py-16 lg:py-24 bg-white dark:bg-midnight" x-data="{ openFaq: null }">
    <div class="max-w-3xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-14 animate-up">
            <span class="badge" style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">FAQ</span>
            <h2 class="section-title text-midnight dark:text-white mt-4">Frequently Asked Questions</h2>
        </div>

        <div class="space-y-4">
            @foreach($faq as $index => $item)
            <div class="border border-gray-200 dark:border-soft/10 rounded-xl overflow-hidden animate-up">
                <button @click="openFaq = openFaq === {{ $index }} ? null : {{ $index }}"
                        class="w-full flex items-center justify-between px-6 py-5 text-left transition-colors"
                        :class="openFaq === {{ $index }} ? 'bg-gray-50 dark:bg-ocean/20' : 'hover:bg-gray-50 dark:hover:bg-ocean/10'">
                    <span class="text-base font-semibold text-midnight dark:text-white pr-4">{{ $item['question'] }}</span>
                    <svg class="w-5 h-5 shrink-0 transition-transform duration-300 text-soft-dark/40 dark:text-soft/40"
                         :class="openFaq === {{ $index }} ? 'rotate-180' : ''"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
                <div x-show="openFaq === {{ $index }}"
                     x-collapse
                     x-cloak>
                    <div class="px-6 pb-5 text-sm text-soft-dark/70 dark:text-soft/50 leading-relaxed">
                        {{ $item['answer'] }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.product.cta_banner')
{{-- ========== SECTION 10: CTA BANNER ========== --}}
@if($isDemo || $ctaUrl || $product->project_url)
<section class="py-16 lg:py-20 relative overflow-hidden"
         style="background: linear-gradient(135deg, {{ $accent }}20 0%, #0D1B2A 50%, {{ $accentSecondary }}15 100%);">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center relative z-10">
        <div class="animate-up">
            @if($isDemo)
            <h2 class="text-3xl font-bold text-white mb-4">See it in action?</h2>
            <p class="text-soft/60 mb-8">Book a 30-minute demo and see how {{ $product->title }} can work for your team.</p>
            <div class="flex flex-wrap justify-center gap-4">
                <button onclick="window.dispatchEvent(new CustomEvent('open-demo-modal', {detail:{plan:''}}))"
                        class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                        style="background: {{ $accent }};">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    {{ $ctaLabel }}
                </button>
                @if($pricing && count($pricing) > 0)
                <a href="#pricing"
                   class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-white border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                   style="border-color: {{ $accent }}60;">
                    View Pricing
                </a>
                @endif
            </div>
            @else
            <h2 class="text-3xl font-bold text-white mb-4">Ready to get started?</h2>
            <p class="text-soft/60 mb-8">Get {{ $product->title }} today and launch in minutes.</p>
            <div class="flex flex-wrap justify-center gap-4">
                @if($ctaUrl)
                <a href="{{ $ctaUrl }}" target="_blank" rel="noopener"
                   class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                   style="background: {{ $accent }};">
                    {{ $ctaLabel }}
                </a>
                @endif
                @if($product->project_url)
                <a href="{{ $product->project_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-white border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                   style="border-color: {{ $accent }}60;">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Live Demo
                </a>
                @endif
            </div>
            @endif
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.product.more_products')
{{-- ========== SECTION 11: MORE PRODUCTS ========== --}}
@if($relatedProducts->count())
<section class="py-16 lg:py-24 bg-white dark:bg-ocean/10">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-12 animate-up">
            <h2 class="text-2xl font-bold text-midnight dark:text-white">More Products</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($relatedProducts as $index => $relatedProduct)
            @php
                $relAccent = $relatedProduct->color_primary ?? '#41EAD4';
            @endphp
            <article class="animate-up">
                <a href="{{ route('products.show', $relatedProduct->slug) }}"
                   class="block card card-hover relative overflow-hidden h-full rounded-2xl">

                    {{-- Landscape thumbnail --}}
                    @if($relatedProduct->featured_image)
                    <div class="relative overflow-hidden">
                        <img src="{{ Storage::url($relatedProduct->featured_image) }}"
                             alt="{{ $relatedProduct->title }}"
                             class="w-full aspect-video object-cover group-hover:scale-105 transition-transform duration-500"
                             loading="lazy">
                        @if($relatedProduct->color_primary)
                        <div class="absolute bottom-0 left-0 right-0 h-1" style="background: {{ $relatedProduct->color_primary }};"></div>
                        @else
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-teal to-sunset"></div>
                        @endif
                    </div>
                    @endif

                    <div class="p-6">
                        @if($relatedProduct->category)
                        <span class="inline-block px-3 py-1 text-xs font-medium rounded-full mb-3"
                              style="background: {{ $relatedProduct->category->color }}15; color: {{ $relatedProduct->category->color }};">
                            {{ $relatedProduct->category->name }}
                        </span>
                        @endif

                        <h3 class="text-lg font-bold text-midnight dark:text-white mb-2 line-clamp-2">
                            {{ $relatedProduct->title }}
                        </h3>

                        <p class="text-sm text-soft-dark dark:text-soft/60 mb-4 line-clamp-2">
                            {{ $relatedProduct->short_description }}
                        </p>

                        @if($relatedProduct->technologies)
                        <div class="flex flex-wrap gap-1.5">
                            @foreach(array_slice($relatedProduct->technologies, 0, 3) as $tech)
                            <span class="px-2 py-0.5 text-xs rounded bg-soft/10 dark:bg-soft/5 text-soft-dark dark:text-soft/60">
                                {{ $tech }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </a>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif
@endfeature

{{-- ========== DEMO SCHEDULING MODAL ========== --}}
@if($isDemo)
<div x-data="demoScheduler('{{ $product->slug }}', {{ $product->product_data['slot_duration'] ?? 30 }})"
     @open-demo-modal.window="openModal($event.detail.plan)"
     @keydown.escape.window="closeModal()"
     x-cloak>

    {{-- Modal backdrop --}}
    <div x-show="open"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[200] bg-midnight/80 backdrop-blur-sm flex items-center justify-center p-4"
         @click.self="closeModal()">

        <div x-show="open"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 scale-100"
             x-transition:leave-end="opacity-0 scale-95"
             class="relative bg-midnight border border-soft/10 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header --}}
            <div class="flex items-center justify-between p-6 border-b border-soft/10">
                <div>
                    <h2 class="text-xl font-bold text-white">Schedule a Demo</h2>
                    <p class="text-sm text-soft/50 mt-0.5">{{ $product->title }} · <span x-text="duration"></span> min</p>
                </div>
                <button @click="closeModal()" class="text-soft/40 hover:text-white transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Step indicators --}}
            <div class="flex items-center gap-0 px-6 py-4 border-b border-soft/10" x-show="step < 4">
                <template x-for="(label, i) in ['Pick a Date', 'Pick a Time', 'Your Details']" :key="i">
                    <div class="flex items-center flex-1">
                        <div class="flex items-center gap-2">
                            <div class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-colors"
                                 :class="step > i + 1 ? 'bg-teal text-midnight' : (step === i + 1 ? 'border-2 border-teal text-teal' : 'bg-soft/10 text-soft/30')">
                                <span x-show="step <= i + 1" x-text="i + 1"></span>
                                <svg x-show="step > i + 1" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <span class="text-xs font-medium hidden sm:block transition-colors"
                                  :class="step === i + 1 ? 'text-white' : (step > i + 1 ? 'text-teal' : 'text-soft/30')"
                                  x-text="label"></span>
                        </div>
                        <div x-show="i < 2" class="flex-1 h-px bg-soft/10 mx-3"></div>
                    </div>
                </template>
            </div>

            {{-- Step 1: Date Picker --}}
            <div x-show="step === 1" class="p-6">
                <div x-show="loadingSlots" class="text-center py-12 text-soft/40">
                    <svg class="w-8 h-8 animate-spin mx-auto mb-3" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    <p class="text-sm">Loading availability…</p>
                </div>

                <div x-show="!loadingSlots">
                    {{-- Month navigation --}}
                    <div class="flex items-center justify-between mb-5">
                        <button @click="prevMonth()" :disabled="!canGoPrev()"
                                class="p-2 rounded-lg text-soft/50 hover:text-teal hover:bg-teal/10 disabled:opacity-30 disabled:cursor-not-allowed transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <span class="font-bold text-white" x-text="monthLabel()"></span>
                        <button @click="nextMonth()"
                                class="p-2 rounded-lg text-soft/50 hover:text-teal hover:bg-teal/10 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    {{-- Day headers --}}
                    <div class="grid grid-cols-7 mb-2">
                        <template x-for="d in ['Mon','Tue','Wed','Thu','Fri','Sat','Sun']">
                            <div class="text-center text-xs font-medium text-soft/40 py-1" x-text="d"></div>
                        </template>
                    </div>

                    {{-- Calendar grid --}}
                    <div class="grid grid-cols-7 gap-1">
                        {{-- Empty cells before month start --}}
                        <template x-for="_ in monthStartOffset()" :key="'blank-' + _">
                            <div></div>
                        </template>
                        {{-- Day cells --}}
                        <template x-for="day in daysInMonth()" :key="day">
                            <button @click="selectDate(day)"
                                    :disabled="!isDayAvailable(day)"
                                    :class="{
                                        'bg-teal text-midnight font-bold': isSelectedDay(day),
                                        'hover:bg-teal/10 hover:text-teal text-white cursor-pointer': isDayAvailable(day) && !isSelectedDay(day),
                                        'text-soft/20 cursor-not-allowed': !isDayAvailable(day),
                                    }"
                                    class="h-10 w-full rounded-lg text-sm transition-colors">
                                <span x-text="day"></span>
                            </button>
                        </template>
                    </div>

                    <p x-show="Object.keys(slots).length === 0 && !loadingSlots"
                       class="text-center text-sm text-soft/40 mt-6">
                        No availability this month. Try the next month.
                    </p>
                </div>
            </div>

            {{-- Step 2: Time Slot Picker --}}
            <div x-show="step === 2" class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-soft/60">
                        <span x-text="formatSelectedDate()"></span>
                        <button @click="step = 1; selectedTime = ''" class="ml-2 text-teal hover:underline text-xs">Change date</button>
                    </p>
                </div>

                <div class="grid grid-cols-3 sm:grid-cols-4 gap-3">
                    <template x-for="slot in slotsForSelectedDate()" :key="slot">
                        <button @click="selectTime(slot)"
                                :class="selectedTime === slot ? 'bg-teal text-midnight font-bold' : 'border border-soft/10 text-white hover:border-teal hover:text-teal'"
                                class="py-2.5 px-3 rounded-xl text-sm transition-colors text-center">
                            <div x-text="formatTime(slot)"></div>
                            <div class="text-xs opacity-60 mt-0.5" x-text="localTime(slot)"></div>
                        </button>
                    </template>
                </div>

                <p x-show="slotsForSelectedDate().length === 0" class="text-center text-sm text-soft/40 mt-6">
                    No slots available on this date.
                </p>
            </div>

            {{-- Step 3: Details Form --}}
            <div x-show="step === 3" class="p-6">
                {{-- Summary bar --}}
                <div class="bg-ocean/30 border border-soft/10 rounded-xl p-4 mb-6 text-sm">
                    <div class="flex items-center gap-2 text-soft/70">
                        <svg class="w-4 h-4 text-teal shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span><span class="text-white font-medium" x-text="formatSelectedDate()"></span> at <span class="text-teal font-medium" x-text="formatTime(selectedTime)"></span> · <span x-text="duration"></span> min</span>
                        <button @click="step = 2" class="ml-auto text-xs text-soft/40 hover:text-teal">Change</button>
                    </div>
                </div>

                <form @submit.prevent="submitBooking" class="space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-soft/70 mb-1.5">Full Name <span class="text-sunset">*</span></label>
                            <input type="text" x-model="form.name" required
                                   class="w-full px-4 py-2.5 rounded-xl bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 text-sm focus:outline-none focus:border-teal transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-soft/70 mb-1.5">Email <span class="text-sunset">*</span></label>
                            <input type="email" x-model="form.email" required
                                   class="w-full px-4 py-2.5 rounded-xl bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 text-sm focus:outline-none focus:border-teal transition-colors">
                        </div>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-soft/70 mb-1.5">Company</label>
                            <input type="text" x-model="form.company"
                                   class="w-full px-4 py-2.5 rounded-xl bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 text-sm focus:outline-none focus:border-teal transition-colors">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-soft/70 mb-1.5">Plan Interest</label>
                            <input type="text" x-model="form.plan_interest"
                                   class="w-full px-4 py-2.5 rounded-xl bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 text-sm focus:outline-none focus:border-teal transition-colors"
                                   placeholder="e.g. Professional">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-soft/70 mb-1.5">Message (optional)</label>
                        <textarea x-model="form.message" rows="3"
                                  class="w-full px-4 py-2.5 rounded-xl bg-ocean/30 border border-soft/10 text-white placeholder-soft/30 text-sm focus:outline-none focus:border-teal resize-none transition-colors"
                                  placeholder="Anything you'd like us to know before the demo…"></textarea>
                    </div>

                    <p x-show="formError" x-text="formError" class="text-sunset text-sm"></p>

                    <button type="submit" :disabled="submitting"
                            class="w-full py-3.5 rounded-xl font-semibold text-midnight transition-all hover:shadow-lg hover:-translate-y-0.5 disabled:opacity-60 disabled:cursor-not-allowed"
                            style="background: {{ $accent }};">
                        <span x-show="!submitting">Confirm Booking</span>
                        <span x-show="submitting" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Booking…
                        </span>
                    </button>
                </form>
            </div>

            {{-- Step 4: Confirmation --}}
            <div x-show="step === 4" class="p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-teal/10 flex items-center justify-center mx-auto mb-5">
                    <svg class="w-8 h-8 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-2xl font-bold text-white mb-2">You're booked!</h3>
                <p class="text-soft/60 mb-1" x-text="confirmedTime"></p>
                <p class="text-sm text-soft/40 mb-8">Check your email for a confirmation + calendar invite.</p>
                <button @click="closeModal()"
                        class="px-8 py-3 rounded-xl font-semibold text-midnight"
                        style="background: {{ $accent }};">
                    Done
                </button>
            </div>

        </div>
    </div>
</div>
@endif

@push('scripts')
@if($isDemo)
<script>
function demoScheduler(productSlug, duration) {
    return {
        open: false,
        step: 1,
        duration: duration,
        productSlug: productSlug,

        // Calendar state
        viewYear: new Date().getFullYear(),
        viewMonth: new Date().getMonth() + 1, // 1-12
        selectedDate: null,
        selectedTime: '',
        slots: {},
        loadingSlots: false,

        // Form
        form: { name: '', email: '', company: '', plan_interest: '', message: '' },
        submitting: false,
        formError: '',
        confirmedTime: '',

        openModal(plan) {
            this.open = true;
            this.step = 1;
            this.selectedDate = null;
            this.selectedTime = '';
            this.form = { name: '', email: '', company: '', plan_interest: plan || '', message: '' };
            this.formError = '';
            this.confirmedTime = '';
            document.body.style.overflow = 'hidden';
            this.fetchSlots();
        },

        closeModal() {
            this.open = false;
            document.body.style.overflow = '';
        },

        // ── Calendar helpers ─────────────────────────────────────────────

        monthLabel() {
            return new Date(this.viewYear, this.viewMonth - 1, 1)
                .toLocaleString('default', { month: 'long', year: 'numeric' });
        },

        daysInMonth() {
            return new Date(this.viewYear, this.viewMonth, 0).getDate();
        },

        monthStartOffset() {
            // Day of week for 1st of month, Mon=0
            let d = new Date(this.viewYear, this.viewMonth - 1, 1).getDay();
            return d === 0 ? 6 : d - 1; // Convert Sun-based to Mon-based
        },

        isDayAvailable(day) {
            const key = `${this.viewYear}-${String(this.viewMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            return this.slots[key] && this.slots[key].length > 0;
        },

        isSelectedDay(day) {
            if (!this.selectedDate) return false;
            const key = `${this.viewYear}-${String(this.viewMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            return this.selectedDate === key;
        },

        canGoPrev() {
            const now = new Date();
            return this.viewYear > now.getFullYear() || this.viewMonth > now.getMonth() + 1;
        },

        prevMonth() {
            if (!this.canGoPrev()) return;
            if (this.viewMonth === 1) { this.viewMonth = 12; this.viewYear--; }
            else this.viewMonth--;
            this.fetchSlots();
        },

        nextMonth() {
            if (this.viewMonth === 12) { this.viewMonth = 1; this.viewYear++; }
            else this.viewMonth++;
            this.fetchSlots();
        },

        selectDate(day) {
            if (!this.isDayAvailable(day)) return;
            const key = `${this.viewYear}-${String(this.viewMonth).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            this.selectedDate = key;
            this.selectedTime = '';
            this.step = 2;
        },

        formatSelectedDate() {
            if (!this.selectedDate) return '';
            const d = new Date(this.selectedDate + 'T00:00:00');
            return d.toLocaleDateString('default', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        },

        // ── Time slot helpers ────────────────────────────────────────────

        slotsForSelectedDate() {
            if (!this.selectedDate) return [];
            return this.slots[this.selectedDate] || [];
        },

        formatTime(slot) {
            if (!slot) return '';
            const [h, m] = slot.split(':');
            const d = new Date(); d.setHours(+h, +m);
            return d.toLocaleTimeString('default', { hour: 'numeric', minute: '2-digit', timeZoneName: 'short' })
                .replace(/\s[A-Z]{3,}$/, ' PKT'); // Normalise to PKT display
        },

        localTime(slot) {
            if (!slot) return '';
            // Convert PKT time to user's local timezone for display
            const [h, m] = slot.split(':');
            const pktOffset = 5 * 60; // PKT is UTC+5
            const localOffset = new Date().getTimezoneOffset(); // local offset in minutes (negative)
            const diffMin = -localOffset - pktOffset;
            if (diffMin === 0) return ''; // Same timezone
            const localH = +h + Math.floor(diffMin / 60);
            const localM = +m + (diffMin % 60);
            const d = new Date(); d.setHours(localH, localM);
            return d.toLocaleTimeString('default', { hour: 'numeric', minute: '2-digit' });
        },

        selectTime(slot) {
            this.selectedTime = slot;
            this.step = 3;
        },

        // ── API calls ────────────────────────────────────────────────────

        async fetchSlots() {
            this.loadingSlots = true;
            const month = `${this.viewYear}-${String(this.viewMonth).padStart(2,'0')}`;
            try {
                const res = await fetch(`/api/demo/slots?month=${month}&product_slug=${this.productSlug}`);
                if (res.ok) {
                    this.slots = await res.json();
                }
            } catch(e) {
                console.error('Failed to fetch slots', e);
            } finally {
                this.loadingSlots = false;
            }
        },

        async submitBooking() {
            this.submitting = true;
            this.formError = '';
            const scheduledAt = `${this.selectedDate} ${this.selectedTime}`;
            try {
                const res = await fetch('/api/demo/book', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        ...this.form,
                        scheduled_at: scheduledAt,
                        product_slug: this.productSlug,
                    }),
                });
                const data = await res.json();
                if (data.success) {
                    this.confirmedTime = data.scheduled_formatted;
                    this.step = 4;
                } else {
                    this.formError = data.message || 'Something went wrong. Please try again.';
                }
            } catch(e) {
                this.formError = 'Network error. Please try again.';
            } finally {
                this.submitting = false;
            }
        },

        // ── Init ─────────────────────────────────────────────────────────

        init() {
            // Pre-fetch slots for current month on page load
            this.fetchSlots();
        },
    };
}
</script>
@endif
@endpush

@endsection
