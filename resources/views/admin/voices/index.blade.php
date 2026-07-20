@extends('layouts.admin')

@section('title', 'Voices')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Voices</h1>
    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
        Find, verify and approve real user sentiment. Approved voices become selectable when you generate an article for that work item.
    </p>
</div>

@unless($braveConfigured)
<div class="mb-6 p-4 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg text-xs text-gray-500 dark:text-gray-400">
    Searches use <strong>Claude (Haiku)</strong>, roughly $0.08 per run. Brave Search is available as an optional second engine but is <strong>paid</strong> (~$5 per 1,000 queries; its free tier ended in Feb 2026) &mdash; set <code>BRAVE_SEARCH_API_KEY</code> in <code>.env</code> if you ever want it.
</div>
@endunless

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    @forelse($workItems as $wi)
    <a href="{{ route('admin.voices.show', $wi) }}"
       class="admin-card p-5 block border border-transparent hover:border-teal transition">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-gray-900 dark:text-white truncate">{{ $wi->name }}</h2>
                @if($wi->tagline)
                <p class="text-xs text-gray-400 mt-0.5">{{ \Illuminate\Support\Str::limit($wi->tagline, 80) }}</p>
                @endif
            </div>
            <span class="text-xs text-teal whitespace-nowrap">Manage &rarr;</span>
        </div>
        <div class="flex flex-wrap gap-4 mt-3 text-xs">
            <span><strong class="text-gray-900 dark:text-white">{{ $wi->approved_count }}</strong> <span class="text-gray-400">approved</span></span>
            <span><strong class="{{ $wi->candidate_count > 0 ? 'text-amber-500' : 'text-gray-900 dark:text-white' }}">{{ $wi->candidate_count }}</strong> <span class="text-gray-400">to review</span></span>
            <span><strong class="text-gray-900 dark:text-white">{{ $wi->screenshot_count }}</strong> <span class="text-gray-400">screenshots</span></span>
        </div>
    </a>
    @empty
    <p class="text-sm text-gray-400">No work items yet. Create one first, then come back to gather voices for it.</p>
    @endforelse
</div>
@endsection
