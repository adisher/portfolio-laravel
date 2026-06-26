@extends('layouts.admin')

@section('title', $workItem->name . ' - Manual')

@section('content')
<div class="mb-6 flex items-start justify-between gap-4">
    <div>
        <div class="flex items-center gap-3">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $workItem->name }}</h1>
            <span class="status-badge">{{ ucfirst($workItem->type) }}</span>
            @unless($workItem->active)<span class="text-xs text-gray-400">(inactive)</span>@endunless
        </div>
        @if($workItem->tagline)<p class="text-gray-600 dark:text-gray-400 mt-1">{{ $workItem->tagline }}</p>@endif
        <div class="flex items-center gap-4 mt-2 text-sm">
            @if($workItem->url)<a href="{{ $workItem->url }}" target="_blank" class="text-teal hover:underline">{{ $workItem->url }}</a>@endif
            @if($workItem->project)<a href="{{ route('admin.projects.edit', $workItem->project) }}" class="text-gray-500 hover:text-teal">Linked: {{ $workItem->project->title }}</a>@endif
        </div>
    </div>
    <div class="flex gap-2 flex-shrink-0">
        <a href="{{ route('admin.work-items.edit', $workItem) }}" class="btn-primary text-sm">Edit</a>
        <a href="{{ route('admin.work-items.index') }}" class="btn-secondary text-sm">Back</a>
    </div>
</div>

@php
    $listBlocks = [
        'pain_points'     => ['Pain Points', 'text-red-500'],
        'key_outcomes'    => ['Key Outcomes / Proof', 'text-green-500'],
        'differentiators' => ['Differentiators', 'text-teal-500'],
        'article_angles'  => ['Article Angles', 'text-purple-500'],
    ];
@endphp

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Positioning --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Positioning</h2>
        @if($workItem->target_audience)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Target Audience</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4">{{ $workItem->target_audience }}</p>
        @endif
        @if($workItem->how_it_helps)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">How It Helps</p>
        <p class="text-sm text-gray-700 dark:text-gray-300 mb-4 whitespace-pre-line">{{ $workItem->how_it_helps }}</p>
        @endif
        @if($workItem->tech_stack)
        <p class="text-xs uppercase tracking-wide text-gray-400 mb-1">Tech Stack</p>
        <p class="text-sm text-gray-700 dark:text-gray-300">{{ $workItem->tech_stack }}</p>
        @endif
    </div>

    {{-- Target keywords --}}
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Target Keywords</h2>
        @if(!empty($workItem->target_keywords))
        <div class="flex flex-wrap gap-2">
            @foreach($workItem->target_keywords as $kw)
            <span class="text-xs px-3 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300">{{ $kw }}</span>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400">None set.</p>
        @endif
    </div>

    {{-- List blocks --}}
    @foreach($listBlocks as $field => [$label, $color])
    <div class="admin-card p-6">
        <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">{{ $label }}</h2>
        @if(!empty($workItem->{$field}))
        <ul class="space-y-2">
            @foreach($workItem->{$field} as $entry)
            <li class="flex items-start gap-2 text-sm text-gray-700 dark:text-gray-300">
                <span class="{{ $color }} mt-0.5">•</span>
                <span>{{ $entry }}</span>
            </li>
            @endforeach
        </ul>
        @else
        <p class="text-sm text-gray-400">None added.</p>
        @endif
    </div>
    @endforeach
</div>

@if($workItem->notes)
<div class="admin-card p-6 mt-6">
    <h2 class="text-base font-semibold text-gray-900 dark:text-white mb-3">Notes</h2>
    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $workItem->notes }}</p>
</div>
@endif
@endsection
