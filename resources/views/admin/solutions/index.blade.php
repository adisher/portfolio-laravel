@extends('layouts.admin')

@section('title', 'Solutions - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Solutions</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage domain expertise items for the "What I Build" section</p>
    </div>
    <a href="{{ route('admin.solutions.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add Solution
    </a>
</div>

@if(session('success'))
<div class="mb-4 p-4 bg-green-100 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg text-green-700 dark:text-green-400">
    {{ session('success') }}
</div>
@endif

<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Icon</th>
                    <th>Title</th>
                    <th>Accent</th>
                    <th>Keywords</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($solutions as $solution)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <span class="text-sm font-mono text-gray-500">{{ $solution->sort_order }}</span>
                    </td>
                    <td>
                        @if($solution->icon)
                        <div class="w-8 h-8 text-teal-500">
                            {!! $solution->icon !!}
                        </div>
                        @else
                        <div class="w-8 h-8 rounded bg-gray-100 dark:bg-gray-700 flex items-center justify-center">
                            <span class="text-xs font-bold text-gray-400">{{ substr($solution->title, 0, 2) }}</span>
                        </div>
                        @endif
                    </td>
                    <td>
                        <div>
                            <div class="font-medium text-gray-900 dark:text-white">{{ $solution->title }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400 line-clamp-1">{{ $solution->description }}</div>
                        </div>
                    </td>
                    <td>
                        @php
                            $accentColors = [
                                'teal'   => 'bg-teal-400',
                                'sunset' => 'bg-orange-400',
                                'blend'  => 'bg-gradient-to-r from-teal-400 to-orange-400',
                            ];
                        @endphp
                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-full {{ $accentColors[$solution->accent_color] ?? 'bg-gray-400' }}"></span>
                            <span class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ $solution->accent_color }}</span>
                        </div>
                    </td>
                    <td>
                        @if($solution->keywords)
                        <div class="flex flex-wrap gap-1">
                            @foreach($solution->keywords as $keyword)
                            <span class="text-xs px-1.5 py-0.5 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded">{{ $keyword }}</span>
                            @endforeach
                        </div>
                        @else
                        <span class="text-sm text-gray-400">—</span>
                        @endif
                    </td>
                    <td>
                        @if($solution->is_published)
                        <span class="status-badge status-published">Published</span>
                        @else
                        <span class="status-badge status-draft">Draft</span>
                        @endif
                        @if($solution->is_featured)
                        <span class="ml-1 text-yellow-500" title="Featured">&#11088;</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('admin.solutions.edit', $solution) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.solutions.destroy', $solution) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this solution?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No solutions yet</p>
                        <p class="text-sm">Add your first domain expertise item.</p>
                        <a href="{{ route('admin.solutions.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add Solution
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($solutions->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $solutions->links() }}
    </div>
    @endif
</div>
@endsection
