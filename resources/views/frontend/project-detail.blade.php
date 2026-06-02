@extends('layouts.app')

@section('title', $project->title . ' - Portfolio')
@section('description', $project->short_description)

@php
    $accent = $project->color_primary ?? '#41EAD4';
    $accentSecondary = $project->color_secondary ?? '#FF6B35';
    $hasNarrative = $project->challenge || $project->solution || $project->results;
    $hasMetrics = $project->primary_metric_value || ($project->metrics && count($project->metrics) > 0);
    $hasScreenshots = $project->images->count() > 0;

    if (!function_exists('formatCaseStudyText')) {
    if (!function_exists('formatResultsText')) {
        function formatResultsText(string $text): string {
            $text = str_replace(["\r\n", "\r"], "\n", $text);
            $paragraphs = preg_split('/\n{2,}/', trim($text));
            $items = array_filter(array_map('trim', $paragraphs));
            $output = '<div class="cs-results-grid">';
            foreach ($items as $item) {
                if ($item === '') continue;
                $output .= '<div class="cs-result-card">';
                $output .= '<div class="cs-result-check"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg></div>';
                $output .= '<div class="cs-result-body">';
                $output .= '<p class="cs-result-lead">' . e($item) . '</p>';
                $output .= '</div>';
                $output .= '</div>';
            }
            $output .= '</div>';
            return $output;
        }
    }

    function formatCaseStudyText(string $text): string {
        // Normalize Windows CRLF to Unix LF so paragraph splitting works reliably
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $output = '';
        $paragraphs = preg_split('/\n{2,}/', trim($text));
        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') continue;
            $lines = preg_split('/\r?\n/', $para, 2);
            $firstLine = trim($lines[0]);
            $body = isset($lines[1]) ? trim($lines[1]) : '';

            // Numbered heading: "1. Title text"
            if (preg_match('/^(\d+)\.\s+(.+)$/', $firstLine, $m)) {
                $output .= '<div class="cs-numbered-item">';
                $output .= '<div class="cs-numbered-header">';
                $output .= '<span class="cs-point-num">' . e($m[1]) . '</span>';
                $output .= '<h3 class="cs-point-title">' . e($m[2]) . '</h3>';
                $output .= '</div>';
                if ($body !== '') {
                    $output .= '<p class="cs-point-body">' . nl2br(e($body)) . '</p>';
                }
                $output .= '</div>';
            }
            // Plain subheading: short first line (no trailing period) followed by body on next line
            elseif ($body !== '' && mb_strlen($firstLine) <= 80 && !str_ends_with($firstLine, '.') && !str_ends_with($firstLine, '!') && !str_ends_with($firstLine, '?')) {
                $output .= '<div class="cs-subblock">';
                $output .= '<h3 class="cs-subheading">' . e($firstLine) . '</h3>';
                $output .= '<p class="cs-body">' . nl2br(e($body)) . '</p>';
                $output .= '</div>';
            }
            // Standalone subheading: whole paragraph is a short title-like line
            elseif ($body === '' && mb_strlen($firstLine) <= 80 && !str_ends_with($firstLine, '.') && !str_ends_with($firstLine, '!') && !str_ends_with($firstLine, '?') && str_word_count($firstLine) <= 8) {
                $output .= '<h3 class="cs-subheading mt-6">' . e($firstLine) . '</h3>';
            }
            // Multi-line block: multiple single-newline-separated lines → styled sub-list
            elseif (str_contains($para, "\n")) {
                $lines = array_filter(array_map('trim', explode("\n", $para)));
                $output .= '<ul class="cs-inline-list">';
                foreach ($lines as $line) {
                    $output .= '<li>' . e($line) . '</li>';
                }
                $output .= '</ul>';
            }
            // Regular paragraph
            else {
                $output .= '<p class="cs-body">' . nl2br(e($para)) . '</p>';
            }
        }
        return $output;
    }
    } // end function_exists guard
@endphp

@section('content')

