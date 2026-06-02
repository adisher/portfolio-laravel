@extends('layouts.admin')

@section('title', 'Sync Logs - Sports Admin')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('admin.sports.dashboard') }}" class="hover:text-gray-700">Sports</a>
            <span>›</span>
            <span>Sync Logs</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">API Sync Logs</h1>
        <p class="text-gray-600 dark:text-gray-400">History of all sports data synchronization operations</p>
    </div>
    <a href="{{ route('admin.sports.dashboard') }}" class="btn-secondary">
        Back to Dashboard
    </a>
</div>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Sync Type</th>
                    <th>Status</th>
                    <th>Records Synced</th>
                    <th>API Calls</th>
                    <th>Error</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($logs as $log)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            {{ ucfirst($log->sync_type) }}
                        </span>
                    </td>
                    <td>
                        @if($log->status === 'success')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Success
                        </span>
                        @elseif($log->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                            Failed
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200">
                            {{ ucfirst($log->status) }}
                        </span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-900 dark:text-white">
                        {{ $log->records_synced ?? 0 }}
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $log->api_calls_used ?? 0 }}
                    </td>
                    <td class="text-sm">
                        @if($log->error_message)
                        <span class="text-red-600 dark:text-red-400" title="{{ $log->error_message }}">
                            {{ Str::limit($log->error_message, 50) }}
                        </span>
                        @else
                        <span class="text-gray-400">-</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400" title="{{ $log->created_at->format('M j, Y g:i A') }}">
                        {{ $log->created_at->diffForHumans() }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <p class="text-lg font-medium">No sync logs yet</p>
                        <p class="text-sm">Sync logs will appear here after you run a data sync.</p>
                        <a href="{{ route('admin.sports.dashboard') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Go to Dashboard
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($logs->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $logs->links() }}
    </div>
    @endif
</div>
@endsection
