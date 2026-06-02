@extends('layouts.admin')

@section('title', 'Matches - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Matches</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage sports matches and fixtures</p>
    </div>
    <a href="{{ route('admin.sports.matches.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Match
    </a>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sport</label>
            <select name="sport" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Sports</option>
                @foreach($sports as $sport)
                <option value="{{ $sport->slug }}" {{ request('sport') === $sport->slug ? 'selected' : '' }}>
                    {{ $sport->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select name="status" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Statuses</option>
                <option value="scheduled" {{ request('status') === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="live" {{ request('status') === 'live' ? 'selected' : '' }}>Live</option>
                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="postponed" {{ request('status') === 'postponed' ? 'selected' : '' }}>Postponed</option>
                <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                <option value="abandoned" {{ request('status') === 'abandoned' ? 'selected' : '' }}>Abandoned</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search matches..."
                class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['sport', 'status', 'search']))
        <a href="{{ route('admin.sports.matches.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
        @endif
    </form>
</div>

<!-- Matches Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Match</th>
                    <th>Sport</th>
                    <th>Tournament</th>
                    <th>Status</th>
                    <th>Scheduled At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($matches as $match)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $match->title }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ $match->homeTeam->name ?? 'TBD' }}
                                @if($match->home_score !== null && $match->away_score !== null)
                                    <span class="font-semibold text-gray-700 dark:text-gray-300">{{ $match->home_score }} - {{ $match->away_score }}</span>
                                @else
                                    <span>vs</span>
                                @endif
                                {{ $match->awayTeam->name ?? 'TBD' }}
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ $match->sport->name }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $match->tournament->name ?? '—' }}
                    </td>
                    <td>
                        @switch($match->status)
                            @case('scheduled')
                                <span class="status-badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">Scheduled</span>
                                @break
                            @case('live')
                                <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Live</span>
                                @break
                            @case('completed')
                                <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300">Completed</span>
                                @break
                            @case('postponed')
                                <span class="status-badge bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300">Postponed</span>
                                @break
                            @case('cancelled')
                                <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Cancelled</span>
                                @break
                            @case('abandoned')
                                <span class="status-badge bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300">Abandoned</span>
                                @break
                            @default
                                <span class="status-badge bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-300">{{ ucfirst($match->status) }}</span>
                        @endswitch
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        @if($match->scheduled_at)
                        {{ $match->scheduled_at->format('M j, Y') }}
                        <div class="text-xs">{{ $match->scheduled_at->format('g:i A') }}</div>
                        @else
                        <span class="text-gray-400">Not scheduled</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('sports.matches.show', $match->slug ?? $match->id) }}" target="_blank"
                                class="text-gray-400 hover:text-blue-600" title="View on Frontend">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </a>
                            <a href="{{ route('admin.sports.matches.edit', $match) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.sports.matches.destroy', $match) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this match?')">
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
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        <p class="text-lg font-medium">No matches found</p>
                        <p class="text-sm">Get started by creating your first match.</p>
                        <a href="{{ route('admin.sports.matches.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add New Match
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($matches->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $matches->links() }}
    </div>
    @endif
</div>
@endsection
