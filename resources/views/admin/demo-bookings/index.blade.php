@extends('layouts.admin')

@section('title', 'Demo Bookings')

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demo Bookings</h1>
        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            @if($upcomingCount > 0)
                <span class="text-yellow-600 font-medium">{{ $upcomingCount }} upcoming</span>
            @else
                No upcoming demos
            @endif
        </p>
    </div>
    <a href="{{ route('admin.demo-availability.edit') }}" class="btn-secondary text-sm">
        Availability Settings
    </a>
</div>

{{-- Tabs --}}
<div class="border-b border-gray-200 dark:border-gray-700 mb-6">
    <nav class="-mb-px flex space-x-6">
        @foreach(['upcoming' => 'Upcoming', 'past' => 'Past', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
        <a href="{{ route('admin.demo-bookings.index', array_merge(request()->only('search'), ['tab' => $key])) }}"
           class="pb-3 px-1 text-sm font-medium border-b-2 transition-colors
                  {{ $tab === $key
                     ? 'border-blue-600 text-blue-600 dark:text-blue-400 dark:border-blue-400'
                     : 'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400' }}">
            {{ $label }}
        </a>
        @endforeach
    </nav>
</div>

{{-- Search --}}
<form method="GET" action="{{ route('admin.demo-bookings.index') }}" class="mb-6 flex gap-3">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <input type="text" name="search" value="{{ $search }}"
           placeholder="Search by name, email, or company…"
           class="flex-1 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    <button type="submit" class="btn-primary text-sm">Search</button>
    @if($search)
    <a href="{{ route('admin.demo-bookings.index', ['tab' => $tab]) }}" class="btn-secondary text-sm">Clear</a>
    @endif
</form>

<div class="admin-card overflow-hidden">
    @if($bookings->count())
    <table class="admin-table w-full">
        <thead>
            <tr>
                <th>Date & Time</th>
                <th>Name</th>
                <th>Email</th>
                <th>Company</th>
                <th>Product</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bookings as $booking)
            <tr>
                <td class="whitespace-nowrap">
                    <div class="font-medium text-gray-900 dark:text-white text-sm">
                        {{ $booking->scheduledAtFormatted('d M Y') }}
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $booking->scheduledAtFormatted('g:i A T') }}
                    </div>
                </td>
                <td class="font-medium text-gray-900 dark:text-white">{{ $booking->name }}</td>
                <td class="text-sm text-gray-600 dark:text-gray-300">{{ $booking->email }}</td>
                <td class="text-sm text-gray-600 dark:text-gray-300">{{ $booking->company ?? '—' }}</td>
                <td class="text-sm text-gray-600 dark:text-gray-300">{{ $booking->project?->title ?? '—' }}</td>
                <td class="text-sm text-gray-600 dark:text-gray-300">{{ $booking->plan_interest ?? '—' }}</td>
                <td>
                    @php
                        $colors = [
                            'confirmed'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'cancelled'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                            'completed'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                            'no_show'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        ];
                    @endphp
                    <span class="status-badge {{ $colors[$booking->status] ?? '' }}">
                        {{ ucfirst(str_replace('_', ' ', $booking->status)) }}
                    </span>
                </td>
                <td>
                    <a href="{{ route('admin.demo-bookings.show', $booking) }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 text-sm font-medium">View</a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $bookings->links() }}
    </div>
    @else
    <div class="text-center py-16 text-gray-400 dark:text-gray-500">
        <svg class="w-12 h-12 mx-auto mb-4 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p class="font-medium">No bookings found</p>
        @if($search)<p class="text-sm mt-1">Try a different search term.</p>@endif
    </div>
    @endif
</div>
@endsection
