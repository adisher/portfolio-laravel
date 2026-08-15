@extends('layouts.admin')

@section('title', 'Publish Board - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('admin.social.index') }}" class="hover:text-gray-700">Social Accounts</a>
            <span>›</span>
            <span>Publish Board</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Publish Board</h1>
        <p class="text-gray-600 dark:text-gray-400">Post a published article to a connected account on demand</p>
    </div>
</div>

@if($accounts->isEmpty())
    <div class="admin-card p-6 text-center text-gray-500 dark:text-gray-400">
        No active accounts. <a href="{{ route('admin.social.create') }}" class="text-blue-600 hover:underline">Connect one first.</a>
    </div>
@else
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Human views</th>
                    <th>Published</th>
                    @foreach($accounts as $account)
                        <th>
                            {{ $account->name }}
                            <span class="block text-xs font-normal text-gray-400 normal-case">{{ $account->platformLabel() }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($articles as $article)
                    @php $byAccount = $article->socialPosts->keyBy('social_account_id'); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="max-w-sm">
                            <div class="font-medium text-gray-900 dark:text-white truncate">{{ $article->title }}</div>
                            <div class="text-xs text-gray-400">{{ optional($article->category)->name }}</div>
                        </td>
                        <td class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($article->views) }}</td>
                        <td class="text-sm text-gray-500 dark:text-gray-400">{{ optional($article->published_at)->format('M j, Y') }}</td>
                        @foreach($accounts as $account)
                            @php $log = $byAccount->get($account->id); @endphp
                            <td>
                                @if($log && $log->status === 'posted')
                                    <span class="status-badge status-published">Posted</span>
                                    @if($log->external_url)
                                        <a href="{{ $log->external_url }}" target="_blank" rel="noopener" class="block text-xs text-blue-600 hover:underline mt-1">View</a>
                                    @endif
                                @else
                                    <form method="POST" action="{{ route('admin.social.post-now') }}" class="inline"
                                        onsubmit="return confirm('Post this article to {{ $account->name }} now?')">
                                        @csrf
                                        <input type="hidden" name="blog_post_id" value="{{ $article->id }}">
                                        <input type="hidden" name="social_account_id" value="{{ $account->id }}">
                                        <button type="submit" class="btn-secondary text-xs py-1 px-3">Post now</button>
                                    </form>
                                    @if($log && $log->status === 'failed')
                                        <div class="text-xs text-red-500 mt-1 max-w-[12rem] truncate" title="{{ $log->error }}">{{ $log->error }}</div>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + $accounts->count() }}" class="text-center py-8 text-gray-500 dark:text-gray-400">
                            No published articles.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($articles->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $articles->links() }}</div>
    @endif
</div>
@endif
@endsection
