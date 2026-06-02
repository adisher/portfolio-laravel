@extends('layouts.admin')

@section('title', 'Edit RSS Source - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.rss-sources.index') }}" class="hover:text-gray-700">RSS Sources</a>
        <span>›</span>
        <span>{{ $rssSource->name }}</span>
        <span>›</span>
        <span>Edit</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit RSS Source</h1>
</div>

<form action="{{ route('admin.rss-sources.update', $rssSource) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Source Information</h2>

                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Source Name *
                        </label>
                        <input type="text" id="name" name="name" required value="{{ old('name', $rssSource->name) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('name') border-red-500 @enderror">
                        @error('name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            RSS Feed URL *
                        </label>
                        <input type="url" id="url" name="url" required value="{{ old('url', $rssSource->url) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('url') border-red-500 @enderror">
                        @error('url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category *
                        </label>
                        <select id="category" name="category" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('category') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            <option value="development" {{ old('category', $rssSource->category) === 'development' ? 'selected' : '' }}>Web Development</option>
                            <option value="design" {{ old('category', $rssSource->category) === 'design' ? 'selected' : '' }}>Design</option>
                            <option value="tech" {{ old('category', $rssSource->category) === 'tech' ? 'selected' : '' }}>Technology</option>
                            <option value="tutorials" {{ old('category', $rssSource->category) === 'tutorials' ? 'selected' : '' }}>Tutorials</option>
                            <option value="news" {{ old('category', $rssSource->category) === 'news' ? 'selected' : '' }}>Tech News</option>
                            <option value="tools" {{ old('category', $rssSource->category) === 'tools' ? 'selected' : '' }}>Tools & Resources</option>
                        </select>
                        @error('category')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Priority *
                        </label>
                        <select id="priority" name="priority" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('priority') border-red-500 @enderror">
                            @for($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ old('priority', $rssSource->priority) == $i ? 'selected' : '' }}>
                                {{ $i }} {{ $i <= 3 ? '(Low)' : ($i >= 8 ? '(High)' : '(Medium)') }}
                            </option>
                            @endfor
                        </select>
                        @error('priority')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="fetch_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Fetch Frequency (minutes) *
                        </label>
                        <select id="fetch_frequency" name="fetch_frequency" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('fetch_frequency') border-red-500 @enderror">
                            <option value="15" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 15 ? 'selected' : '' }}>15 minutes</option>
                            <option value="30" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 30 ? 'selected' : '' }}>30 minutes</option>
                            <option value="60" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 60 ? 'selected' : '' }}>1 hour</option>
                            <option value="120" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 120 ? 'selected' : '' }}>2 hours</option>
                            <option value="240" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 240 ? 'selected' : '' }}>4 hours</option>
                            <option value="480" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 480 ? 'selected' : '' }}>8 hours</option>
                            <option value="720" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 720 ? 'selected' : '' }}>12 hours</option>
                            <option value="1440" {{ old('fetch_frequency', $rssSource->fetch_frequency) == 1440 ? 'selected' : '' }}>24 hours</option>
                        </select>
                        @error('fetch_frequency')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="flex items-center">
                            <input type="checkbox" name="active" value="1" {{ old('active', $rssSource->active) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Active</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="admin-card p-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Statistics</h3>
                
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $rssSource->collected_articles_count ?? 0 }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Articles</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-white">
                            @if($rssSource->last_fetched_at)
                                {{ $rssSource->last_fetched_at->diffForHumans() }}
                            @else
                                Never
                            @endif
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Last Fetch</div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Update Source
                    </button>
                    <a href="{{ route('admin.rss-sources.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection