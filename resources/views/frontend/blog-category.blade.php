@extends('layouts.app')

@section('title', $category->name . ' Articles - Blog')
@section('description', 'Read articles about ' . $category->name . ': ' . $category->description)

@section('content')
<!-- Page Header -->
<section class="bg-gradient-to-r from-purple-600 to-blue-600 text-white section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="mb-4">
            <span class="inline-block px-4 py-2 bg-white bg-opacity-20 rounded-full text-sm font-medium">
                {{ $posts->total() }} {{ Str::plural('Article', $posts->total()) }}
            </span>
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold mb-4">
            {{ $category->name }} Articles
        </h1>
        @if($category->description)
        <p class="text-xl text-purple-100 max-w-2xl mx-auto">
            {{ $category->description }}
        </p>
        @endif
    </div>
</section>

<!-- Breadcrumb -->
<section class="bg-gray-50 dark:bg-gray-800 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('blog.index') }}" class="text-gray-500 hover:text-blue-600">Blog</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd"
                                d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-500">{{ $category->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
</section>

<!-- Category Navigation -->
<section class="py-8 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-2">
            <a href="{{ route('blog.index') }}" class="filter-btn">
                All Articles
            </a>
            @foreach(\App\Models\Category::active()->forBlog()->withCount(['blogPosts' => fn($q) => $q->where('status','published')])->orderBy('name')->get()
            as $cat)
            <a href="{{ route('blog.category', $cat->slug) }}"
                class="filter-btn {{ $cat->id === $category->id ? 'active' : '' }}">
                {{ $cat->name }} ({{ $cat->blog_posts_count }})
            </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Blog Content -->
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($posts->count())
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
            <article class="card card-hover overflow-hidden flex flex-col">
                @if($post->featured_image)
                <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden">
                    <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                        class="w-full aspect-video object-cover img-zoom" loading="lazy">
                </a>
                @endif

                <div class="p-6 flex flex-col flex-1">
                    <div class="flex items-center flex-wrap gap-2 mb-3">
                        <span class="inline-block px-3 py-1 text-xs font-medium rounded-full"
                            style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                            {{ $post->category->name }}
                        </span>
                        <time class="text-sm text-soft-dark dark:text-soft">
                            {{ $post->published_at->format('M j, Y') }}
                        </time>
                        <span class="text-soft/30">•</span>
                        <span class="text-sm text-soft-dark dark:text-soft">
                            {{ $post->reading_time }} min read
                        </span>
                    </div>

                    <h2 class="text-xl font-bold text-midnight dark:text-soft-light mb-3 leading-snug">
                        <a href="{{ route('blog.show', $post->slug) }}"
                            class="hover:text-teal dark:hover:text-teal transition-colors">
                            {{ $post->title }}
                        </a>
                    </h2>

                    <p class="text-soft-dark dark:text-soft text-sm mb-4 line-clamp-3 flex-1">
                        {{ $post->excerpt }}
                    </p>

                    <div class="flex items-center justify-between mt-auto pt-2">
                        <a href="{{ route('blog.show', $post->slug) }}"
                            class="text-teal hover:text-teal-dark font-medium text-sm transition-colors">
                            Read More →
                        </a>

                        @if($post->tags->count())
                        <div class="flex flex-wrap gap-1">
                            @foreach($post->tags->take(2) as $tag)
                            <span class="text-xs px-2 py-1 rounded-full"
                                style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                                {{ $tag->name }}
                            </span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </article>
            @endforeach
        </div>

        <!-- Pagination -->
        @if($posts->hasPages())
        <div class="mt-12">
            {{ $posts->links() }}
        </div>
        @endif
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                </path>
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No articles in this category</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Check back soon for new {{ $category->name }} articles, or browse other categories.
            </p>
            <div class="mt-6">
                <a href="{{ route('blog.index') }}" class="btn-primary">
                    View All Articles
                </a>
            </div>
        </div>
        @endif
    </div>
</section>
@endsection