@extends('layouts.app')

@section('title', 'Search Results: ' . request('q') . ' - Blog')
@section('description', 'Search results for "' . request('q') . '" in blog articles')

@section('content')

{{-- Search Header --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light overflow-hidden py-16 lg:py-20">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 left-10 w-64 h-64 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-72 h-72 bg-sunset/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="text-center mb-8">
            <span class="badge badge-teal mb-4">Search</span>
            <h1 class="text-4xl lg:text-5xl font-bold mb-4 text-soft-light">
                Search Results
            </h1>
            <p class="text-lg text-soft">
                {{ $posts->total() }} result{{ $posts->total() !== 1 ? 's' : '' }} found for
                <span class="text-teal font-semibold">"{{ $query }}"</span>
            </p>
        </div>
        <div class="max-w-2xl mx-auto">
            <x-search-form :posts="$posts" />
        </div>
    </div>
</section>

{{-- Search Results --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">

            {{-- Main Content --}}
            <div class="lg:col-span-3">
                @if($posts->count())

                {{-- Search Stats Bar --}}
                <div class="mb-8 p-4 bg-teal/5 dark:bg-teal/10 rounded-lg border border-teal/20">
                    <div class="flex items-center gap-2">
                        <svg class="w-5 h-5 text-teal flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-midnight dark:text-soft-light font-medium">
                            Found {{ $posts->total() }} article{{ $posts->total() !== 1 ? 's' : '' }}
                            @if($searchTime)
                                in {{ number_format($searchTime, 3) }} seconds
                            @endif
                        </span>
                    </div>
                </div>

                <div class="space-y-6">
                    @foreach($posts as $post)
                    <article class="card card-hover overflow-hidden">
                        <div class="md:flex">
                            @if($post->featured_image)
                            <div class="md:w-72 md:flex-shrink-0">
                                <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                    class="w-full h-48 md:h-full object-cover">
                            </div>
                            @endif

                            <div class="p-6 flex-1">
                                {{-- Meta row --}}
                                <div class="flex flex-wrap items-center gap-2 mb-3">
                                    <span class="inline-block px-3 py-1 text-xs font-medium rounded-full"
                                        style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                                        {{ $post->category->name }}
                                    </span>
                                    <time class="text-sm text-soft-dark dark:text-soft/60">
                                        {{ $post->published_at->format('M j, Y') }}
                                    </time>
                                    <span class="text-soft-dark/40 dark:text-soft/30">•</span>
                                    <span class="text-sm text-soft-dark dark:text-soft/60">
                                        {{ $post->reading_time }} min read
                                    </span>
                                </div>

                                {{-- Title --}}
                                <h2 class="text-xl font-bold text-midnight dark:text-soft-light mb-3 leading-snug">
                                    <a href="{{ route('blog.show', $post->slug) }}"
                                        class="hover:text-teal transition-colors duration-200">
                                        {!! $post->highlighted_title !!}
                                    </a>
                                </h2>

                                {{-- Excerpt --}}
                                <p class="text-soft-dark dark:text-soft/80 mb-4 leading-relaxed text-sm">
                                    {!! $post->highlighted_excerpt !!}
                                </p>

                                {{-- Content snippet --}}
                                @if($post->highlighted_content)
                                <div class="bg-sunset/5 dark:bg-sunset/10 border-l-4 border-sunset/40 p-3 mb-4 rounded-r">
                                    <p class="text-sm text-soft-dark dark:text-soft/80">
                                        <span class="font-medium text-midnight dark:text-soft-light">Found in content:</span>
                                        {!! $post->highlighted_content !!}
                                    </p>
                                </div>
                                @endif

                                {{-- Footer row --}}
                                <div class="flex items-center justify-between pt-2 border-t border-soft-dark/10 dark:border-soft/10">
                                    <a href="{{ route('blog.show', $post->slug) }}"
                                        class="text-teal hover:text-teal/80 font-medium text-sm transition-colors duration-200 flex items-center gap-1">
                                        Read More
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </a>

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
                            </div>
                        </div>
                    </article>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($posts->hasPages())
                <div class="mt-12">
                    {{ $posts->appends(request()->query())->links() }}
                </div>
                @endif

                @else
                {{-- No Results --}}
                <div class="text-center py-16">
                    <div class="w-20 h-20 bg-soft-dark/10 dark:bg-soft/10 rounded-full flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-soft-dark dark:text-soft/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-midnight dark:text-soft-light mb-3">No articles found</h3>
                    <p class="text-soft-dark dark:text-soft mb-8 max-w-md mx-auto">
                        We couldn't find any articles matching
                        <span class="text-teal font-medium">"{{ $query }}"</span>.
                        Try different keywords or browse our categories.
                    </p>

                    <div class="max-w-xs mx-auto mb-8 text-left">
                        <h4 class="text-sm font-semibold text-midnight dark:text-soft-light mb-3 uppercase tracking-wide">Search Tips</h4>
                        <ul class="text-sm text-soft-dark dark:text-soft space-y-1.5">
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-teal flex-shrink-0"></span>Check your spelling</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-teal flex-shrink-0"></span>Try more general keywords</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-teal flex-shrink-0"></span>Use fewer keywords</li>
                            <li class="flex items-center gap-2"><span class="w-1.5 h-1.5 rounded-full bg-teal flex-shrink-0"></span>Browse by category instead</li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('blog.index') }}" class="btn-primary">View All Articles</a>
                        <button onclick="document.querySelector('input[name=q]').focus()" class="btn-secondary">
                            Try Another Search
                        </button>
                    </div>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Categories --}}
                @if($categories->count())
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-midnight dark:text-soft-light mb-4">Browse by Category</h3>
                    <ul class="space-y-2">
                        @foreach($categories as $category)
                        <li>
                            <a href="{{ route('blog.category', $category->slug) }}"
                                class="flex items-center justify-between text-soft-dark dark:text-soft hover:text-teal dark:hover:text-teal transition-colors text-sm">
                                <span>{{ $category->name }}</span>
                                <span class="text-xs bg-soft-light dark:bg-midnight-light text-soft-dark dark:text-soft px-2 py-0.5 rounded-full">
                                    {{ $category->blog_posts_count }}
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Popular Tags --}}
                @if($popularTags->count())
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-midnight dark:text-soft-light mb-4">Popular Tags</h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                        <a href="{{ route('blog.search') }}?q={{ urlencode($tag->name) }}"
                            class="inline-block px-3 py-1 text-xs font-medium rounded-full hover:opacity-80 transition-opacity"
                            style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Recent Articles --}}
                @if($recentPosts->count())
                <div class="card p-6">
                    <h3 class="text-base font-semibold text-midnight dark:text-soft-light mb-4">Recent Articles</h3>
                    <div class="space-y-4">
                        @foreach($recentPosts as $recentPost)
                        <div class="flex gap-3">
                            @if($recentPost->featured_image)
                            <img src="{{ Storage::url($recentPost->featured_image) }}" alt="{{ $recentPost->title }}"
                                class="w-14 h-14 rounded-lg object-cover flex-shrink-0">
                            @else
                            <div class="w-14 h-14 bg-soft-light dark:bg-midnight-light rounded-lg flex items-center justify-center flex-shrink-0">
                                <svg class="w-5 h-5 text-soft-dark dark:text-soft/40" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                                <h4 class="text-sm font-medium text-midnight dark:text-soft-light mb-1 leading-snug">
                                    <a href="{{ route('blog.show', $recentPost->slug) }}"
                                        class="hover:text-teal transition-colors">
                                        {{ Str::limit($recentPost->title, 60) }}
                                    </a>
                                </h4>
                                <p class="text-xs text-soft-dark dark:text-soft/50">
                                    {{ $recentPost->published_at->format('M j, Y') }}
                                </p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</section>
@endsection
