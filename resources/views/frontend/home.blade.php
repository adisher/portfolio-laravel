@extends('layouts.app')

@section('title', 'Home - Portfolio')
@section('description', 'Full Stack Developer specializing in modern web applications and API development')

@section('content')

{{-- ========== HERO SECTION ========== --}}
@feature('section.home.hero')
<section class="min-h-screen bg-soft-light dark:bg-midnight flex items-center relative overflow-hidden">
    {{-- Floating Background Shapes --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="float-element absolute top-20 left-10 w-72 h-72 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="float-element absolute bottom-20 right-10 w-96 h-96 bg-sunset/10 rounded-full blur-3xl"></div>
        <div class="float-element absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-ocean/5 rounded-full blur-3xl"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 py-20 relative z-10">
        <div class="grid lg:grid-cols-2 gap-16 items-center lg:items-stretch">
            {{-- Hero Content --}}
            <div class="hero-text">
                <p class="hero-word text-teal font-medium tracking-widest uppercase text-sm mb-6">
                    Full Stack Developer
                </p>
                <h1 class="hero-word text-5xl lg:text-7xl font-black text-midnight dark:text-soft-light leading-[1.1] mb-8">
                    Building digital experiences that
                    <span class="text-gradient">stand out</span>
                </h1>
                <p class="hero-word text-xl text-soft-dark dark:text-soft leading-relaxed mb-10 max-w-lg">
                    Crafting performant, accessible web applications with modern technologies.
                    Focused on clean code and exceptional user experiences.
                </p>
                <div class="hero-word flex flex-wrap gap-4">
                    <a href="{{ route('portfolio.index') }}" class="btn-primary">
                        View My Work
                        <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                        </svg>
                    </a>
                    <a href="{{ route('contact') }}" class="btn-secondary">
                        Get In Touch
                    </a>
                </div>
            </div>

            {{-- Hero Image --}}
            <div class="animate-up hidden lg:flex lg:flex-col lg:justify-center">
                <div class="relative h-full">
                    <div class="absolute inset-0 bg-gradient-to-br from-teal/20 to-sunset/20 rounded-3xl blur-2xl"></div>
                    <div class="relative bg-ocean/10 dark:bg-ocean/30 rounded-3xl backdrop-blur-sm border border-soft/10 h-full flex items-center">
                        <img src="{{ asset('storage/media/home-header-2.webp') }}"
                            alt="Development"
                            class="w-full h-full object-cover object-[center_30%] rounded-2xl"
                            loading="eager">
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scroll Indicator --}}
    <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-soft-dark dark:text-soft" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </div>
</section>
@endfeature

@feature('section.home.stats')
{{-- ========== STATS BANNER ========== --}}
<section class="py-6 bg-midnight dark:bg-midnight-dark border-y border-ocean/20">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-6 md:gap-8">
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-teal mb-1">{{ $featuredSkills->count() }}+</div>
                <div class="text-sm text-soft/70">Tools & Technologies</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-sunset mb-1">{{ $projectCount }}+</div>
                <div class="text-sm text-soft/70">Projects Delivered</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-teal mb-1">5+</div>
                <div class="text-sm text-soft/70">Years Experience</div>
            </div>
            <div class="text-center">
                <div class="text-3xl md:text-4xl font-black text-sunset mb-1">&infin;</div>
                <div class="text-sm text-soft/70">Always Learning</div>
            </div>
        </div>
    </div>
</section>
@endfeature

