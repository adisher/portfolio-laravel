@extends('layouts.admin')

@section('title', 'Demo Availability Settings')

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.demo-bookings.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demo Availability Settings</h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Weekly schedule --}}
    <div class="lg:col-span-2">
        <form action="{{ route('admin.demo-availability.update') }}" method="POST" class="space-y-6">
            @csrf @method('PUT')

            <div class="admin-card p-6">
                <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-5">Weekly Schedule</h2>

                {{-- Active toggle --}}
                <div class="flex items-center justify-between mb-6 pb-5 border-b border-gray-200 dark:border-gray-700">
                    <div>
                        <p class="font-medium text-gray-900 dark:text-white text-sm">Accept Demo Bookings</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">When disabled, the scheduling modal will not be shown.</p>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" class="sr-only peer" {{ $availability->is_active ? 'checked' : '' }}>
                        <div class="w-11 h-6 bg-gray-200 peer-focus:ring-2 peer-focus:ring-blue-500 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600 dark:bg-gray-700"></div>
                    </label>
                </div>

                {{-- Days of week --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Available Days</label>
                    <div class="flex flex-wrap gap-2">
                        @foreach([1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat', 7 => 'Sun'] as $num => $label)
                        <label class="cursor-pointer">
                            <input type="checkbox" name="days_of_week[]" value="{{ $num }}" class="sr-only peer"
                                   {{ in_array($num, $availability->days_of_week ?? []) ? 'checked' : '' }}>
                            <span class="inline-block px-4 py-2 rounded-lg text-sm font-medium border transition-colors peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:border-blue-400">
                                {{ $label }}
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Time range --}}
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Start Time</label>
                        <input type="time" name="start_time" value="{{ substr($availability->start_time, 0, 5) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">End Time</label>
                        <input type="time" name="end_time" value="{{ substr($availability->end_time, 0, 5) }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Slot duration --}}
                <div class="mb-5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slot Duration</label>
                    <div class="flex gap-3">
                        @foreach([15, 30, 45, 60] as $dur)
                        <label class="cursor-pointer">
                            <input type="radio" name="slot_duration" value="{{ $dur }}" class="sr-only peer"
                                   {{ $availability->slot_duration == $dur ? 'checked' : '' }}>
                            <span class="inline-block px-4 py-2 rounded-lg text-sm font-medium border transition-colors peer-checked:bg-blue-600 peer-checked:text-white peer-checked:border-blue-600 border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300">
                                {{ $dur }} min
                            </span>
                        </label>
                        @endforeach
                    </div>
                </div>

                {{-- Buffer + max per day --}}
                <div class="grid grid-cols-2 gap-4 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Buffer Between Demos
                            <span class="text-gray-400 font-normal">(minutes)</span>
                        </label>
                        <input type="number" name="buffer_minutes" min="0" max="60" step="5"
                               value="{{ $availability->buffer_minutes }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Max Bookings / Day</label>
                        <input type="number" name="max_per_day" min="1" max="20"
                               value="{{ $availability->max_per_day }}"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                {{-- Timezone --}}
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Timezone</label>
                    <input type="text" name="timezone" value="{{ $availability->timezone }}"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Asia/Karachi">
                    <p class="text-xs text-gray-400 mt-1">PHP timezone identifier (e.g. Asia/Karachi, America/New_York)</p>
                </div>

                <button type="submit" class="btn-primary">Save Settings</button>
            </div>
        </form>
    </div>

    {{-- Blocked dates --}}
    <div class="space-y-4">
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Block a Date</h2>
            <form action="{{ route('admin.demo-availability.blocked-dates.store') }}" method="POST" class="space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Date</label>
                    <input type="date" name="blocked_date" min="{{ date('Y-m-d') }}" required
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Start (optional)</label>
                        <input type="time" name="start_time"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">End (optional)</label>
                        <input type="time" name="end_time"
                               class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 dark:text-gray-400 mb-1">Reason (optional)</label>
                    <input type="text" name="reason" placeholder="e.g. Public holiday"
                           class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                </div>
                <button type="submit" class="btn-primary w-full text-sm">Block Date</button>
            </form>
        </div>

        @if($blockedDates->count())
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Blocked Dates</h2>
            <ul class="space-y-3">
                @foreach($blockedDates as $block)
                <li class="flex items-start justify-between gap-2 text-sm">
                    <div>
                        <div class="font-medium text-gray-900 dark:text-white">{{ $block->blocked_date->format('d M Y') }}</div>
                        @if($block->start_time)
                        <div class="text-xs text-gray-400">{{ $block->start_time }} – {{ $block->end_time }}</div>
                        @else
                        <div class="text-xs text-gray-400">All day</div>
                        @endif
                        @if($block->reason)
                        <div class="text-xs text-gray-400 italic">{{ $block->reason }}</div>
                        @endif
                    </div>
                    <form action="{{ route('admin.demo-availability.blocked-dates.destroy', $block) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-red-400 hover:text-red-600 text-xs">Remove</button>
                    </form>
                </li>
                @endforeach
            </ul>
        </div>
        @endif
    </div>
</div>
@endsection
