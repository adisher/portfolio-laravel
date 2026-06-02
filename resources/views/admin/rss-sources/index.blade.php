@extends('layouts.admin')

@section('title', 'RSS Sources - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">RSS Sources</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage external content sources for curation</p>
    </div>
    <div class="flex space-x-3">
        <form method="POST" action="{{ route('admin.rss-sources.fetch-all') }}" class="inline">
            @csrf
            <button type="submit" class="btn-secondary">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                        d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Fetch All
            </button>
        </form>
        <a href="{{ route('admin.rss-sources.create') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Add RSS Source
        </a>
    </div>
</div>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Source</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Articles</th>
                    <th>Last Fetched</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($sources as $source)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $source->name }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $source->url }}</div>
                            <div class="text-xs text-gray-400">Every {{ $source->fetch_frequency }} minutes</div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($source->category) }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center">
                            @for($i = 1; $i <= 10; $i++)
                                <svg class="w-3 h-3 {{ $i <= $source->priority ? 'text-yellow-400' : 'text-gray-300' }}" 
                                    fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                </svg>
                            @endfor
                            <span class="ml-1 text-sm text-gray-500">{{ $source->priority }}/10</span>
                        </div>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($source->collected_articles_count) }}
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        @if($source->last_fetched_at)
                            {{ $source->last_fetched_at->diffForHumans() }}
                        @else
                            <span class="text-gray-400">Never</span>
                        @endif
                    </td>
                    <td>
                        <span class="status-badge {{ $source->active ? 'status-published' : 'status-draft' }}">
                            {{ $source->active ? 'Active' : 'Inactive' }}
                        </span>
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <form method="POST" action="{{ route('admin.rss-sources.fetch', $source) }}" class="inline">
                                @csrf
                                <button type="submit" class="text-gray-400 hover:text-blue-600" title="Fetch Now">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                </button>
                            </form>
                            <a href="{{ route('admin.rss-sources.show', $source) }}" 
                                class="text-gray-400 hover:text-blue-600" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="{{ route('admin.rss-sources.edit', $source) }}" 
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.rss-sources.destroy', $source) }}" class="inline"
                                onsubmit="return confirm('Are you sure? This will also delete all collected articles from this source.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"></path>
                        </svg>
                        <p class="text-lg font-medium">No RSS sources found</p>
                        <p class="text-sm">Add your first RSS source to start collecting content.</p>
                        <a href="{{ route('admin.rss-sources.create') }}" 
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add RSS Source
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($sources->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $sources->links() }}
    </div>
    @endif
</div>
@endsection