@extends('layouts.app')

@section('title', $post->meta_title ?: $post->title . ' - Blog')
@section('description', $post->meta_description ?: $post->excerpt)
@section('og_type', 'article')
@if($post->featured_image && !\Illuminate\Support\Str::endsWith($post->featured_image, '.svg'))
@section('og_image', \Illuminate\Support\Facades\Storage::url($post->featured_image))
@endif
@if($post->published_at)
@section('og_published_time', $post->published_at->toIso8601String())
@endif

@push('schema')
<x-schema.article :post="$post" />
<x-schema.breadcrumb :items="[
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'Blog', 'url' => route('blog.index')],
    ['name' => $post->category->name ?? 'Article', 'url' => $post->category ? route('blog.category', $post->category->slug) : route('blog.index')],
    ['name' => $post->title],
]" />
@endpush

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/styles/github-dark.min.css">
<style>
    .prose {
        max-width: none;
    }

    .prose img {
        border-radius: 0.5rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
     /* Enhanced markdown styling */
    .prose pre {
        background: #1e293b;
        border-radius: 0.5rem;
        padding: 1rem;
        overflow-x: auto;
    }

    .prose code {
        background: rgba(110, 118, 129, 0.1);
        padding: 0.2em 0.4em;
        border-radius: 0.25rem;
        font-size: 0.875em;
    }

    .prose pre code {
        background: transparent;
        padding: 0;
        border-radius: 0;
        font-size: 0.875em;
    }

    .prose h2 {
        font-size: 1.875rem;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .prose h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.75rem;
    }

    .prose blockquote {
        border-left: 4px solid #41EAD4;
        padding-left: 1rem;
        font-style: italic;
        color: #475B6B;
    }

    .dark .prose blockquote {
        color: #E0E1DD;
    }

    .prose a {
        color: #41EAD4;
        text-decoration: underline;
    }

    .prose a:hover {
        color: #2BC4B0;
    }

    /* Citation / reference links: small and unobtrusive, purely for sourcing */
    .prose a.ref-link {
        font-size: 0.72em;
        font-weight: 600;
        text-decoration: none;
        opacity: 0.65;
        white-space: nowrap;
        vertical-align: 0.15em;
    }

    .prose a.ref-link:hover {
        opacity: 1;
        text-decoration: underline;
    }

    .prose ul, .prose ol {
        padding-left: 1.5rem;
    }

    .prose li {
        margin: 0.5rem 0;
    }

    .prose table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
    }

    .prose th, .prose td {
        border: 1px solid #E0E1DD;
        padding: 0.5rem 1rem;
    }

    .dark .prose th, .dark .prose td {
        border-color: #1B3A4B;
    }

    .prose th {
        background: #F8F9FA;
        font-weight: 600;
    }

    .dark .prose th {
        background: #1B3A4B;
    }
</style>
@endpush

@section('content')
<!-- Article Header -->
<article>
    <header class="bg-soft-light dark:bg-midnight section-padding border-b border-soft/20 dark:border-ocean">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('blog.index') }}" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">Blog</a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-soft-dark/50 dark:text-soft/40" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd"></path>
                            </svg>
                            <span class="text-soft-dark dark:text-soft">{{ $post->title }}</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <div class="flex items-center mb-4">
                <span class="inline-block px-3 py-1 text-sm font-medium rounded-full mr-3"
                    style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                    {{ $post->category->name }}
                </span>
                @if($post->tags->count())
                <div class="flex flex-wrap gap-1">
                    @foreach($post->tags->take(3) as $tag)
                    <span class="text-xs px-2 py-1 rounded-full"
                        style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                        {{ $tag->name }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            <h1 class="text-3xl lg:text-5xl font-bold text-midnight dark:text-soft-light mb-6">
                {{ $post->title }}
            </h1>

            <div class="flex items-center text-soft-dark dark:text-soft mb-8">
                <div class="flex items-center mr-6">
                    <div
                        class="w-10 h-10 rounded-full bg-teal/10 dark:bg-teal/20 mr-3 flex items-center justify-center">
                        <span class="text-teal font-medium">
                            {{ substr($post->user->name, 0, 1) }}
                        </span>
                    </div>
                    <div>
                        <p class="font-medium text-midnight dark:text-soft-light">{{ $post->user->name }}</p>
                        <p class="text-sm">Author</p>
                    </div>
                </div>

                <div class="flex items-center space-x-6 text-sm">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                            </path>
                        </svg>
                        {{ $post->published_at->format('M j, Y') }}
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ $post->reading_time }} min read
                    </div>
                    <div class="flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        {{ number_format($post->views) }} views
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Featured Image -->
    @if($post->featured_image)
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 -mt-10 lg:-mt-14 relative z-10">
        <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
            class="w-full aspect-video object-cover rounded-xl shadow-xl">
    </div>
    @endif

    <!-- Article Content -->
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 section-padding">
        <div class="prose prose-lg dark:prose-invert mx-auto">
            {!! $post->rendered_content !!}
        </div>

        <!-- Share Buttons -->
        <div class="border-t border-soft/20 dark:border-ocean pt-8 mt-12">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-medium text-midnight dark:text-soft-light mb-2">
                        Share this article
                    </h3>
                    <div class="flex space-x-4">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->fullUrl()) }}&text={{ urlencode($post->title) }}"
                            target="_blank" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z" />
                            </svg>
                        </a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->fullUrl()) }}"
                            target="_blank" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z" />
                            </svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->fullUrl()) }}"
                            target="_blank" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
                            </svg>
                        </a>
                    </div>
                </div>

                @if($post->tags->count())
                <div class="text-right">
                    <h4 class="text-sm font-medium text-soft-dark dark:text-soft mb-2">Tags</h4>
                    <div class="flex flex-wrap gap-2 justify-end">
                        @foreach($post->tags as $tag)
                        <span class="inline-block px-3 py-1 text-sm font-medium rounded-full"
                            style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </span>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</article>

