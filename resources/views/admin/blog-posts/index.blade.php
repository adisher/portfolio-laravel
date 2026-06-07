@extends('layouts.admin')

@section('title', 'Blog Posts - Admin Panel')

@section('content')
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Blog Posts</h1>
        <p class="text-gray-600 dark:text-gray-400">Manage your blog articles and content</p>
    </div>
    <a href="{{ route('admin.blog-posts.create') }}" class="btn-primary">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
        </svg>
        Add New Post
    </a>
</div>

<!-- Filters -->
<div class="admin-card p-4 mb-6">
    <form method="GET" class="flex flex-wrap gap-4 items-end">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
            <select name="status" class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Posts</option>
                <option value="published" {{ request('status')==='published' ? 'selected' : '' }}>Published</option>
                <option value="draft" {{ request('status')==='draft' ? 'selected' : '' }}>Draft</option>
                <option value="archived" {{ request('status')==='archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
            <select name="category"
                class="form-select rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                <option value="">All Categories</option>
                @foreach(\App\Models\Category::active()->forBlog()->get() as $category)
                <option value="{{ $category->id }}" {{ request('category')==$category->id ? 'selected' : '' }}>
                    {{ $category->name }}
                </option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search posts..."
                class="form-input rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700">
        </div>
        <button type="submit" class="btn-secondary">Filter</button>
        @if(request()->hasAny(['status', 'category', 'search']))
        <a href="{{ route('admin.blog-posts.index') }}" class="text-gray-500 hover:text-gray-700">Clear</a>
        @endif
    </form>
</div>

<!-- Blog Posts Table -->
<div class="admin-card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th>Published Date</th>
                    <th>Views</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($posts as $post)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td>
                        <div class="flex items-center space-x-3">
                            @if($post->featured_image)
                            <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                                class="w-12 h-12 rounded-lg object-cover">
                            @else
                            <div
                                class="w-12 h-12 rounded-lg bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <svg class="w-6 h-6 text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            @endif
                            <div>
                                <div class="font-medium text-gray-900 dark:text-white">{{ $post->title }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($post->excerpt, 50)
                                    }}</div>
                                <div class="text-xs text-gray-400 dark:text-gray-500">{{ $post->reading_time }} min read
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full"
                            style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                            {{ $post->category->name }}
                        </span>
                    </td>
                    <td>
                        <span class="status-badge status-{{ $post->status }}">
                            {{ ucfirst($post->status) }}
                        </span>
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        @if($post->published_at)
                        {{ $post->published_at->format('M j, Y') }}
                        <div class="text-xs">{{ $post->published_at->format('g:i A') }}</div>
                        @else
                        <span class="text-gray-400">Not published</span>
                        @endif
                    </td>
                    <td class="text-sm text-gray-500 dark:text-gray-400">
                        {{ number_format($post->views) }}
                    </td>
                    <td>
                        <div class="flex items-center space-x-2">
                            @if($post->status === 'published')
                            <a href="{{ route('blog.show', $post->slug) }}" target="_blank"
                                class="text-gray-400 hover:text-blue-600" title="View">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                            </a>
                            @endif
                            <a href="{{ route('admin.blog-posts.edit', $post) }}"
                                class="text-gray-400 hover:text-green-600" title="Edit">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('admin.blog-posts.destroy', $post) }}" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this blog post?')">
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
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                            </path>
                        </svg>
                        <p class="text-lg font-medium">No blog posts found</p>
                        <p class="text-sm">Get started by creating your first blog post.</p>
                        <a href="{{ route('admin.blog-posts.create') }}"
                            class="mt-4 inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                            Add New Post
                        </a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($posts->hasPages())
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
        {{ $posts->links() }}
    </div>
    @endif
</div>
@endsection