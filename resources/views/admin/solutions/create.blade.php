@extends('layouts.admin')

@section('title', 'Add Solution - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.solutions.index') }}" class="hover:text-gray-700">Solutions</a>
        <span>></span>
        <span>Add New</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Add Solution</h1>
</div>

<form action="{{ route('admin.solutions.store') }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Solution Details</h2>

                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Title *
                        </label>
                        <input type="text" id="title" name="title" required value="{{ old('title') }}"
                            placeholder="e.g., E-commerce Platforms"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('title') border-red-500 @enderror">
                        @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Description *
                        </label>
                        <textarea id="description" name="description" rows="3" required
                            placeholder="One-line capability description"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Icon (SVG Markup)
                        </label>
                        <textarea id="icon" name="icon" rows="4"
                            placeholder='<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">...</svg>'
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ old('icon') }}</textarea>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Paste a monoline SVG icon. Use 24x24 viewBox with stroke-width="1.5" and stroke="currentColor".</p>
                    </div>

                    <div>
                        <label for="keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Keywords
                        </label>
                        <input type="text" id="keywords" name="keywords" value="{{ old('keywords') }}"
                            placeholder="e.g., Stripe, Inventory, Checkout"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Comma-separated tags displayed below the description.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="accent_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Accent Color *
                        </label>
                        <select id="accent_color" name="accent_color" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="teal" {{ old('accent_color', 'teal') === 'teal' ? 'selected' : '' }}>Teal</option>
                            <option value="sunset" {{ old('accent_color') === 'sunset' ? 'selected' : '' }}>Sunset</option>
                            <option value="blend" {{ old('accent_color') === 'blend' ? 'selected' : '' }}>Blend (Teal → Sunset)</option>
                        </select>
                    </div>

                    <div>
                        <label for="sort_order" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Sort Order
                        </label>
                        <input type="number" id="sort_order" name="sort_order" value="{{ old('sort_order', 0) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 mt-1">Lower numbers appear first</p>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured on Home Page</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', true) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Published</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Create Solution
                    </button>
                    <a href="{{ route('admin.solutions.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