{{-- ========== SECTION 1: IMMERSIVE HERO ========== --}}
<section class="case-study-hero relative min-h-[60vh] flex items-end overflow-hidden"
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
                <li><a href="{{ route('portfolio.index') }}" class="text-soft/60 hover:text-teal transition-colors">Portfolio</a></li>
                <li class="text-soft/40">/</li>
                <li><a href="{{ route('portfolio.category', $project->category->slug) }}" class="text-soft/60 hover:text-teal transition-colors">{{ $project->category->name }}</a></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft/80">{{ $project->title }}</li>
            </ol>
        </nav>

        {{-- Category badge --}}
        <div class="mb-6 animate-up">
            <span class="inline-block px-4 py-1.5 text-sm font-medium rounded-full border"
                  style="color: {{ $accent }}; border-color: {{ $accent }}40; background: {{ $accent }}10;">
                {{ $project->category->name }}
            </span>
            @if($project->status === 'in_progress')
            <span class="inline-block px-3 py-1 text-xs font-medium bg-sunset/20 text-sunset rounded-full ml-2">
                In Progress
            </span>
            @endif
        </div>

        {{-- Title --}}
        <h1 class="text-4xl sm:text-5xl lg:text-6xl font-black text-white leading-tight mb-6 max-w-4xl animate-up">
            {{ $project->title }}
        </h1>

        {{-- Short description --}}
        <p class="text-xl text-soft/70 max-w-2xl mb-8 animate-up">
            {{ $project->short_description }}
        </p>

        {{-- Scroll indicator --}}
        <div class="animate-up">
            <div class="w-6 h-10 border-2 border-soft/30 rounded-full flex justify-center pt-2">
                <div class="w-1 h-3 bg-soft/50 rounded-full" style="animation: float 2s ease-in-out infinite;"></div>
            </div>
        </div>
    </div>
</section>

