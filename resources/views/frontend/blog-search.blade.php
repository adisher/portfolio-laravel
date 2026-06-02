@extends('layouts.app')

@section('title', 'Search Results: ' . request('q') . ' - Blog')
@section('description', 'Search results for "' . request('q') . '" in blog articles')

@section('content')
<!-- Search Header -->
<section class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-4xl lg:text-5xl font-bold mb-4">
                Search Results
            </h1>
            <p class="text-xl text-indigo-100">
                {{ $posts->total() }} result{{ $posts->total() !== 1 ? 's' : '' }} found for "{{ $query }}"
            </p>
        </div>

        <!-- Search Form -->
        <div class="max-w-2xl mx-auto">
            <x-search-form :posts="$posts" />
        </div>
    </div>
</section>

<!-- Search Results -->
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
            <!-- Main Content -->
            <div class="lg:col-span-3">
                @if($posts->count())
                <!-- Search Stats -->
                <div
                    class="mb-8 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="text-blue-800 dark:text-blue-200 font-medium">
                            Found {{ $posts->total() }} article{{ $posts->total() !== 1 ? 's' : '' }}
                            @if($searchTime)
                            in {{ number_format($searchTime, 3) }} seconds
                            @endif
                        </span>
                    </div>
                </div>

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
                                        {!! $post->highlighted_title !!}
                                    </a>
                                </h2>

                                <p class="text-gray-600 dark:text-gray-300 mb-4">
                                    {!! $post->highlighted_excerpt !!}
                                </p>

                                @if($post->highlighted_content)
                                <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 p-3 mb-4">
                                    <p class="text-sm text-gray-700 dark:text-gray-300">
                                        <span class="font-medium">Found in content:</span>
                                        {!! $post->highlighted_content !!}
                                    </p>
                                </div>
                                @endif

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
                    {{ $posts->appends(request()->query())->links() }}
                </div>
                @endif
                @else
                <!-- No Results -->
                <div class="text-center py-12">
                    <svg class="mx-auto h-16 w-16 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <h3 class="text-2xl font-medium text-gray-900 dark:text-white mb-2">No articles found</h3>
                    <p class="text-gray-600 dark:text-gray-400 mb-6">
                        We couldn't find any articles matching "{{ $query }}". Try different keywords or browse our
                        categories.
                    </p>

                    <!-- Search Suggestions -->
                    <div class="max-w-md mx-auto mb-8">
                        <h4 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Search Tips:</h4>
                        <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                            <li>• Check your spelling</li>
                            <li>• Try more general keywords</li>
                            <li>• Use fewer keywords</li>
                            <li>• Browse by category instead</li>
                        </ul>
                    </div>

                    <div class="flex flex-col sm:flex-row justify-center gap-4">
                        <a href="{{ route('blog.index') }}" class="btn-primary">
                            View All Articles
                        </a>
                        <button onclick="document.querySelector('input[name=q]').focus()" class="btn-secondary">
                            Try Another Search
                        </button>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <!-- Popular Categories -->
                @if($categories->count())
                <div class="card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Browse by Category
                    </h3>
                    <ul class="space-y-2">
                        @foreach($categories as $category)
                        <li>
                            <a href="{{ route('blog.category', $category->slug) }}"
                                class="flex items-center justify-between text-gray-600 dark:text-gray-300 hover:text-blue-600 dark:hover:text-blue-400">
                                <span>{{ $category->name }}</span>
                                <span class="text-sm bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded">
                                    {{ $category->blog_posts_count }}
                                </span>
                            </a>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <!-- Popular Tags -->
                @if($popularTags->count())
                <div class="card p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Popular Tags
                    </h3>
                    <div class="flex flex-wrap gap-2">
                        @foreach($popularTags as $tag)
                        <a href="{{ route('blog.search') }}?q={{ urlencode($tag->name) }}"
                            class="inline-block px-3 py-1 text-sm font-medium rounded-full hover:opacity-80"
                            style="background-color: {{ $tag->color }}20; color: {{ $tag->color }}">
                            {{ $tag->name }}
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif

                <!-- Recent Articles -->
                @if($recentPosts->count())
                <div class="card p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                        Recent Articles
                    </h3>
                    <div class="space-y-4">
                        @foreach($recentPosts as $recentPost)
                        <div class="flex space-x-3">
                            @if($recentPost->featured_image)
                            <img src="{{ Storage::url($recentPost->featured_image) }}" alt="{{ $recentPost->title }}"
                                class="w-16 h-16 rounded-lg object-cover">
                            @else
                            <div
                                class="w-16 h-16 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            @endif
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                    <a href="{{ route('blog.show', $recentPost->slug) }}"
                                        class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ Str::limit($recentPost->title, 60) }}
                                    </a>
                                </h4>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
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