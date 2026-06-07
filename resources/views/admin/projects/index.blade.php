@extends('layouts.admin')

@section('title', 'Projects - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Projects</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage your portfolio projects</p>
    </div>
    <a href="{{ route('admin.projects.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Project
    </a>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select name="status" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Statuses</option>
                <option value="completed" {{ request('status')==='completed' ? 'selected' : '' }}>Completed</option>
                <option value="in_progress" {{ request('status')==='in_progress' ? 'selected' : '' }}>In Progress
                </option>
                <option value="on_hold" {{ request('status')==='on_hold' ? 'selected' : '' }}>On Hold</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
            <select name="category"
                class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Categories</option>
                @foreach(\App\Models\Category::active()->forProjects()->get() as $category)
                <option value="{{ $category->id }}" {{ request('category')==$category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search projects..."
                class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['status', 'category', 'search']))
        <a href="{{ route('admin.projects.index') }}" class="text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<!-- Projects Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Published</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($projects as $project)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div class="flex items-center space-x-3">
                            <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}"
                                class="w-12 h-12 rounded-lg object-cover">
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">
                                    {{ $project->title }}
                                    @if($project->is_own_product)
                                    <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300">Product</span>
                                    @endif
                                </div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{
                                    Str::limit($project->short_description, 50) }}</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                            style="background-color: {{ $project->category->color }}20; color: {{ $project->category->color }}">
                            {{ $project->category->name }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $project->status }}">
                            {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $project->project_date->format('M j, Y') }}
                    </td>
                    <td>
                        @if($project->is_published)
                        <span class="status-badge status-published">Published</span>
                        @else
                        <span class="status-badge status-draft">Draft</span>
                        @endif
                        @if($project->is_featured)
                        <span class="ml-1 text-yellow-500" title="Featured">⭐</span>
                        @endif
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            <a href="{{ route('portfolio.show', $project->slug) }}" target="_blank"
                                class="text-gray-400 hover:text-blue-600" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </a>
                            <a href="{{ route('admin.projects.edit', $project) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.projects.destroy', $project) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this project?')">
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
                    <td colspan="6" class="text-center py-8 text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No projects found</p>
                        <p class="text-sm">Get started by creating your first project.</p>
                        <a href="{{ route('admin.projects.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add New Project
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($projects->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $projects->links() }}
    </div>
    @endif
</div>
@endsection