{{-- ========== SECTION 2: QUICK STATS BAR ========== --}}
@if($project->role || $project->duration || $project->client_name)
<section class="py-5 bg-midnight border-y border-soft/5">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex flex-wrap items-center justify-center gap-8 lg:gap-16">
            @if($project->role)
            <div class="stats-bar-item text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-soft/40 mb-1">Role</p>
                <p class="text-sm font-medium text-soft/90">{{ $project->role }}</p>
            </div>
            @endif

            @if($project->duration)
            <div class="stats-bar-item text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-soft/40 mb-1">Duration</p>
                <p class="text-sm font-medium text-soft/90">{{ $project->duration }}</p>
            </div>
            @endif

            @if($project->client_name)
            <div class="stats-bar-item text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-soft/40 mb-1">Built For</p>
                <p class="text-sm font-medium text-soft/90">{{ $project->client_name }}</p>
            </div>
            @endif

            <div class="stats-bar-item text-center">
                <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-soft/40 mb-1">Category</p>
                <p class="text-sm font-medium" style="color: {{ $accent }};">{{ $project->category->name }}</p>
            </div>
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 3: SCREENSHOT SHOWCASE ========== --}}
@if($hasScreenshots)
<section class="py-16 lg:py-24 bg-midnight/95" x-data="projectShowcase">
    <div class="max-w-6xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-10 animate-up">
            <h2 class="text-2xl font-bold text-white mb-2">Project Showcase</h2>
            <p class="text-soft/50 text-sm">Full-page screenshots of the live application</p>
        </div>

        {{-- Main Carousel --}}
        <div class="relative mb-6 animate-up">
            <div class="swiper screenshot-swiper-main rounded-xl overflow-hidden shadow-2xl" x-ref="mainSwiper">
                <div class="swiper-wrapper">
                    @foreach($project->images as $index => $image)
                    <div class="swiper-slide">
                        <div class="screenshot-slide cursor-pointer" @click="openLightbox({{ $index }})">
                            <img src="{{ Storage::url($image->image_path) }}"
                                 alt="{{ $image->alt_text ?: $project->title . ' screenshot ' . ($index + 1) }}"
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
        @if($project->images->count() > 1)
        <div class="swiper screenshot-swiper-thumbs animate-up" x-ref="thumbsSwiper">
            <div class="swiper-wrapper justify-center">
                @foreach($project->images as $index => $image)
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
        @foreach($project->images as $index => $image)
        <img x-show="lightboxIndex === {{ $index }}"
             src="{{ Storage::url($image->image_path) }}"
             alt="{{ $image->alt_text ?: $project->title }}"
             class="max-w-full max-h-[90vh] object-contain rounded-lg">
        @endforeach

        {{-- Counter --}}
        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 text-white/50 text-sm">
            <span x-text="lightboxIndex + 1"></span> / {{ $project->images->count() }}
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 4: THE CHALLENGE ========== --}}
@if($project->challenge)
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 animate-up relative overflow-hidden">
        <span class="case-study-number" style="color: {{ $accent }};">01</span>
        <div class="relative z-10">
            <h2 class="text-2xl lg:text-3xl font-black text-midnight dark:text-soft-light mb-8 pb-5 border-b border-soft/10 dark:border-soft/5">The Challenge</h2>
            <div class="cs-content">
                {!! formatCaseStudyText($project->challenge) !!}
            </div>
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 5: THE SOLUTION ========== --}}
@if($project->solution)
<section class="py-16 lg:py-24 bg-white dark:bg-midnight-light">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 animate-up relative overflow-hidden">
        <span class="case-study-number" style="color: {{ $accent }};">02</span>
        <div class="relative z-10">
            <h2 class="text-2xl lg:text-3xl font-black text-midnight dark:text-soft-light mb-8 pb-5 border-b border-soft/10 dark:border-soft/5">The Solution</h2>
            <div class="cs-content">
                {!! formatCaseStudyText($project->solution) !!}
            </div>
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 6: KEY METRICS ========== --}}
@if($hasMetrics)
<section class="py-16 lg:py-24 bg-midnight relative overflow-hidden">
    {{-- Subtle gradient bg --}}
    <div class="absolute inset-0 opacity-30" style="background: radial-gradient(ellipse at 50% 50%, {{ $accent }}10, transparent 70%);"></div>

    <div class="max-w-5xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center mb-12 animate-up">
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-3" style="color: {{ $accent }};">Impact</h2>
            <p class="text-2xl font-bold text-white">Key Metrics</p>
        </div>

        {{-- Primary metric --}}
        @if($project->primary_metric_value)
        <div class="text-center mb-12 animate-up" x-data="metricCountUp" data-value="{{ $project->primary_metric_value }}">
            <div class="metric-hero-value" x-text="displayValue" style="background: linear-gradient(135deg, {{ $accent }} 0%, {{ $accentSecondary }} 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                {{ $project->primary_metric_value }}
            </div>
            @if($project->primary_metric_label)
            <p class="text-sm font-semibold uppercase tracking-[0.15em] text-soft/50 mt-2">{{ $project->primary_metric_label }}</p>
            @endif
        </div>
        @endif

        {{-- Secondary metrics grid --}}
        @if($project->metrics && count($project->metrics) > 0)
        <div class="grid grid-cols-2 md:grid-cols-{{ min(count($project->metrics), 4) }} gap-6">
            @foreach($project->metrics as $index => $metric)
            @php $accentColor = $index % 2 === 0 ? $accent : $accentSecondary; @endphp
            <div class="text-center p-6 rounded-xl border border-soft/5 bg-ocean/20 animate-up"
                 x-data="metricCountUp" data-value="{{ $metric['value'] }}">
                <div class="text-3xl lg:text-4xl font-black mb-2" x-text="displayValue"
                     style="color: {{ $accentColor }};">{{ $metric['value'] }}</div>
                <p class="text-xs font-semibold uppercase tracking-wider text-soft/50">{{ $metric['label'] }}</p>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</section>
@endif

{{-- ========== SECTION 7: TECHNOLOGIES & STACK ========== --}}
@if($project->technologies && count($project->technologies) > 0)
<section class="py-16 lg:py-20 bg-soft-light dark:bg-midnight/80">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="animate-up">
            <h2 class="text-sm font-bold uppercase tracking-[0.2em] mb-6" style="color: {{ $accent }};">Tech Stack</h2>
            <div class="flex flex-wrap gap-3">
                @foreach($project->technologies as $tech)
                <span class="tech-pill-accent px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200 hover:-translate-y-0.5"
                      style="background: {{ $accent }}10; color: {{ $accent }}; border: 1px solid {{ $accent }}20;">
                    {{ $tech }}
                </span>
                @endforeach
            </div>
        </div>

        @if($project->tags->count())
        <div class="mt-8 animate-up">
            <h3 class="text-sm font-bold uppercase tracking-[0.2em] text-soft-dark/50 dark:text-soft/40 mb-4">Tags</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($project->tags as $tag)
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

