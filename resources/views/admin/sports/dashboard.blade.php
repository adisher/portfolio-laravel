@extends('layouts.admin')

@section('title', 'Sports Dashboard - Admin Panel')

@section('content')
{{-- Header --}}
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Sports Dashboard</h1>
    <p class="text-gray-600 dark:text-gray-400">Monitor live matches, sync data, and manage sports content.</p>
</div>

{{-- Stats Grid --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    {{-- Live Matches --}}
    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-red-100 dark:bg-red-900">
                <svg class="w-8 h-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Live Matches</p>
                <div class="flex items-center">
                    <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['live_matches'] }}</p>
                    @if($stats['live_matches'] > 0)
                        <span class="ml-2 relative flex h-3 w-3">
                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                            <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                        </span>
                    @endif
                </div>
                <p class="text-sm {{ $stats['live_matches'] > 0 ? 'text-red-600' : 'text-gray-500' }}">
                    {{ $stats['live_matches'] > 0 ? 'In progress' : 'No live matches' }}
                </p>
            </div>
        </div>
    </div>

    {{-- Today's Matches --}}
    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-blue-100 dark:bg-blue-900">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Matches</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['today_matches'] }}</p>
                <p class="text-sm text-blue-600">Scheduled today</p>
            </div>
        </div>
    </div>

    {{-- Upcoming Matches --}}
    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-green-100 dark:bg-green-900">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Upcoming Matches</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['upcoming_matches'] }}</p>
                <p class="text-sm text-green-600">Coming up</p>
            </div>
        </div>
    </div>

    {{-- Total Matches --}}
    <div class="admin-card p-6">
        <div class="flex items-center">
            <div class="p-3 rounded-full bg-purple-100 dark:bg-purple-900">
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                </svg>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Matches</p>
                <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_matches'] }}</p>
                <p class="text-sm text-purple-600">All time</p>
            </div>
        </div>
    </div>
</div>

{{-- Sync Controls --}}
<div class="admin-card p-6 mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sync Controls</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Manually trigger data synchronization from the sports API.</p>

    <div class="flex flex-wrap items-end gap-4">
        {{-- Sport Filter --}}
        <div>
            <label for="sync-sport-filter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sport Filter (optional)</label>
            <select id="sync-sport-filter" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 text-sm">
                <option value="">All Sports</option>
                @foreach($sports as $sport)
                    <option value="{{ $sport->id }}">{{ $sport->name }}</option>
                @endforeach
            </select>
        </div>

        {{-- Sync Buttons --}}
        <form method="POST" action="{{ route('admin.sports.sync', 'teams') }}" class="inline">
            @csrf
            <input type="hidden" name="sport_id" class="sync-sport-input" value="">
            <button type="submit" class="btn-secondary text-sm">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Sync Teams
            </button>
        </form>

        <form method="POST" action="{{ route('admin.sports.sync', 'tournaments') }}" class="inline">
            @csrf
            <input type="hidden" name="sport_id" class="sync-sport-input" value="">
            <button type="submit" class="btn-secondary text-sm">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"></path>
                </svg>
                Sync Tournaments
            </button>
        </form>

        <form method="POST" action="{{ route('admin.sports.sync', 'schedule') }}" class="inline">
            @csrf
            <input type="hidden" name="sport_id" class="sync-sport-input" value="">
            <button type="submit" class="btn-primary text-sm">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Sync Schedule
            </button>
        </form>

        <form method="POST" action="{{ route('admin.sports.sync', 'live') }}" class="inline">
            @csrf
            <input type="hidden" name="sport_id" class="sync-sport-input" value="">
            <button type="submit" class="btn-primary text-sm">
                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Sync Live
            </button>
        </form>
    </div>
</div>

{{-- Live Matches Table --}}
@if($liveMatches->count())
<div class="admin-card mb-8">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Live Matches</h2>
                <span class="ml-2 relative flex h-3 w-3">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                </span>
            </div>
            <a href="{{ route('admin.sports.matches.index', ['status' => 'live']) }}" class="text-sm text-blue-600 hover:text-blue-500">View all live</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sport</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Match</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tournament</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Scheduled At</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($liveMatches as $match)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 cursor-pointer"
                    onclick="window.location='{{ route('admin.sports.matches.edit', $match) }}'">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            @if($match->sport->icon)
                                <span class="mr-2">{{ $match->sport->icon }}</span>
                            @endif
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $match->sport->name }}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm">
                            <div class="font-medium text-gray-900 dark:text-white">{{ $match->title }}</div>
                            <div class="text-gray-600 dark:text-gray-400 mt-1">
                                <span class="font-semibold">{{ $match->homeTeam->name }}</span>
                                <span class="mx-1 text-lg font-bold text-blue-600 dark:text-blue-400">{{ $match->formatted_home_score }}</span>
                                <span class="text-gray-400">-</span>
                                <span class="mx-1 text-lg font-bold text-blue-600 dark:text-blue-400">{{ $match->formatted_away_score }}</span>
                                <span class="font-semibold">{{ $match->awayTeam->name }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $match->tournament->name ?? 'N/A' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            {{ ucfirst($match->status) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $match->scheduled_at->format('M d, Y H:i') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Sports Overview --}}
<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Sports Overview</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($sports as $sport)
        <a href="{{ route('admin.sports.matches.index', ['sport_id' => $sport->id]) }}"
            class="admin-card p-5 hover:shadow-md transition-shadow group">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center">
                    @if($sport->icon)
                        <span class="text-2xl mr-3">{{ $sport->icon }}</span>
                    @else
                        <div class="p-2 rounded-full bg-blue-100 dark:bg-blue-900 mr-3">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                        </div>
                    @endif
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        {{ $sport->name }}
                    </h3>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <span class="text-2xl font-bold text-gray-900 dark:text-white">{{ $sport->matches_count }}</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">{{ Str::plural('match', $sport->matches_count) }}</span>
            </div>
            <div class="mt-2 text-xs text-blue-600 dark:text-blue-400 opacity-0 group-hover:opacity-100 transition-opacity">
                View matches →
            </div>
        </a>
        @endforeach
    </div>
</div>

{{-- Recent Sync Logs --}}
<div class="admin-card">
    <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Recent Sync Logs</h2>
            <a href="{{ route('admin.sports.sync-logs') }}" class="text-sm text-blue-600 hover:text-blue-500">View all logs</a>
        </div>
    </div>
    <div class="overflow-x-auto">
        <table class="admin-table w-full">
            <thead>
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sync Type</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Records Synced</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">API Calls</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Error</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Time</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($recentSyncs as $sync)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900 dark:text-white capitalize">{{ $sync->sync_type }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($sync->status === 'success')
                            <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                Success
                            </span>
                        @elseif($sync->status === 'failed')
                            <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200">
                                Failed
                            </span>
                        @else
                            <span class="status-badge inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200">
                                {{ ucfirst($sync->status) }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                        {{ number_format($sync->records_synced) }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                        {{ $sync->api_calls_used }}
                    </td>
                    <td class="px-6 py-4">
                        @if($sync->error_message)
                            <span class="text-sm text-red-600 dark:text-red-400" title="{{ $sync->error_message }}">
                                {{ Str::limit($sync->error_message, 50) }}
                            </span>
                        @else
                            <span class="text-sm text-gray-400 dark:text-gray-500">--</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $sync->created_at->diffForHumans() }}
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                        No sync logs yet. Use the sync controls above to start syncing data.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Update hidden sport_id inputs when the sport filter dropdown changes
    document.getElementById('sync-sport-filter').addEventListener('change', function() {
        const sportId = this.value;
        document.querySelectorAll('.sync-sport-input').forEach(function(input) {
            input.value = sportId;
        });
    });
</script>
@endpush
