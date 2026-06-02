@extends('layouts.admin')

@section('title', 'Tournaments')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Tournaments</h1>
        <a href="{{ route('admin.sports.tournaments.create') }}" class="btn-primary inline-flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Add New Tournament
        </a>
    </div>

    {{-- Filters --}}
    <div class="admin-card">
        <form method="GET" action="{{ route('admin.sports.tournaments.index') }}" class="flex flex-wrap items-end gap-4">
            <div class="flex-1 min-w-[180px]">
                <label for="sport" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sport</label>
                <select id="sport" name="sport" class="form-select w-full">
                    <option value="">All Sports</option>
                    @foreach($sports as $sport)
                        <option value="{{ $sport->slug }}" @selected(request('sport') === $sport->slug)>
                            {{ $sport->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label for="search" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Search tournaments..." class="form-input w-full">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary">
                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                    </svg>
                    Filter
                </button>
                @if(request()->hasAny(['sport', 'search']))
                    <a href="{{ route('admin.sports.tournaments.index') }}" class="btn-secondary text-sm">Clear</a>
                @endif
            </div>
        </form>
    </div>

    {{-- Tournaments Table --}}
    <div class="admin-card overflow-hidden">
        @if($tournaments->count())
            <div class="overflow-x-auto">
                <table class="admin-table w-full">
                    <thead>
                        <tr>
                            <th class="text-left">Tournament</th>
                            <th class="text-left">Sport</th>
                            <th class="text-left">Season</th>
                            <th class="text-left">Dates</th>
                            <th class="text-center">Featured</th>
                            <th class="text-center">Status</th>
                            <th class="text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tournaments as $tournament)
                            <tr>
                                {{-- Tournament Name + Logo --}}
                                <td>
                                    <div class="flex items-center gap-3">
                                        @if($tournament->logo_url)
                                            <img src="{{ $tournament->logo_url }}" alt="{{ $tournament->name }}" class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                                        @else
                                            <div class="w-10 h-10 rounded-lg bg-gray-100 dark:bg-gray-700 flex items-center justify-center border border-gray-200 dark:border-gray-600">
                                                <svg class="w-5 h-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">{{ $tournament->name }}</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">{{ $tournament->slug }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Sport --}}
                                <td>
                                    <span class="status-badge bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300">
                                        {{ $tournament->sport->name }}
                                    </span>
                                </td>

                                {{-- Season --}}
                                <td class="text-gray-700 dark:text-gray-300">
                                    {{ $tournament->season ?? '—' }}
                                </td>

                                {{-- Dates --}}
                                <td class="text-sm text-gray-600 dark:text-gray-400">
                                    @if($tournament->start_date && $tournament->end_date)
                                        {{ \Carbon\Carbon::parse($tournament->start_date)->format('M d, Y') }}
                                        <span class="text-gray-400 dark:text-gray-500">—</span>
                                        {{ \Carbon\Carbon::parse($tournament->end_date)->format('M d, Y') }}
                                    @elseif($tournament->start_date)
                                        {{ \Carbon\Carbon::parse($tournament->start_date)->format('M d, Y') }}
                                    @else
                                        —
                                    @endif
                                </td>

                                {{-- Featured --}}
                                <td class="text-center">
                                    @if($tournament->is_featured)
                                        <svg class="w-5 h-5 text-yellow-500 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                                        </svg>
                                    @endif
                                </td>

                                {{-- Status --}}
                                <td class="text-center">
                                    @if($tournament->is_active)
                                        <span class="status-badge bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">Active</span>
                                    @else
                                        <span class="status-badge bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">Inactive</span>
                                    @endif
                                </td>

                                {{-- Actions --}}
                                <td>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('admin.sports.tournaments.edit', $tournament) }}" class="btn-secondary inline-flex items-center gap-1 text-sm" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                            </svg>
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.sports.tournaments.destroy', $tournament) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this tournament?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-secondary inline-flex items-center gap-1 text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300" title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $tournaments->withQueryString()->links() }}
            </div>
        @else
            {{-- Empty State --}}
            <div class="px-6 py-12 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">No tournaments found</h3>
                <p class="text-gray-500 dark:text-gray-400 mb-4">
                    @if(request()->hasAny(['sport', 'search']))
                        No tournaments match your current filters. Try adjusting your search criteria.
                    @else
                        Get started by creating your first tournament.
                    @endif
                </p>
                @if(request()->hasAny(['sport', 'search']))
                    <a href="{{ route('admin.sports.tournaments.index') }}" class="btn-secondary">Clear Filters</a>
                @else
                    <a href="{{ route('admin.sports.tournaments.create') }}" class="btn-primary">Add New Tournament</a>
                @endif
            </div>
        @endif
    </div>
</div>
@endsection
