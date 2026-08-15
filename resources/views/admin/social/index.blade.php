@extends('layouts.admin')

@section('title', 'Social Accounts - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Social Accounts</h1>
        <p class="text-gray-600 dark:text-gray-400">Connect destinations and auto-post published articles</p>
    </div>
    <div class="flex space-x-3">
        <a href="{{ route('admin.social.publish') }}" class="btn-secondary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
            </svg>
            Publish Board
        </a>
        <a href="{{ route('admin.social.create') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
            </svg>
            Connect Account
        </a>
    </div>
</div>

<div class="admin-card overflow-hidden mb-8">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Platform</th>
                    <th>Posted</th>
                    <th>Auto-post gate</th>
                    <th>Last posted</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($accounts as $account)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $account->name }}</div>
                        <div class="text-xs text-gray-400">Page ID: {{ $account->credential('page_id') ?: '-' }}</div>
                    </td>
                    <td>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ $account->platformLabel() }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">{{ number_format($account->posted_count) }}</td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">≥ {{ $account->min_human_views }} human views</td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $account->last_posted_at ? $account->last_posted_at->diffForHumans() : '-' }}
                    </td>
                    <td>
                        <div class="flex flex-col gap-1">
                            <span class="status-badge {{ $account->is_active ? 'status-published' : 'status-draft' }}">
                                {{ $account->is_active ? 'Active' : 'Inactive' }}
                            </span>
                            @if($account->allowsAutomatedPosting())
                                <span class="text-xs {{ $account->auto_post_enabled ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                    Auto-post {{ $account->auto_post_enabled ? 'ON' : 'off' }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400">Manual only</span>
                            @endif
                            @if($account->token_expires_at)
                                @php $days = (int) floor(now()->floatDiffInDays($account->token_expires_at, false)); @endphp
                                <span class="text-xs {{ $days < 0 ? 'text-red-600 dark:text-red-400' : ($days <= 14 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-400') }}">
                                    Token {{ $days < 0 ? 'expired' : 'expires ' . $account->token_expires_at->diffForHumans() }}
                                </span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            @if($account->allowsAutomatedPosting())
                            <form method="POST" action="{{ route('admin.social.toggle-auto', $account) }}" class="inline">
                                @csrf @method('PATCH')
                                <button type="submit" class="text-gray-400 hover:text-green-600" title="Toggle auto-posting">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </button>
                            </form>
                            @endif
                            <a href="{{ route('admin.social.edit', $account) }}" class="text-gray-400 hover:text-blue-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.social.destroy', $account) }}" class="inline"
                                onsubmit="return confirm('Remove this account? Its post history is deleted too.')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Remove">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <p class="text-lg font-medium">No social accounts connected</p>
                        <p class="text-sm">Connect a Facebook Page to start publishing your articles.</p>
                        <a href="{{ route('admin.social.create') }}" class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Connect Account
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Recent activity</h2>
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Article</th>
                    <th>Account</th>
                    <th>Trigger</th>
                    <th>Status</th>
                    <th>When</th>
                    <th>Link</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recent as $log)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="max-w-xs truncate text-gray-900 dark:text-white">{{ optional($log->blogPost)->title ?? '-' }}</td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">{{ optional($log->socialAccount)->name ?? '-' }}</td>
                    <td class="text-xs uppercase text-gray-400">{{ $log->trigger }}</td>
                    <td>
                        <span class="status-badge {{ $log->status === 'posted' ? 'status-published' : 'status-draft' }}">
                            {{ ucfirst($log->status) }}
                        </span>
                        @if($log->status === 'failed' && $log->error)
                            <div class="text-xs text-red-500 mt-1 max-w-xs truncate" title="{{ $log->error }}">{{ $log->error }}</div>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">{{ $log->created_at->diffForHumans() }}</td>
                    <td>
                        @if($log->external_url)
                            <a href="{{ $log->external_url }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline text-sm">View</a>
                        @else
                            <span class="text-gray-400 text-sm">-</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">No posts yet.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
