@extends('layouts.app')

@section('title', 'Coming Soon')

@section('content')
<section class="min-h-screen flex items-center justify-center bg-midnight px-6 py-20">
    <div class="max-w-lg w-full text-center">
        {{-- Icon --}}
        <div class="w-20 h-20 rounded-2xl bg-teal/10 flex items-center justify-center mx-auto mb-8">
            <svg class="w-10 h-10 text-teal" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
            </svg>
        </div>

        <h1 class="text-4xl font-black text-white mb-4">Coming Soon</h1>
        <p class="text-soft/60 text-lg mb-10 leading-relaxed">
            This section is under construction. We're working on something great — check back soon.
        </p>

        <a href="{{ url('/') }}"
           class="inline-flex items-center px-8 py-3.5 rounded-xl font-semibold text-midnight bg-teal hover:shadow-lg hover:shadow-teal/20 transition-all duration-300 hover:-translate-y-0.5">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Back to Home
        </a>
    </div>
</section>
@endsection
