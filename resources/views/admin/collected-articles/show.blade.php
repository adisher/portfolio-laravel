@extends('layouts.admin')

@section('title', 'Article Details - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.collected-articles.index') }}" class="hover:text-gray-700">Collected Articles</a>
        <span>›</span>
        <span>{{ Str::limit($collectedArticle->title, 50) }}</span>
    </div>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Article Details</h1>
        <div class="flex space-x-2">
            @if($collectedArticle->status === 'pending')
            <form method="POST" action="{{ route('admin.collected-articles.approve', $collectedArticle) }}" class="inline">
                @csrf
                <button type="submit" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Approve
                </button>
            </form>
            @endif

            @if(in_array($collectedArticle->status, ['approved', 'pending']) && !$collectedArticle->blog_post_id)
            <a href="{{ route('admin.collected-articles.create-blog-post', $collectedArticle) }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                </svg>
                Create Blog Post
            </a>
            @endif
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Main Content -->
    <div class="lg:col-span-2">
        <div class="admin-card p-6">
            <div class="mb-6">
                <div class="flex items-center space-x-4 mb-4">
                    <span class="status-badge status-{{ $collectedArticle->status }}">
                        {{ ucfirst($collectedArticle->status) }}
                    </span>
                    <div class="flex items-center">
                        <div class="w-20 bg-gray-200 rounded-full h-2 mr-2">
                            <div class="h-2 rounded-full {{ $collectedArticle->relevance_score >= 80 ? 'bg-green-600' : ($collectedArticle->relevance_score >= 60 ? 'bg-yellow-600' : 'bg-red-600') }}" 
                                style="width: {{ $collectedArticle->relevance_score }}%"></div>
                        </div>
                        <span class="text-sm font-medium">{{ $collectedArticle->relevance_score }}% relevant</span>
                    </div>
                </div>

                <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">{{ $collectedArticle->title }}</h2>
                
                @if($collectedArticle->description)
                <div class="prose dark:prose-invert max-w-none mb-6">
                    <p class="text-lg text-gray-600 dark:text-gray-300">{{ $collectedArticle->description }}</p>
                </div>
                @endif

                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <h4 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Original Article</h4>
                    <a href="{{ $collectedArticle->url }}" target="_blank" 
                        class="text-blue-600 hover:text-blue-700 break-all">
                        {{ $collectedArticle->url }}
                    </a>
                </div>
            </div>

            @if($collectedArticle->curator_notes)
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-3">Curator Notes</h3>
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                    <p class="text-gray-700 dark:text-gray-300">{{ $collectedArticle->curator_notes }}</p>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Sidebar -->
    <div class="space-y-6">
        <!-- Article Details -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Article Information</h3>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Source</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $collectedArticle->rssSource->name }}</dd>
                </div>

                @if($collectedArticle->author)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Author</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $collectedArticle->author }}</dd>
                </div>
                @endif

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Originally Published</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $collectedArticle->published_at->format('M j, Y g:i A') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Collected</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $collectedArticle->created_at->format('M j, Y g:i A') }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Relevance Score</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $collectedArticle->relevance_score }}%</dd>
                </div>

                @if($collectedArticle->blog_post_id)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Blog Post</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">
                        <a href="{{ route('admin.blog-posts.edit', $collectedArticle->blog_post_id) }}" 
                            class="text-blue-600 hover:text-blue-700">
                            View Blog Post
                        </a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Actions -->
        <div class="admin-card p-6">
            <div class="space-y-3">
                @if($collectedArticle->status === 'pending')
                <form method="POST" action="{{ route('admin.collected-articles.approve', $collectedArticle) }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Approve Article
                    </button>
                </form>

                <form method="POST" action="{{ route('admin.collected-articles.reject', $collectedArticle) }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Reject Article
                    </button>
                </form>
                @endif

                @if(in_array($collectedArticle->status, ['approved', 'pending']) && !$collectedArticle->blog_post_id)
                <a href="{{ route('admin.collected-articles.create-blog-post', $collectedArticle) }}" 
                    class="w-full btn-primary text-center">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Create Blog Post
                </a>
                @endif

                @if($collectedArticle->blog_post_id)
                <a href="{{ route('admin.blog-posts.edit', $collectedArticle->blog_post_id) }}" 
                    class="w-full btn-secondary text-center">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Blog Post
                </a>
                @endif

                <a href="{{ $collectedArticle->url }}" target="_blank" class="w-full btn-secondary text-center">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                    View Original
                </a>
            </div>
        </div>
    </div>
</div>
@endsection