{{-- ========== MY PRODUCTS SECTION ========== --}}
@if($ownProducts->count())
<section id="products" class="section-padding bg-white dark:bg-midnight-light relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute top-0 left-0 w-80 h-80 bg-teal/5 rounded-full -translate-x-1/2 -translate-y-1/2 blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        {{-- Section Header --}}
        <div class="section-header animate-up">
            <span class="badge badge-teal mb-4">Indie Products</span>
            <h2 class="section-title">
                Built By <span class="text-gradient">Me</span>
            </h2>
            <p class="section-subtitle">
                Products I've designed, built, and ship independently
            </p>
        </div>

        {{-- Products Grid --}}
        <div class="grid md:grid-cols-2 gap-8 animate-stagger">
            @foreach($ownProducts as $product)
            <article class="card card-hover group opacity-0 relative overflow-hidden rounded-2xl">
                <a href="{{ route('products.show', $product->slug) }}" class="block">
                    {{-- Full-width landscape thumbnail --}}
                    @if($product->featured_image)
                    <div class="relative overflow-hidden">
                        <img src="{{ Storage::url($product->featured_image) }}"
                             alt="{{ $product->title }}"
                             class="w-full aspect-video object-cover group-hover:scale-105 transition-transform duration-500"
                             loading="lazy">
                        {{-- Accent strip at bottom of image --}}
                        @if($product->color_primary)
                        <div class="absolute bottom-0 left-0 right-0 h-1" style="background: {{ $product->color_primary }};"></div>
                        @else
                        <div class="absolute bottom-0 left-0 right-0 h-1 bg-gradient-to-r from-teal to-sunset"></div>
                        @endif
                    </div>
                    @endif

                    <div class="p-6">
                        {{-- Category --}}
                        @if($product->category)
                        <span class="inline-block text-[10px] font-semibold uppercase tracking-wider mb-2"
                              style="color: {{ $product->category->color ?? '#41EAD4' }};">
                            {{ $product->category->name }}
                        </span>
                        @endif

                        {{-- Title + Arrow --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <h3 class="text-lg font-bold text-midnight dark:text-soft-light group-hover:text-teal transition-colors">
                                {{ $product->title }}
                            </h3>
                            <svg class="w-4 h-4 flex-shrink-0 mt-1 text-soft-dark/30 dark:text-soft/20 group-hover:text-teal group-hover:translate-x-0.5 transition-all"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </div>

                        {{-- Description - full, no truncation --}}
                        <p class="text-sm text-soft-dark dark:text-soft/70 leading-relaxed">
                            {{ $product->short_description }}
                        </p>
                    </div>
                </a>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ========== TECH STACK (Marquee) ========== --}}
@if($featuredSkills->count())
<section id="skills" class="py-12 lg:py-16 bg-white dark:bg-midnight-light overflow-hidden">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 mb-8">
        <div class="section-header animate-up">
            <span class="badge badge-teal mb-4">Tech Stack</span>
            <h2 class="section-title">
                Technologies I <span class="text-gradient">Work With</span>
            </h2>
        </div>
    </div>

    {{-- Marquee Row --}}
    <div class="marquee-container">
        <div class="marquee-track">
            {{-- First copy --}}
            @foreach($featuredSkills as $skill)
            <div class="marquee-item">
                @if($skill->icon)
                <div class="w-8 h-8 flex-shrink-0" style="color: {{ $skill->color }}">
                    {!! $skill->icon !!}
                </div>
                @else
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background: {{ $skill->color }}20; color: {{ $skill->color }}">
                    <span class="font-bold text-xs">{{ substr($skill->name, 0, 2) }}</span>
                </div>
                @endif
                <span class="text-sm font-medium text-midnight dark:text-soft-light whitespace-nowrap">{{ $skill->name }}</span>
            </div>
            @endforeach
            {{-- Duplicate for seamless loop --}}
            @foreach($featuredSkills as $skill)
            <div class="marquee-item">
                @if($skill->icon)
                <div class="w-8 h-8 flex-shrink-0" style="color: {{ $skill->color }}">
                    {!! $skill->icon !!}
                </div>
                @else
                <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
                     style="background: {{ $skill->color }}20; color: {{ $skill->color }}">
                    <span class="font-bold text-xs">{{ substr($skill->name, 0, 2) }}</span>
                </div>
                @endif
                <span class="text-sm font-medium text-midnight dark:text-soft-light whitespace-nowrap">{{ $skill->name }}</span>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ========== WHAT I BUILD SECTION ========== --}}
