@extends('layouts.admin')

@section('title', 'Reels - Social Studio')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
            <a href="{{ route('admin.social.index') }}" class="hover:text-gray-700">Social Accounts</a>
            <span>›</span>
            <span>Reels</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reels</h1>
        <p class="text-gray-600 dark:text-gray-400">Short videos for Instagram Reels and YouTube Shorts. Nothing posts until you approve it.</p>
    </div>
    <a href="{{ route('admin.social.reels.settings') }}" class="btn-secondary text-sm py-2 px-4">Studio settings</a>
</div>

{{-- Start a reel from a well-performing article --}}
<div class="admin-card p-6 mb-6">
    <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-1">Start a reel</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Top articles by human views that have no reel yet. Writing a script takes about 20 seconds.</p>

    @if($candidates->isEmpty())
        <p class="text-sm text-gray-500 dark:text-gray-400">Every published article already has a reel.</p>
    @else
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($candidates as $post)
                <div class="flex items-center justify-between gap-4 py-3">
                    <div class="min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white truncate">{{ $post->title }}</div>
                        <div class="text-xs text-gray-400">
                            {{ number_format($post->views) }} human views
                            · {{ $post->source_type === 'curated' ? 'News' : 'Own article' }}
                            · {{ optional($post->published_at)->format('M j, Y') }}
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.social.reels.store') }}"
                        x-data="{ busy: false }" @submit="busy = true">
                        @csrf
                        <input type="hidden" name="blog_post_id" value="{{ $post->id }}">
                        <button type="submit" class="btn-primary text-sm py-2 px-4 whitespace-nowrap" :disabled="busy"
                            x-text="busy ? 'Writing script...' : 'Create reel'">Create reel</button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif
</div>

{{-- Filters --}}
<div class="flex flex-wrap items-center gap-2 mb-4">
    @php
        $filters = [null => 'All', 'review' => "Needs review ({$needsReview})", 'ready' => 'Ready for review', 'draft' => 'Not rendered', 'rendering' => 'Rendering', 'render_failed' => 'Render failed', 'rejected' => 'Rejected'];
    @endphp
    @foreach($filters as $key => $label)
        <a href="{{ route('admin.social.reels.index', array_filter(['status' => $key])) }}"
            class="text-sm px-3 py-1 rounded-full border {{ ($status ?? null) === ($key ?: null) ? 'bg-blue-600 text-white border-blue-600' : 'border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300' }}">
            {{ $label }}
        </a>
    @endforeach
</div>

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reel</th>
                    <th>Status</th>
                    <th>Platforms</th>
                    <th>Created</th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($drafts as $draft)
                    @php $hook = collect($draft->scenes())->firstWhere('type', 'hook'); @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                        <td class="max-w-sm">
                            <div class="font-medium text-gray-900 dark:text-white truncate">{{ $hook['on_screen_text'] ?? $draft->blogPost->title }}</div>
                            <div class="text-xs text-gray-400 truncate">{{ $draft->blogPost->title }}</div>
                        </td>
                        <td>
                            <span class="status-badge {{ $draft->status === 'ready' ? 'status-published' : ($draft->status === 'render_failed' ? 'status-unread' : 'status-draft') }}">{{ $draft->statusLabel() }}</span>
                        </td>
                        <td class="text-sm text-gray-500 dark:text-gray-400">
                            @foreach($draft->targets as $target)
                                <div>{{ $target->label() }}: {{ ucfirst($target->status) }}</div>
                            @endforeach
                        </td>
                        <td class="text-sm text-gray-500 dark:text-gray-400">{{ $draft->created_at->format('M j, Y') }}</td>
                        <td class="text-right">
                            <a href="{{ route('admin.social.reels.show', $draft) }}" class="btn-secondary text-xs py-1 px-3">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-8 text-gray-500 dark:text-gray-400">No reels yet. Start one above.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($drafts->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">{{ $drafts->links() }}</div>
    @endif
</div>
@endsection
