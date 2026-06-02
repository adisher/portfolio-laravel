@extends('layouts.admin')
@section('title', 'Product Pages - ' . $project->title)

@section('content')
<div class="p-6">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.projects.edit', $project) }}" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800 dark:text-white">Product Pages</h1>
            </div>
            <p class="text-sm text-gray-500 dark:text-gray-400 ml-8">{{ $project->title }}</p>
        </div>
        <a href="{{ route('admin.projects.product-pages.create', $project) }}" class="btn-primary">
            + Add Page
        </a>
    </div>

    @if(session('success'))
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400 text-sm">
        {{ session('success') }}
    </div>
    @endif

    @if($pages->count())
    <div class="admin-card overflow-hidden">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Slug</th>
                    <th>Status</th>
                    <th>Order</th>
                    <th class="text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pages as $page)
                <tr>
                    <td class="font-medium text-gray-800 dark:text-white">{{ $page->title }}</td>
                    <td>
                        @php
                            $typeColors = [
                                'setup'  => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
                                'deploy' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
                                'custom' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                            ];
                        @endphp
                        <span class="status-badge {{ $typeColors[$page->type] ?? $typeColors['custom'] }}">
                            {{ ucfirst($page->type) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">{{ $page->slug }}</td>
                    <td>
                        @if($page->is_published)
                        <span class="status-badge bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">Published</span>
                        @else
                        <span class="status-badge bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">Draft</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500">{{ $page->sort_order }}</td>
                    <td class="text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('products.page', [$project->slug, $page->slug]) }}" target="_blank"
                               class="text-gray-400 hover:text-blue-500 transition-colors" title="View">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                            </a>
                            <a href="{{ route('admin.projects.product-pages.edit', [$project, $page]) }}"
                               class="text-gray-400 hover:text-blue-500 transition-colors" title="Edit">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </a>
                            <form action="{{ route('admin.projects.product-pages.destroy', [$project, $page]) }}" method="POST"
                                  onsubmit="return confirm('Delete this page?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-500 transition-colors" title="Delete">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @else
    <div class="admin-card p-12 text-center">
        <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
        <p class="text-gray-500 dark:text-gray-400 mb-4">No product pages yet.</p>
        <a href="{{ route('admin.projects.product-pages.create', $project) }}" class="btn-primary text-sm">
            Create First Page
        </a>
    </div>
    @endif
</div>
@endsection