@if($solutions->count())
<section id="solutions" class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="section-header animate-up">
            <span class="badge badge-teal mb-4">Domain Expertise</span>
            <h2 class="section-title">
                What I <span class="text-gradient">Build</span>
            </h2>
            <p class="section-subtitle">
                From e-commerce to real-time platforms: the types of projects I deliver
            </p>
        </div>

        {{-- Solutions Grid --}}
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($solutions as $solution)
            <div class="card card-hover p-6 accent-strip accent-{{ $solution->accent_color }} group opacity-0">
                {{-- Icon --}}
                <div class="w-12 h-12 rounded-xl flex items-center justify-center mb-4
                            bg-teal/10 dark:bg-teal/5 group-hover:bg-teal/20 transition-colors duration-300">
                    @if($solution->icon)
                    <div class="w-6 h-6 text-teal">{!! $solution->icon !!}</div>
                    @else
                    <span class="text-teal font-bold text-sm">{{ substr($solution->title, 0, 2) }}</span>
                    @endif
                </div>

                {{-- Title --}}
                <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-2">
                    {{ $solution->title }}
                </h3>

                {{-- Description --}}
                <p class="text-sm text-soft-dark dark:text-soft leading-relaxed mb-4 line-clamp-2">
                    {{ $solution->description }}
                </p>

                {{-- Keywords --}}
                @if($solution->keywords && count($solution->keywords))
                <div class="flex flex-wrap gap-1.5">
                    @foreach(array_slice($solution->keywords, 0, 3) as $keyword)
                    <span class="text-xs px-2 py-0.5 bg-ocean/10 dark:bg-ocean/30
                                 text-midnight dark:text-soft-light rounded">{{ $keyword }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

