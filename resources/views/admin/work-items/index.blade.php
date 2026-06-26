@extends('layouts.admin')

@section('title', 'Work Items - Admin Panel')

@section('content')
<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Work Items</h1>
        <p class="text-gray-600 dark:text-gray-400 text-sm mt-1">Marketing manuals: your products, services, and projects as the source of truth for content.</p>
    </div>
    <a href="{{ route('admin.work-items.create') }}" class="btn-primary text-sm">+ New Work Item</a>
</div>

@if(session('success'))
<div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
    {{ session('success') }}
</div>
@endif

<div class="admin-card overflow-hidden">
    <table class="admin-table w-full text-sm">
        <thead>
            <tr>
                <th class="text-left">Name</th>
                <th class="text-left">Type</th>
                <th class="text-left">Linked Project</th>
                <th class="text-center">Pain Points</th>
                <th class="text-center">Angles</th>
                <th class="text-center">Active</th>
                <th class="text-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($workItems as $item)
            <tr>
                <td class="text-left font-medium text-gray-900 dark:text-white">
                    <a href="{{ route('admin.work-items.show', $item) }}" class="hover:text-teal">{{ $item->name }}</a>
                    @if($item->tagline)<div class="text-xs text-gray-400">{{ \Illuminate\Support\Str::limit($item->tagline, 60) }}</div>@endif
                </td>
                <td class="text-left"><span class="status-badge">{{ ucfirst($item->type) }}</span></td>
                <td class="text-left text-gray-600 dark:text-gray-400">{{ $item->project?->title ?? '—' }}</td>
                <td class="text-center">{{ count($item->pain_points ?? []) }}</td>
                <td class="text-center">{{ count($item->article_angles ?? []) }}</td>
                <td class="text-center">{{ $item->active ? '✓' : '—' }}</td>
                <td class="text-right">
                    <a href="{{ route('admin.work-items.show', $item) }}" class="text-gray-500 hover:text-teal text-xs">View</a>
                    <a href="{{ route('admin.work-items.edit', $item) }}" class="text-blue-500 hover:text-blue-700 text-xs ml-2">Edit</a>
                </td>
            </tr>
            @empty
            <tr><td colspan="7" class="text-center text-gray-500 py-8">No work items yet. Create your first one to start building manuals.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
