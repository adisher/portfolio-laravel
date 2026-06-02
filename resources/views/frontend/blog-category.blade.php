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
            @foreach(\App\Models\Category::active()->withCount('blogPosts')->having('blog_posts_count', '>', 0)->get()
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
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($posts->count())
        <div class="space-y-8">
            @foreach($posts as $post)
            <article class="card overflow-hidden hover:shadow-lg transition-shadow">
                <div class="md:flex">
                    @if($post->featured_image)
                    <div class="md:w-80 md:flex-shrink-0">
                        <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                            class="w-full h-48 md:h-full object-cover">
                    </div>
                    @endif

                    <div class="p-6 md:p-8 flex-1">
                        <div class="flex items-center mb-3">
                            <span class="inline-block px-3 py-1 text-sm font-medium rounded-full mr-3"
                                style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                                {{ $post->category->name }}
                            </span>
                            <time class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->published_at->format('M j, Y') }}
                            </time>
                            <span class="mx-2 text-gray-300">•</span>
                            <span class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $post->reading_time }} min read
                            </span>
                        </div>

                        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-3">
                            <a href="{{ route('blog.show', $post->slug) }}"
                                class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $post->title }}
                            </a>
                        </h2>

                        <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
                            {{ $post->excerpt }}
                        </p>

                        <div class="flex items-center justify-between">
                            <a href="{{ route('blog.show', $post->slug) }}"
                                class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
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