@feature('section.home.featured_projects')
{{-- ========== PROJECTS SECTION (Metric-First Bento Grid) ========== --}}
@if($featuredProjects->count())
<section id="projects" class="section-padding bg-soft-light dark:bg-midnight relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute top-0 right-0 w-96 h-96 bg-teal/5 rounded-full translate-x-1/2 -translate-y-1/2 blur-3xl"></div>
    <div class="absolute bottom-0 left-0 w-96 h-96 bg-sunset/5 rounded-full -translate-x-1/2 translate-y-1/2 blur-3xl"></div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        {{-- Section Header --}}
        <div class="section-header animate-up">
            <span class="badge badge-sunset mb-4">Featured Work</span>
            <h2 class="section-title">
                Results That <span class="text-gradient">Speak</span>
            </h2>
            <p class="section-subtitle">
                Real impact, measurable outcomes. Here's what I've delivered.
            </p>
        </div>

        {{-- Bento Grid --}}
        <div class="bento-grid animate-stagger">
            @foreach($featuredProjects as $index => $project)
            @php
                $detailUrl = $project->is_own_product
                    ? route('products.show', $project->slug)
                    : route('portfolio.show', $project->slug);
            @endphp

            @if($index === 0)
            {{-- ===== HERO CARD: split layout, spans 2 columns ===== --}}
            <article class="project-card bento-hero opacity-0 group">
                <a href="{{ $detailUrl }}"
                   class="block h-full rounded-2xl border border-soft/10 dark:border-soft/5 overflow-hidden
                          transition-all duration-300 hover:shadow-glow-teal hover:-translate-y-1 relative">

                    <div class="flex flex-col lg:flex-row h-full">
                        {{-- Left: Image --}}
                        <div class="lg:w-1/2 aspect-video lg:aspect-auto overflow-hidden flex-shrink-0">
                            @if($project->featured_image)
                            <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}"
                                 class="w-full h-full object-cover img-zoom">
                            @else
                            <div class="w-full h-full bg-ocean/30"></div>
                            @endif
                        </div>

                        {{-- Right: Content on dark panel --}}
                        <div class="flex-1 bg-midnight p-8 lg:p-10 flex flex-col justify-between relative">
                            <div class="mb-6">
                                <span class="badge badge-sunset">Featured Work</span>
                            </div>

                            <div class="flex-1 flex flex-col justify-center py-4">
                                <h3 class="text-2xl lg:text-3xl font-bold text-soft-light mb-4 group-hover:text-teal transition-colors">
                                    {{ $project->title }}
                                </h3>
                                <p class="text-soft/80 leading-relaxed">
                                    {{ $project->short_description }}
                                </p>
                            </div>

                            @if($project->technologies)
                            <div class="flex flex-wrap gap-2 mt-auto pt-4">
                                @foreach(array_slice($project->technologies, 0, 5) as $tech)
                                <span class="text-xs px-2.5 py-1 bg-white/10 text-soft-light/80 rounded-md backdrop-blur-sm border border-white/5">
                                    {{ $tech }}
                                </span>
                                @endforeach
                            </div>
                            @endif

                            <div class="absolute bottom-6 right-6 w-10 h-10 rounded-full bg-teal/20 flex items-center justify-center
                                        group-hover:bg-teal/40 transition-all duration-300 group-hover:scale-110">
                                <svg class="w-5 h-5 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </a>
            </article>

            @else
            {{-- ===== REGULAR CARD: unified, image top + dark content ===== --}}
            <article class="project-card opacity-0 group">
                <a href="{{ $detailUrl }}"
                   class="flex flex-col h-full rounded-xl border border-soft/10 dark:border-soft/5 overflow-hidden
                          transition-all duration-300 hover:shadow-glow-teal hover:-translate-y-1 relative">

                    {{-- Image --}}
                    @if($project->featured_image)
                    <div class="aspect-video overflow-hidden flex-shrink-0">
                        <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}"
                             class="w-full h-full object-cover img-zoom">
                    </div>
                    @endif

                    {{-- Content --}}
                    <div class="flex-1 bg-midnight p-6 flex flex-col relative">
                        <h3 class="text-lg font-bold text-soft-light mb-3 group-hover:text-teal transition-colors">
                            {{ $project->title }}
                        </h3>

                        <p class="text-soft/70 text-sm leading-relaxed mb-4">
                            {{ $project->short_description }}
                        </p>

                        @if($project->technologies)
                        <div class="flex flex-wrap gap-1.5 mt-auto pt-2">
                            @foreach(array_slice($project->technologies, 0, 4) as $tech)
                            <span class="text-xs px-2.5 py-1 bg-white/10 text-soft-light/80 rounded-md backdrop-blur-sm border border-white/5">
                                {{ $tech }}
                            </span>
                            @endforeach
                        </div>
                        @endif

                        {{-- Circle arrow --}}
                        <div class="absolute bottom-4 right-4 w-8 h-8 rounded-full bg-teal/20 flex items-center justify-center
                                    group-hover:bg-teal/40 transition-all duration-300 group-hover:scale-110">
                            <svg class="w-4 h-4 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                            </svg>
                        </div>
                    </div>
                </a>
            </article>
            @endif

            @endforeach
        </div>

        {{-- View All CTA --}}
        <div class="text-center mt-12 animate-up">
            <a href="{{ route('portfolio.index') }}" class="btn-primary">
                View All Projects
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.home.blog_posts')
{{-- ========== BLOG SECTION ========== --}}
@if($latestPosts->count())
<section id="blog" class="section-padding bg-soft-light dark:bg-midnight-light">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="section-header animate-up">
            <span class="badge badge-teal mb-4">Blog</span>
            <h2 class="section-title">
                Latest <span class="text-gradient">Articles</span>
            </h2>
            <p class="section-subtitle">
                Thoughts on web development, tutorials, and industry insights
            </p>
        </div>

        {{-- Blog Grid --}}
        <div class="animate-stagger grid md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($latestPosts as $post)
            <article class="card card-hover overflow-hidden">
                @if($post->featured_image)
                <div class="overflow-hidden">
                    <img src="{{ Storage::url($post->featured_image) }}"
                         alt="{{ $post->title }}"
                         class="w-full aspect-video object-cover img-zoom"
                         loading="lazy">
                </div>
                @endif
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="badge" style="background: {{ $post->category->color }}15; color: {{ $post->category->color }}">
                            {{ $post->category->name }}
                        </span>
                        <span class="text-sm text-soft-dark dark:text-soft">
                            {{ $post->published_at->format('M j, Y') }}
                        </span>
                    </div>
                    <h3 class="text-xl font-bold text-midnight dark:text-soft-light mb-3 hover:text-teal transition-colors">
                        <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                    </h3>
                    <p class="text-soft-dark dark:text-soft mb-4 line-clamp-2">
                        {{ $post->excerpt }}
                    </p>
                    <div class="flex items-center justify-between">
                        <a href="{{ route('blog.show', $post->slug) }}" class="text-teal font-medium text-sm hover:text-teal-dark transition-colors">
                            Read More →
                        </a>
                        <span class="text-sm text-soft-dark dark:text-soft">
                            {{ $post->reading_time }} min read
                        </span>
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        {{-- View All CTA --}}
        <div class="text-center mt-12 animate-up">
            <a href="{{ route('blog.index') }}" class="btn-secondary">
                View All Articles
            </a>
        </div>
    </div>
</section>
@endif
@endfeature

