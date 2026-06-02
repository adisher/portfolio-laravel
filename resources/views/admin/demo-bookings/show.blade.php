@extends('layouts.admin')

@section('title', 'Demo Booking — ' . $demoBooking->name)

@section('content')
<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.demo-bookings.index') }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
    </a>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Demo Booking</h1>
    @php
        $colors = [
            'confirmed'  => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
            'cancelled'  => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
            'completed'  => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
            'no_show'    => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
        ];
    @endphp
    <span class="status-badge {{ $colors[$demoBooking->status] ?? '' }}">
        {{ ucfirst(str_replace('_', ' ', $demoBooking->status)) }}
    </span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main info --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Booking Details</h2>
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Name</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white font-semibold">{{ $demoBooking->name }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Email</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->email }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Company</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->company ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Plan Interest</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->plan_interest ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Product</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->project?->title ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Duration</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->duration_minutes }} min</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Scheduled</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white font-semibold text-base">{{ $demoBooking->scheduledAtFormatted() }}</dd>
                </div>
                @if($demoBooking->message)
                <div class="col-span-2">
                    <dt class="text-gray-500 dark:text-gray-400 font-medium">Message</dt>
                    <dd class="mt-1 text-gray-900 dark:text-white">{{ $demoBooking->message }}</dd>
                </div>
                @endif
            </dl>
        </div>

        {{-- Email activity --}}
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Email Activity</h2>
            <ul class="space-y-3 text-sm">
                @foreach([
                    ['Confirmation sent', 'on booking creation', true],
                    ['24h reminder', $demoBooking->reminder_24h_sent_at?->format('d M Y g:i A'), (bool)$demoBooking->reminder_24h_sent_at],
                    ['1h reminder', $demoBooking->reminder_1h_sent_at?->format('d M Y g:i A'), (bool)$demoBooking->reminder_1h_sent_at],
                    ['Follow-up', $demoBooking->follow_up_sent_at?->format('d M Y g:i A'), (bool)$demoBooking->follow_up_sent_at],
                ] as [$label, $detail, $sent])
                <li class="flex items-center gap-3">
                    @if($sent)
                    <span class="w-2 h-2 rounded-full bg-green-500 shrink-0"></span>
                    @else
                    <span class="w-2 h-2 rounded-full bg-gray-300 dark:bg-gray-600 shrink-0"></span>
                    @endif
                    <span class="{{ $sent ? 'text-gray-900 dark:text-white' : 'text-gray-400 dark:text-gray-500' }}">
                        {{ $label }}
                    </span>
                    @if($sent && $detail !== true)
                    <span class="text-gray-400 text-xs">{{ $detail }}</span>
                    @endif
                </li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Status update --}}
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Update Status</h2>
            <form action="{{ route('admin.demo-bookings.update-status', $demoBooking) }}" method="POST" class="space-y-3">
                @csrf @method('PATCH')
                <select name="status" class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm">
                    @foreach(['confirmed', 'completed', 'cancelled', 'no_show'] as $s)
                    <option value="{{ $s }}" {{ $demoBooking->status === $s ? 'selected' : '' }}>
                        {{ ucfirst(str_replace('_', ' ', $s)) }}
                    </option>
                    @endforeach
                </select>
                <button type="submit" class="btn-primary w-full text-sm">Update Status</button>
            </form>
        </div>

        {{-- Admin notes --}}
        <div class="admin-card p-6">
            <h2 class="text-sm font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">Admin Notes</h2>
            <form action="{{ route('admin.demo-bookings.update-notes', $demoBooking) }}" method="POST" class="space-y-3">
                @csrf @method('PATCH')
                <textarea name="admin_notes" rows="5"
                    class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm resize-none focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Internal notes…">{{ old('admin_notes', $demoBooking->admin_notes) }}</textarea>
                <button type="submit" class="btn-primary w-full text-sm">Save Notes</button>
            </form>
        </div>

        {{-- Danger --}}
        <div class="admin-card p-6 border border-red-200 dark:border-red-900">
            <h2 class="text-sm font-semibold text-red-500 uppercase tracking-wide mb-4">Danger Zone</h2>
            <form action="{{ route('admin.demo-bookings.destroy', $demoBooking) }}" method="POST"
                  onsubmit="return confirm('Delete this booking permanently?')">
                @csrf @method('DELETE')
                <button type="submit" class="w-full py-2 px-4 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition-colors">
                    Delete Booking
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