<!-- Author / Work-with-me CTA (internal links to cornerstone pages) -->
<section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 pb-4">
    <div class="card p-6 lg:p-8 bg-gradient-to-br from-midnight to-ocean text-soft-light">
        <div class="flex flex-col md:flex-row md:items-center gap-6">
            <div class="flex-1">
                <h3 class="text-xl font-bold text-soft-light mb-2">Written by Adil Sher</h3>
                <p class="text-soft text-sm leading-relaxed">
                    Full stack developer building high-traffic platforms, AI services, and custom web applications.
                    Explore my <a href="{{ route('portfolio.index') }}" class="text-teal hover:underline font-medium">portfolio</a>,
                    learn <a href="{{ route('about') }}" class="text-teal hover:underline font-medium">about my background</a>,
                    or <a href="{{ route('contact') }}" class="text-teal hover:underline font-medium">get in touch</a>.
                </p>
            </div>
            <div class="flex flex-wrap gap-3 flex-shrink-0">
                <a href="{{ route('portfolio.index') }}" class="btn-primary text-sm">View Portfolio</a>
                <a href="{{ route('contact') }}" class="btn-secondary border-soft/30 text-soft-light hover:border-teal hover:text-teal text-sm">Contact</a>
            </div>
        </div>
    </div>
</section>

<!-- Related Posts -->
@if($relatedPosts->count())
<section class="bg-soft-light dark:bg-midnight-light section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-8 animate-up">
            Related Articles
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-stagger">
            @foreach($relatedPosts as $relatedPost)
            <article class="card card-hover overflow-hidden">
                @if($relatedPost->featured_image)
                <div class="overflow-hidden">
                    <img src="{{ Storage::url($relatedPost->featured_image) }}" alt="{{ $relatedPost->title }}"
                        class="w-full aspect-video object-cover img-zoom">
                </div>
                @endif
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <span class="inline-block px-2 py-1 text-xs font-medium rounded-full"
                            style="background-color: {{ $relatedPost->category->color }}20; color: {{ $relatedPost->category->color }}">
                            {{ $relatedPost->category->name }}
                        </span>
                        <span class="ml-auto text-sm text-soft-dark dark:text-soft">
                            {{ $relatedPost->published_at->format('M j') }}
                        </span>
                    </div>
                    <h3 class="text-lg font-semibold text-midnight dark:text-soft-light mb-2">
                        <a href="{{ route('blog.show', $relatedPost->slug) }}"
                            class="hover:text-teal dark:hover:text-teal transition-colors">
                            {{ $relatedPost->title }}
                        </a>
                    </h3>
                    <p class="text-soft-dark dark:text-soft text-sm line-clamp-3">
                        {{ $relatedPost->excerpt }}
                    </p>
                </div>
            </article>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.8.0/highlight.min.js"></script>
<script>
    hljs.highlightAll();
</script>
@endpush