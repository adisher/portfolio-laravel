@extends('layouts.admin')

@section('title', 'Collected Articles - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Collected Articles</h1>
        <p class="text-gray-600 dark:text-gray-400">Review and curate articles from RSS sources</p>
    </div>
    <a href="{{ route('admin.rss-sources.index') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
        </svg>
        Manage RSS Sources
    </a>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select name="status" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Status</option>
                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending Review</option>
                <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="published" {{ request('status') === 'published' ? 'selected' : '' }}>Published</option>
                <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Source</label>
            <select name="source" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Sources</option>
                @foreach($sources as $source)
                <option value="{{ $source->id }}" {{ request('source') == $source->id ? 'selected' : '' }}>
                    {{ $source->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Min Score</label>
            <select name="min_score" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">Any Score</option>
                <option value="80" {{ request('min_score') == '80' ? 'selected' : '' }}>80% or higher</option>
                <option value="70" {{ request('min_score') == '70' ? 'selected' : '' }}>70% or higher</option>
                <option value="60" {{ request('min_score') == '60' ? 'selected' : '' }}>60% or higher</option>
                <option value="50" {{ request('min_score') == '50' ? 'selected' : '' }}>50% or higher</option>
            </select>
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['status', 'source', 'min_score']))
        <a href="{{ route('admin.collected-articles.index') }}" class="text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<!-- Articles Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Source</th>
                    <th>Score</th>
                    <th>Published</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($articles as $article)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white mb-1">
                                <a href="{{ $article->url }}" target="_blank" class="hover:text-blue-600">
                                    {{ Str::limit($article->title, 60) }}
                                </a>
                            </div>
                            @if($article->description)
                            <div class="text-sm text-gray-500 dark:text-gray-400 mb-2">
                                {{ Str::limit($article->description, 120) }}
                            </div>
                            @endif
                            @if($article->author)
                            <div class="text-xs text-gray-400">by {{ $article->author }}</div>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ $article->rssSource->name }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center">
                            <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                <div class="h-2 rounded-full {{ $article->relevance_score >= 80 ? 'bg-green-600' : ($article->relevance_score >= 60 ? 'bg-yellow-600' : 'bg-red-600') }}" 
                                    style="width: {{ $article->relevance_score }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $article->relevance_score }}%</span>
                        </div>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $article->published_at->format('M j, Y') }}
                        <div class="text-xs">{{ $article->published_at->diffForHumans() }}</div>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $article->status }}">
                            {{ ucfirst($article->status) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            @if($article->status === 'pending')
                            <form method="POST" action="{{ route('admin.collected-articles.approve', $article) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-green-600" title="Approve">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                </button>
                            </form>
                            <form method="POST" action="{{ route('admin.collected-articles.reject', $article) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Reject">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </form>
                            @endif

                            @if(in_array($article->status, ['approved', 'pending']) && !$article->blog_post_id)
                            <a href="{{ route('admin.collected-articles.create-blog-post', $article) }}" 
                                class="text-gray-400 hover:text-blue-600" title="Create Blog Post">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </a>
                            @endif

                            @if($article->blog_post_id)
                            <a href="{{ route('admin.blog-posts.edit', $article->blog_post_id) }}" 
                                class="text-gray-400 hover:text-purple-600" title="Edit Blog Post">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            @endif

                            <a href="{{ route('admin.collected-articles.show', $article) }}" 
                                class="text-gray-400 hover:text-gray-600" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9.5a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
        </svg>
                        <p class="text-lg font-medium">No articles found</p>
                        <p class="text-sm">Articles will appear here after fetching from RSS sources.</p>
                        <a href="{{ route('admin.rss-sources.index') }}" 
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Manage RSS Sources
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $articles->appends(request()->query())->links() }}
    </div>
    @endif
</div>
@endsection