@extends('layouts.admin')

@section('title', $rssSource->name . ' - RSS Source Details')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.rss-sources.index') }}" class="hover:text-gray-700">RSS Sources</a>
        <span>›</span>
        <span>{{ $rssSource->name }}</span>
    </div>
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rssSource->name }}</h1>
        <div class="flex space-x-2">
            <form method="POST" action="{{ route('admin.rss-sources.fetch', $rssSource) }}" class="inline">
                @csrf
                <button type="submit" class="btn-secondary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Fetch Now
                </button>
            </form>
            <a href="{{ route('admin.rss-sources.edit', $rssSource) }}" class="btn-primary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                </svg>
                Edit Source
            </a>
        </div>
    </div>
</div>

<!-- Source Stats -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            <span class="status-badge {{ $rssSource->active ? 'status-published' : 'status-draft' }}">
                {{ $rssSource->active ? 'Active' : 'Inactive' }}
            </span>
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Status</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rssSource->collectedArticles->count() }}</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Total Articles</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rssSource->priority }}/10</div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Priority</div>
    </div>

    <div class="admin-card p-4 text-center">
        <div class="text-2xl font-bold text-gray-900 dark:text-white">
            @if($rssSource->last_fetched_at)
                {{ $rssSource->last_fetched_at->diffForHumans() }}
            @else
                <span class="text-gray-400">Never</span>
            @endif
        </div>
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">Last Fetched</div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Articles -->
    <div class="lg:col-span-2">
        <div class="admin-card">
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-medium text-gray-900 dark:text-white">Recent Articles</h2>
                    <a href="{{ route('admin.collected-articles.index', ['source' => $rssSource->id]) }}" 
                        class="text-blue-600 hover:text-blue-700 text-sm">View All</a>
                </div>
            </div>

            @if($rssSource->collectedArticles->count())
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($rssSource->collectedArticles as $article)
                    <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="font-medium text-gray-900 dark:text-white mb-2">
                                    <a href="{{ $article->url }}" target="_blank" class="hover:text-blue-600">
                                        {{ $article->title }}
                                    </a>
                                </h3>
                                @if($article->description)
                                <p class="text-sm text-gray-600 dark:text-gray-300 mb-3">
                                    {{ Str::limit($article->description, 150) }}
                                </p>
                                @endif
                                <div class="flex items-center space-x-4 text-xs text-gray-500">
                                    <span>{{ $article->published_at->format('M j, Y') }}</span>
                                    @if($article->author)
                                    <span>by {{ $article->author }}</span>
                                    @endif
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        {{ $article->relevance_score >= 80 ? 'bg-green-100 text-green-800' : 
                                           ($article->relevance_score >= 60 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $article->relevance_score }}% relevant
                                    </span>
                                </div>
                            </div>
                            <div class="ml-4">
                                <span class="status-badge status-{{ $article->status }}">
                                    {{ ucfirst($article->status) }}
                                </span>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            @else
                <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    <p class="text-lg font-medium">No articles collected yet</p>
                    <p class="text-sm">Fetch from this source to see articles here.</p>
                    <form method="POST" action="{{ route('admin.rss-sources.fetch', $rssSource) }}" class="mt-4">
                        @csrf
                        <button type="submit" class="btn-primary">Fetch Articles Now</button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    <!-- Source Details -->
    <div class="space-y-6">
        <!-- Source Information -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Source Details</h3>

            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Feed URL</dt>
                    <dd class="text-sm text-gray-900 dark:text-white break-all">
                        <a href="{{ $rssSource->url }}" target="_blank" class="text-blue-600 hover:text-blue-700">
                            {{ $rssSource->url }}
                        </a>
                    </dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Category</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($rssSource->category) }}</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Fetch Frequency</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">Every {{ $rssSource->fetch_frequency }} minutes</dd>
                </div>

                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Created</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $rssSource->created_at->format('M j, Y g:i A') }}</dd>
                </div>

                @if($rssSource->last_fetched_at)
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Last Fetched</dt>
                    <dd class="text-sm text-gray-900 dark:text-white">{{ $rssSource->last_fetched_at->format('M j, Y g:i A') }}</dd>
                </div>
                @endif
            </dl>
        </div>

        <!-- Quick Stats -->
        <div class="admin-card p-6">
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Article Statistics</h3>

            @php
                $pending = $rssSource->collectedArticles->where('status', 'pending')->count();
                $approved = $rssSource->collectedArticles->where('status', 'approved')->count();
                $rejected = $rssSource->collectedArticles->where('status', 'rejected')->count();
                $published = $rssSource->collectedArticles->where('status', 'published')->count();
            @endphp

            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Pending Review</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $pending }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Approved</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $approved }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Published</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $published }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Rejected</span>
                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $rejected }}</span>
                </div>
            </div>
        </div>

        <!-- Actions -->
        <div class="admin-card p-6">
            <div class="space-y-3">
                <form method="POST" action="{{ route('admin.rss-sources.fetch', $rssSource) }}" class="w-full">
                    @csrf
                    <button type="submit" class="w-full btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Fetch Articles Now
                    </button>
                </form>

                <a href="{{ route('admin.rss-sources.edit', $rssSource) }}" class="w-full btn-secondary text-center">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit Source
                </a>

                <a href="{{ route('admin.collected-articles.index', ['source' => $rssSource->id]) }}" 
                    class="w-full btn-secondary text-center">
                    <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                            d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                    </svg>
                    View All Articles
                </a>

                <form method="POST" action="{{ route('admin.rss-sources.destroy', $rssSource) }}" class="w-full"
                    onsubmit="return confirm('Are you sure? This will delete the source and all collected articles.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                        Delete Source
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection