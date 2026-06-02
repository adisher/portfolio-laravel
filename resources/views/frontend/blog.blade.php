@extends('layouts.app')

@section('title', 'Blog - Web Development Articles & Tutorials')
@section('description', 'Read articles and tutorials about web development, programming tips, and technology insights')

@section('content')
{{-- Page Header --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light section-padding overflow-hidden">
    {{-- Floating shapes --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 right-10 w-72 h-72 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 left-10 w-64 h-64 bg-sunset/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="badge badge-teal mb-4 animate-up">Articles</span>
        <h1 class="animate-up text-4xl lg:text-5xl font-black mb-4">
            Blog & <span class="text-gradient">Articles</span>
        </h1>
        <p class="animate-up text-lg text-soft max-w-2xl mx-auto">
            Thoughts on web development, tutorials, and industry insights
        </p>
    </div>
</section>

{{-- Blog Content --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
            {{-- Main Content --}}
            <div class="lg:col-span-3">
                @if($posts->count())
                <div class="space-y-8 animate-stagger">
                    @foreach($posts as $post)
                    <article class="card card-hover overflow-hidden">
                        <div class="md:flex">
                            @if($post->featured_image)
                            <div class="md:w-80 md:flex-shrink-0">
                                <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                    class="w-full h-48 md:h-full object-cover img-zoom">
                            </div>
                            @endif

                            <div class="p-6 md:p-8 flex-1">
                                <div class="flex items-center mb-3">
                                    <span class="inline-block px-3 py-1 text-sm font-medium rounded-full mr-3"
                                        style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                                        {{ $post->category->name }}
                                    </span>
                                    <time class="text-sm text-soft-dark dark:text-soft">
                                        {{ $post->published_at->format('M j, Y') }}
                                    </time>
                                    <span class="mx-2 text-soft/30">•</span>
                                    <span class="text-sm text-soft-dark dark:text-soft">
                                        {{ $post->reading_time }} min read
                                    </span>
                                </div>

                                <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-3">
                                    <a href="{{ route('blog.show', $post->slug) }}"
                                        class="hover:text-teal dark:hover:text-teal transition-colors">
                                        {{ $post->title }}
                                    </a>
                                </h2>

                                <p class="text-soft-dark dark:text-soft mb-4 line-clamp-3">
                                    {{ $post->excerpt }}
                                </p>

                                <div class="flex items-center justify-between">
                                    <a href="{{ route('blog.show', $post->slug) }}"
                                        class="text-teal hover:text-teal-dark font-medium transition-colors">
                                        Read More →
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
                <div class="mt-12">
                    {{ $posts->links() }}
                </div>
                @else
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-soft-dark" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-midnight dark:text-soft-light">No articles found</h3>
                    <p class="mt-1 text-sm text-soft-dark dark:text-soft">
                        Check back soon for new articles and tutorials.
                    </p>
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="lg:col-span-1 animate-up">
                {{-- Categories --}}
                @if($categories->count())
                <div class="card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-midnight dark:text-soft-light mb-4">
                        Categories
                    </h3>
                    <ul class="space-y-2">
                        @foreach($categories as $category)
                        <li>
                            <a href="{{ route('blog.category', $category->slug) }}"
                                class="flex items-center justify-between text-soft-dark dark:text-soft hover:text-teal dark:hover:text-teal transition-colors">
                                <span>{{ $category->name }}</span>
                                <span class="text-sm bg-soft-light dark:bg-midnight-light px-2 py-1 rounded">
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
                <div class="card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-midnight dark:text-soft-light mb-4">
                        Popular Tags
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                        <a href="#" class="inline-block px-3 py-1 text-sm font-medium rounded-full hover:opacity-80 transition-opacity"
                            style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                {{-- Newsletter --}}
                <div class="card p-6 bg-gradient-to-br from-midnight to-ocean dark:from-ocean dark:to-midnight-dark text-soft-light mb-8">
                    <h3 class="text-lg font-semibold mb-3">
                        Stay Updated
                    </h3>
                    <p class="text-sm text-soft mb-4">
                        Get notified about new articles and tutorials.
                    </p>
                    <form class="space-y-3">
                        <input type="email" placeholder="Your email address"
                            class="w-full px-3 py-2 border border-soft/20 rounded-lg focus:ring-2 focus:ring-teal focus:border-transparent bg-white/10 text-soft-light placeholder-soft/60">
                        <button type="submit" class="w-full btn-primary text-sm py-2">
                            Subscribe
                        </button>
                    </form>
                </div>

                {{-- Search --}}
                <div class="card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-midnight dark:text-soft-light mb-4">
                        Search Articles
                    </h3>
                    <x-search-form />
                </div>
            </div>
        </div>
    </div>
</section>
@endsection