{{-- ========== SECTION 8: THE RESULTS ========== --}}
@if($project->results)
<section class="py-16 lg:py-24 relative overflow-hidden bg-midnight">
    {{-- Subtle radial glow --}}
    <div class="absolute inset-0 pointer-events-none" style="background: radial-gradient(ellipse at 70% 50%, {{ $accent }}0A, transparent 65%);"></div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 animate-up relative overflow-hidden">
        <span class="case-study-number" style="color: {{ $accent }};">03</span>
        <div class="relative z-10">
            <h2 class="text-2xl lg:text-3xl font-black text-white mb-8 pb-5 border-b border-soft/10">The Results</h2>
            <div class="animate-up">
                {!! formatResultsText($project->results) !!}
            </div>
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 9: PROJECT OVERVIEW ========== --}}
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="animate-up">
            <h2 class="text-2xl font-bold text-midnight dark:text-white mb-8">Project Overview</h2>
            <div class="prose dark:prose-invert max-w-none text-soft-dark dark:text-soft/80 leading-relaxed">
                {!! nl2br(e($project->description)) !!}
            </div>
        </div>
    </div>
</section>

{{-- ========== SECTION 10: ACTION CTA BANNER ========== --}}
@if($project->project_url || $project->github_url)
<section class="py-16 lg:py-20 relative overflow-hidden"
         style="background: linear-gradient(135deg, {{ $accent }}20 0%, #0D1B2A 50%, {{ $accentSecondary }}15 100%);">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center relative z-10">
        <div class="animate-up">
            <h2 class="text-3xl font-bold text-white mb-4">
                @if($project->project_url)
                See it in action
                @else
                Explore the code
                @endif
            </h2>
            <p class="text-soft/60 mb-8">Check out the live project or browse the source code.</p>

            <div class="flex flex-wrap justify-center gap-4">
                @if($project->project_url)
                <a href="{{ $project->project_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                   style="background: {{ $accent }};">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                    Live Demo
                </a>
                @endif
                @if($project->github_url)
                <a href="{{ $project->github_url }}" target="_blank" rel="noopener"
                   class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-white border-2 transition-all duration-300 hover:shadow-lg hover:-translate-y-0.5"
                   style="border-color: {{ $accent }}60;">
                    <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path></svg>
                    View Code
                </a>
                @endif
            </div>
        </div>
    </div>
</section>
@endif

{{-- ========== SECTION 11: RELATED PROJECTS ========== --}}
@if($relatedProjects->count())
<section class="py-16 lg:py-24 bg-white dark:bg-ocean/10">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="text-center mb-12 animate-up">
            <h2 class="text-2xl font-bold text-midnight dark:text-white">Related Projects</h2>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach($relatedProjects as $index => $relatedProject)
            @php
                $relAccent = $relatedProject->color_primary ?? '#41EAD4';
                $accentClasses = ['accent-teal', 'accent-sunset', 'accent-blend'];
            @endphp
            <article class="animate-up">
                <a href="{{ route('portfolio.show', $relatedProject->slug) }}"
                   class="block card card-hover p-6 accent-strip {{ $accentClasses[$index % 3] }} relative overflow-hidden h-full">

                    {{-- Category badge --}}
                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full mb-4"
                          style="background: {{ $relatedProject->category->color }}15; color: {{ $relatedProject->category->color }};">
                        {{ $relatedProject->category->name }}
                    </span>

                    <h3 class="text-lg font-bold text-midnight dark:text-white mb-2 line-clamp-2">
                        {{ $relatedProject->title }}
                    </h3>

                    <p class="text-sm text-soft-dark dark:text-soft/60 mb-4 line-clamp-2">
                        {{ $relatedProject->short_description }}
                    </p>

                    {{-- Tech pills --}}
                    @if($relatedProject->technologies)
                    <div class="flex flex-wrap gap-1.5 mt-auto">
                        @foreach(array_slice($relatedProject->technologies, 0, 3) as $tech)
                        <span class="px-2 py-0.5 text-xs rounded bg-soft/10 dark:bg-soft/5 text-soft-dark dark:text-soft/60">
                            {{ $tech }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    {{-- Arrow --}}
                    <div class="absolute bottom-4 right-4 w-8 h-8 rounded-full bg-soft/10 dark:bg-soft/5 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4 text-soft-dark dark:text-soft/60" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </div>
                </a>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

@endsection
