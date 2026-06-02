@extends('layouts.app')

@section('title', 'Cancel Demo Booking')

@section('content')
<section class="min-h-screen flex items-center justify-center bg-midnight px-6 py-20">
    <div class="max-w-md w-full text-center">
        @if($status === 'success')
            <div class="w-16 h-16 rounded-full bg-teal/10 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-3">Booking Cancelled</h1>
            <p class="text-soft/60 mb-2">Your demo for <strong class="text-soft">{{ $booking->project?->title ?? 'our product' }}</strong> has been cancelled.</p>
            <p class="text-soft/50 text-sm mb-8">Scheduled for {{ $booking->scheduledAtFormatted() }}</p>
            <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-semibold text-midnight bg-teal hover:shadow-lg transition-all">
                Back to Home
            </a>

        @elseif($status === 'already_cancelled')
            <div class="w-16 h-16 rounded-full bg-soft/10 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-soft/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-3">Already Cancelled</h1>
            <p class="text-soft/60 mb-8">This booking was already cancelled.</p>
            <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-semibold text-white border border-soft/20 hover:border-teal transition-all">
                Back to Home
            </a>

        @elseif($status === 'past')
            <div class="w-16 h-16 rounded-full bg-sunset/10 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-sunset" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-3">Demo Already Passed</h1>
            <p class="text-soft/60 mb-8">This demo has already occurred and cannot be cancelled.</p>
            <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-semibold text-white border border-soft/20 hover:border-teal transition-all">
                Back to Home
            </a>

        @else
            <div class="w-16 h-16 rounded-full bg-sunset/10 flex items-center justify-center mx-auto mb-6">
                <svg class="w-8 h-8 text-sunset" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-white mb-3">Booking Not Found</h1>
            <p class="text-soft/60 mb-8">This cancellation link is invalid or has expired.</p>
            <a href="{{ url('/') }}" class="inline-flex items-center px-6 py-3 rounded-xl font-semibold text-white border border-soft/20 hover:border-teal transition-all">
                Back to Home
            </a>
        @endif
    </div>
</section>
@endsection