@feature('section.home.testimonials')
{{-- ========== TESTIMONIALS SECTION (Globe) ========== --}}
@if($testimonials->count())
<section class="section-padding bg-soft-light dark:bg-midnight overflow-hidden" x-data="globeTestimonials()" x-init="init()">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        {{-- Section Header --}}
        <div class="section-header animate-up">
            <span class="badge badge-sunset mb-4">Global Trust</span>
            <h2 class="section-title">
                Words That <span class="text-gradient">Matter</span>
            </h2>
            <p class="section-subtitle">
                What clients and colleagues say about working with me, across {{ $testimonials->whereNotNull('country_code')->unique('country_code')->count() ?: 'multiple' }} countries
            </p>
        </div>

        {{-- Globe & Testimonial Layout --}}
        <div class="relative">
            {{-- Desktop: Globe (left) + Testimonial Card (right) --}}
            <div class="hidden lg:grid lg:grid-cols-12 lg:gap-8 items-center min-h-[550px] animate-up">
                {{-- Globe Column --}}
                <div class="lg:col-span-7 relative">
                    <div class="globe-container mx-auto" id="globe-container">
                        {{-- Globe.gl renders here --}}
                    </div>
                    {{-- Hint text (hidden when a testimonial is active) --}}
                    <p class="text-center text-sm text-soft-dark dark:text-soft mt-4 transition-opacity duration-500"
                       x-show="!activeTestimonial" x-transition:leave="transition ease-in duration-300"
                       x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 animate-pulse text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"/>
                            </svg>
                            Click a location or wait for the showcase
                        </span>
                    </p>
                </div>

                {{-- Testimonial Card Column --}}
                <div class="lg:col-span-5">
                    {{-- Active testimonial card --}}
                    <div x-show="activeTestimonial"
                         x-transition:enter="transition ease-out duration-700"
                         x-transition:enter-start="opacity-0"
                         x-transition:enter-end="opacity-100"
                         x-transition:leave="transition ease-in duration-200"
                         x-transition:leave-start="opacity-100"
                         x-transition:leave-end="opacity-0">
                        <div class="testimonial-card-panel">
                            <div class="card p-8 relative overflow-hidden">
                                {{-- Decorative glow --}}
                                <div class="absolute top-0 right-0 w-32 h-32 bg-teal/10 rounded-full -translate-y-1/2 translate-x-1/2 blur-2xl pointer-events-none"></div>

                                {{-- Country & Flag --}}
                                <div class="flex items-center gap-3 mb-4">
                                    <span class="fi text-3xl rounded overflow-hidden shadow-sm"
                                          :class="activeTestimonial ? 'fi-' + (activeTestimonial.country_code === 'UK' ? 'gb' : activeTestimonial.country_code.toLowerCase()) : ''"></span>
                                    <span class="text-sm font-medium text-soft-dark dark:text-soft"
                                          x-text="activeTestimonial?.country_name"></span>
                                </div>

                                {{-- Stars --}}
                                <div class="flex gap-1 mb-5">
                                    <template x-for="i in 5">
                                        <svg class="w-5 h-5"
                                             :class="i <= (activeTestimonial?.rating || 0) ? 'text-sunset' : 'text-soft/30'"
                                             fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    </template>
                                </div>

                                {{-- Quote --}}
                                <blockquote class="relative mb-6">
                                    <svg class="absolute -top-2 -left-1 w-8 h-8 text-teal/20" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H14.017zm-14.017 0v-7.391c0-5.704 3.731-9.57 8.983-10.609l.998 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10H0z"/>
                                    </svg>
                                    <p class="pl-8 text-lg text-midnight dark:text-soft-light leading-relaxed italic"
                                       x-text="activeTestimonial?.testimonial"></p>
                                </blockquote>

                                {{-- Role progression --}}
                                <template x-if="activeTestimonial?.client_role">
                                    <div class="mb-5 py-3 border-t border-soft/10 dark:border-soft/5">
                                        <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-teal/60 mb-1.5">My Role</p>
                                        <p class="text-sm font-semibold text-midnight dark:text-soft-light"
                                           x-text="activeTestimonial.client_role"></p>
                                        <template x-if="activeTestimonial?.role_description">
                                            <p class="text-xs text-soft-dark dark:text-soft/70 mt-1 leading-relaxed"
                                               x-text="activeTestimonial.role_description"></p>
                                        </template>
                                    </div>
                                </template>

                                {{-- Author --}}
                                <div class="flex items-center gap-3 pt-4 border-t border-soft/20 dark:border-soft/10">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-teal to-sunset flex items-center justify-center flex-shrink-0">
                                        <span class="text-white font-bold text-lg"
                                              x-text="activeTestimonial?.client_name?.charAt(0)"></span>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="font-semibold text-midnight dark:text-soft-light"
                                             x-text="activeTestimonial?.client_name"></div>
                                        <div class="text-sm text-soft-dark dark:text-soft"
                                             x-text="activeTestimonial?.client_position"></div>
                                    </div>
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        <template x-if="activeTestimonial?.client_linkedin">
                                            <a :href="activeTestimonial.client_linkedin" target="_blank" rel="noopener noreferrer"
                                               class="text-soft-dark/50 hover:text-[#0A66C2] dark:text-soft/40 dark:hover:text-[#0A66C2] transition-colors"
                                               title="LinkedIn">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                                </svg>
                                            </a>
                                        </template>
                                        <template x-if="activeTestimonial?.client_website">
                                            <a :href="activeTestimonial.client_website" target="_blank" rel="noopener noreferrer"
                                               class="text-soft-dark/50 hover:text-teal dark:text-soft/40 dark:hover:text-teal transition-colors"
                                               title="Website">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.466.732-3.558"/>
                                                </svg>
                                            </a>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Placeholder when no testimonial is active --}}
                    <div x-show="!activeTestimonial" x-transition class="text-center py-16 text-soft-dark dark:text-soft">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-teal/10 flex items-center justify-center">
                            <svg class="w-8 h-8 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <p class="text-sm">Explore client locations around the globe</p>
                    </div>
                </div>
            </div>

            {{-- Mobile: Swiper Carousel --}}
            <div class="lg:hidden animate-up">
                <div class="swiper testimonials-swiper">
                    <div class="swiper-wrapper">
                        @foreach($testimonials as $testimonial)
                        <div class="swiper-slide">
                            <div class="card p-6">
                                {{-- Country --}}
                                @if($testimonial->country_code)
                                <div class="flex items-center gap-2 mb-3">
                                    <span class="fi text-2xl fi-{{ strtolower($testimonial->country_code === 'UK' ? 'gb' : $testimonial->country_code) }}"></span>
                                    <span class="text-sm font-medium text-soft-dark dark:text-soft">{{ $testimonial->country_name }}</span>
                                </div>
                                @endif

                                {{-- Stars --}}
                                <div class="flex gap-1 mb-4">
                                    @for($i = 1; $i <= 5; $i++)
                                    <svg class="w-4 h-4 {{ $i <= $testimonial->rating ? 'text-sunset' : 'text-soft' }}" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                    </svg>
                                    @endfor
                                </div>

                                {{-- Quote --}}
                                <p class="text-midnight dark:text-soft-light leading-relaxed mb-4 italic">
                                    "{{ $testimonial->testimonial }}"
                                </p>

                                {{-- Role progression --}}
                                @if($testimonial->client_role)
                                <div class="mb-3 py-2.5 border-t border-soft/10 dark:border-soft/5">
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.2em] text-teal/60 mb-1">My Role</p>
                                    <p class="text-sm font-semibold text-midnight dark:text-soft-light">{{ $testimonial->client_role }}</p>
                                    @if($testimonial->role_description)
                                    <p class="text-xs text-soft-dark dark:text-soft/70 mt-1 leading-relaxed">{{ $testimonial->role_description }}</p>
                                    @endif
                                </div>
                                @endif

                                {{-- Author --}}
                                <div class="flex items-center gap-3">
                                    @if($testimonial->client_image)
                                    <img src="{{ Storage::url($testimonial->client_image) }}"
                                        alt="{{ $testimonial->client_name }}"
                                        class="w-10 h-10 rounded-full object-cover">
                                    @else
                                    <div class="w-10 h-10 rounded-full bg-teal/20 flex items-center justify-center">
                                        <span class="text-teal font-bold">{{ substr($testimonial->client_name, 0, 1) }}</span>
                                    </div>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <div class="font-medium text-midnight dark:text-soft-light">{{ $testimonial->client_name }}</div>
                                        <div class="text-xs text-soft-dark dark:text-soft">{{ $testimonial->client_position }}</div>
                                    </div>
                                    @if($testimonial->client_linkedin || $testimonial->client_website)
                                    <div class="flex items-center gap-2 flex-shrink-0">
                                        @if($testimonial->client_linkedin)
                                        <a href="{{ $testimonial->client_linkedin }}" target="_blank" rel="noopener noreferrer"
                                           class="text-soft-dark/50 hover:text-[#0A66C2] dark:text-soft/40 dark:hover:text-[#0A66C2] transition-colors"
                                           title="LinkedIn">
                                            <svg class="w-4.5 h-4.5" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                            </svg>
                                        </a>
                                        @endif
                                        @if($testimonial->client_website)
                                        <a href="{{ $testimonial->client_website }}" target="_blank" rel="noopener noreferrer"
                                           class="text-soft-dark/50 hover:text-teal dark:text-soft/40 dark:hover:text-teal transition-colors"
                                           title="Website">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5a17.92 17.92 0 0 1-8.716-2.247m0 0A8.966 8.966 0 0 1 3 12c0-1.264.26-2.466.732-3.558"/>
                                            </svg>
                                        </a>
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination mt-6"></div>
                </div>
            </div>

            {{-- Flag Navigation Rail --}}
            <div class="flags-rail animate-up mt-10">
                {{-- Progress track --}}
                <div class="flags-rail-track">
                    <div class="flags-rail-progress" :style="'width: ' + progressWidth"></div>
                </div>
                {{-- Flag buttons --}}
                <div class="flags-rail-items">
                    @foreach($testimonials->whereNotNull('country_code')->unique('country_code') as $index => $testimonial)
                    <button class="flag-rail-btn"
                            data-country-code="{{ $testimonial->country_code }}"
                            @click="selectCountry('{{ $testimonial->country_code }}')"
                            :class="{ 'active': activeCountry === '{{ $testimonial->country_code }}' }"
                            title="{{ $testimonial->country_name }}">
                        <span class="fi fi-{{ strtolower($testimonial->country_code === 'UK' ? 'gb' : $testimonial->country_code) }}"></span>
                        <span class="flag-rail-label"
                              x-show="activeCountry === '{{ $testimonial->country_code }}'"
                              x-transition:enter="transition ease-out duration-200"
                              x-transition:enter-start="opacity-0 -translate-y-1"
                              x-transition:enter-end="opacity-100 translate-y-0">
                            {{ $testimonial->country_name }}
                        </span>
                    </button>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- Pass testimonials data to JavaScript --}}
    @php
        $testimonialData = $testimonials->map(fn($t) => [
            'id' => $t->id,
            'client_name' => $t->client_name,
            'client_position' => $t->client_position,
            'client_company' => $t->client_company,
            'client_role' => $t->client_role,
            'role_description' => $t->role_description,
            'client_website' => $t->client_website,
            'client_linkedin' => $t->client_linkedin,
            'testimonial' => $t->testimonial,
            'rating' => $t->rating,
            'country_code' => $t->country_code,
            'country_name' => $t->country_name,
            'lat' => $t->latitude ? (float)$t->latitude : null,
            'lng' => $t->longitude ? (float)$t->longitude : null,
        ])->values();
    @endphp
    <script type="application/json" id="testimonials-data">{!! json_encode($testimonialData) !!}</script>
</section>
@endif
@endfeature

{{-- ========== CTA SECTION ========== --}}
<section class="py-24 bg-gradient-to-br from-midnight via-ocean to-midnight-dark relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="float-element absolute top-10 left-10 w-32 h-32 bg-teal/20 rounded-full blur-2xl"></div>
        <div class="float-element absolute bottom-10 right-10 w-40 h-40 bg-sunset/20 rounded-full blur-2xl"></div>
    </div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center relative z-10">
        <h2 class="animate-up text-4xl lg:text-5xl font-black text-soft-light mb-6">
            Ready to Start Your <span class="text-gradient">Project</span>?
        </h2>
        <p class="animate-up text-xl text-soft mb-10 max-w-2xl mx-auto">
            Let's discuss how I can help bring your ideas to life with modern web solutions.
        </p>
        <div class="animate-up">
            <a href="{{ route('contact') }}" class="btn-primary text-lg">
                Start a Conversation
                <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>
</section>

@endsection
