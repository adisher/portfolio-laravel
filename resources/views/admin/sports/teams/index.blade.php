@extends('layouts.admin')

@section('title', 'Teams - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Teams</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage sports teams</p>
    </div>
    <a href="{{ route('admin.sports.teams.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Team
    </a>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sport</label>
            <select name="sport" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                <option value="">All Sports</option>
                @foreach($sports as $sport)
                <option value="{{ $sport->slug }}" {{ request('sport') === $sport->slug ? 'selected' : '' }}>
                    {{ $sport->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search teams..."
                class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['sport', 'search']))
        <a href="{{ route('admin.sports.teams.index') }}" class="text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">Clear</a>
        @endif
    </form>
</div>

<!-- Teams Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Team</th>
                    <th>Sport</th>
                    <th>Country</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($teams as $team)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div class="flex items-center space-x-3">
                            @if($team->logo_url)
                            <img src="{{ $team->logo_url }}" alt="{{ $team->name }}"
                                class="w-10 h-10 rounded-full object-cover">
                            @else
                            <div class="w-10 h-10 rounded-full flex items-center justify-center text-white text-xs font-bold"
                                style="background-color: {{ $team->primary_color ?? '#6B7280' }}">
                                {{ $team->abbreviation }}
                            </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $team->name }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">({{ $team->abbreviation }})</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="status-badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                            {{ $team->sport->name }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $team->country_code }}
                    </td>
                    <td>
                        @if($team->is_active)
                        <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                        @else
                        <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.sports.teams.edit', $team) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.sports.teams.destroy', $team) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this team?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No teams found</p>
                        <p class="text-sm">Get started by creating your first team.</p>
                        <a href="{{ route('admin.sports.teams.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add New Team
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($teams->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $teams->links() }}
    </div>
    @endif
</div>
@endsection
