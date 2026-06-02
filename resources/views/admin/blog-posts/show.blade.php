@extends('layouts.admin')

@section('title', $blogPost->title . ' - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.blog-posts.index') }}" class="hover:text-gray-700">Blog Posts</a>
        <span>›</span>
        <span>{{ $blogPost->title }}</span>
    </div>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $blogPost->title }}</h1>
        <div class="flex space-x-2">
            <a href="{{ route('admin.blog-posts.edit', $blogPost) }}" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                    </path>
                </svg>
                Edit Post
            </a>
            @if($blogPost->status === 'published')
            <a href="{{ route('blog.show', $blogPost->slug) }}" target="_blank" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                </svg>
                View Live
            </a>
            @endif
        </div>
    </div>
</div>

<!-- Post Meta -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            <span class="status-badge status-{{ $blogPost->status }}">{{ ucfirst($blogPost->status) }}</span>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($blogPost->views) }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Views</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $blogPost->reading_time }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Min Read</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            @if($blogPost->published_at)
            {{ $blogPost->published_at->format('M j') }}
            @else
            <span class="text-gray-400">Not Set</span>
            @endif
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Published</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2">
        <!-- Post Preview -->
        <div class="admin-card overflow-hidden">
            @if($blogPost->featured_image)
            <div class="aspect-w-16 aspect-h-9">
                <img src="{{ Storage::url($blogPost->featured_image) }}" alt="{{ $blogPost->title }}"
                    class="w-full h-64 object-cover">
            </div>
            @endif

            <div class="p-6">
                <div class="flex items-center mb-4">
                    <span class="inline-block px-3 py-1 text-sm font-medium rounded-full mr-3"
                        style="background-color: {{ $blogPost->category->color }}20; color: {{ $blogPost->category->color }}">
                        {{ $blogPost->category->name }}
                    </span>
                    @if($blogPost->published_at)
                    <time class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $blogPost->published_at->format('M j, Y') }}
                    </time>
                    @endif
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ $blogPost->title }}</h2>

                <div class="prose dark:prose-invert max-w-none">
                    <p class="text-lg text-gray-600 dark:text-gray-300 mb-6">{{ $blogPost->excerpt }}</p>
                    <div class="content">
                        {!! nl2br(e($blogPost->content)) !!}
                    </div>
                </div>

                @if($blogPost->tags->count())
                <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Tags</h4>
                    <div class="flex flex-wrap gap-2">
                        @foreach($blogPost->tags as $tag)
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

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Post Details -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Post Details</h3>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Author</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->user->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->category->name }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->created_at->format('M j, Y g:i A')
                        }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Updated</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->updated_at->format('M j, Y g:i A')
                        }}</dd>
                </div>

                @if($blogPost->published_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Published</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->published_at->format('M j, Y g:i A')
                        }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- SEO Information -->
        @if($blogPost->meta_title || $blogPost->meta_description || $blogPost->meta_keywords)
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Information</h3>

            <dl class="space-y-3">
                @if($blogPost->meta_title)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta Title</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->meta_title }}</dd>
                </div>
                @endif

                @if($blogPost->meta_description)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta Description</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $blogPost->meta_description }}</dd>
                </div>
                @endif

                @if($blogPost->meta_keywords)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Keywords</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ implode(', ', $blogPost->meta_keywords) }}</dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

        <!-- Actions -->
        <div class="admin-card p-6">
            <div class="space-y-3">
                <a href="{{ route('admin.blog-posts.edit', $blogPost) }}" class="w-full btn-primary text-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                        </path>
                    </svg>
                    Edit Post
                </a>

                @if($blogPost->status === 'published')
                <a href="{{ route('blog.show', $blogPost->slug) }}" target="_blank"
                    class="w-full btn-secondary text-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    View Live Post
                </a>
                @endif

                <form method="POST" action="{{ route('admin.blog-posts.destroy', $blogPost) }}" class="w-full"
                    onsubmit="return confirm('Are you sure you want to delete this blog post?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Delete Post
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection