@extends('layouts.admin')

@section('title', 'Create Blog Post from Article - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.collected-articles.index') }}" class="hover:text-gray-700">Collected Articles</a>
        <span>›</span>
        <a href="{{ route('admin.collected-articles.show', $collectedArticle) }}" class="hover:text-gray-700">
            {{ Str::limit($collectedArticle->title, 30) }}
        </a>
        <span>›</span>
        <span>Create Blog Post</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create Blog Post from Curated Article</h1>
</div>

<!-- Original Article Preview -->
<div class="admin-card p-4 mb-6 bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800">
    <div class="flex items-start space-x-4">
        <div class="flex-shrink-0">
            <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100">
                {{ $collectedArticle->rssSource->name }}
            </span>
        </div>
        <div class="flex-1">
            <h3 class="font-medium text-blue-900 dark:text-blue-100 mb-2">Original Article:</h3>
            <p class="text-blue-800 dark:text-blue-200 font-medium">{{ $collectedArticle->title }}</p>
            @if($collectedArticle->description)
            <p class="text-blue-700 dark:text-blue-300 text-sm mt-1">{{ Str::limit($collectedArticle->description, 200) }}</p>
            @endif
            <div class="flex items-center space-x-4 mt-2 text-xs text-blue-600 dark:text-blue-400">
                @if($collectedArticle->author)
                <span>by {{ $collectedArticle->author }}</span>
                @endif
                <span>{{ $collectedArticle->published_at->format('M j, Y') }}</span>
                <a href="{{ $collectedArticle->url }}" target="_blank" class="hover:underline">View Original</a>
            </div>
        </div>
    </div>
</div>

<form action="{{ route('admin.collected-articles.store-blog-post', $collectedArticle) }}" method="POST">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Post Content</h2>

                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Post Title *
                        </label>
                        <input type="text" id="title" name="title" required 
                            value="{{ old('title', $collectedArticle->title) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('title') border-red-500 @enderror">
                        @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Edit the title to make it unique to your blog</p>
                    </div>

                    <div>
                        <label for="excerpt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Excerpt *
                        </label>
                        <textarea id="excerpt" name="excerpt" rows="3" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('excerpt') border-red-500 @enderror"
                            placeholder="Write your own summary and perspective on this article...">{{ old('excerpt', $collectedArticle->description) }}</textarea>
                        @error('excerpt')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="content" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Content *
                        </label>
                        <textarea id="content" name="content" rows="20" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('content') border-red-500 @enderror"
                            placeholder="Add your commentary, insights, and value to the original article...">{{ old('content', $initialContent) }}</textarea>
                        @error('content')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Add your unique perspective, commentary, and insights. Remember to provide attribution to the original source.
                        </p>
                    </div>

                    <div>
                        <label for="curator_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Curator Notes
                        </label>
                        <textarea id="curator_notes" name="curator_notes" rows="3"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('curator_notes') border-red-500 @enderror"
                            placeholder="Internal notes about why you chose this article, your curation process, etc...">{{ old('curator_notes') }}</textarea>
                        @error('curator_notes')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">These notes are for internal use only</p>
                    </div>
                </div>
            </div>

            <!-- SEO Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">SEO Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="meta_title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Title
                        </label>
                        <input type="text" id="meta_title" name="meta_title" value="{{ old('meta_title') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('meta_title') border-red-500 @enderror"
                            placeholder="Leave empty to use post title">
                        @error('meta_title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meta_description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Description
                        </label>
                        <textarea id="meta_description" name="meta_description" rows="3"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('meta_description') border-red-500 @enderror"
                            placeholder="Leave empty to use excerpt">{{ old('meta_description') }}</textarea>
                        @error('meta_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="meta_keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Keywords
                        </label>
                        <input type="text" id="meta_keywords" name="meta_keywords" value="{{ old('meta_keywords') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('meta_keywords') border-red-500 @enderror"
                            placeholder="keyword1, keyword2, keyword3">
                        @error('meta_keywords')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Separate keywords with commas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Publishing -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Publishing</h2>

                <div class="space-y-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status *
                        </label>
                        <select id="status" name="status" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror">
                            <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                            <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                            <option value="archived" {{ old('status') === 'archived' ? 'selected' : '' }}>Archived</option>
                        </select>
                        @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="published_at" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Publish Date
                        </label>
                        <input type="datetime-local" id="published_at" name="published_at"
                            value="{{ old('published_at') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('published_at') border-red-500 @enderror">
                        @error('published_at')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Leave empty to publish immediately when status is published</p>
                    </div>
                </div>
            </div>

            <!-- Source Attribution -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Source Attribution</h2>
                
                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 space-y-2">
                    <div class="text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Original Source:</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $collectedArticle->rssSource->name }}</span>
                    </div>
                    @if($collectedArticle->author)
                    <div class="text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Author:</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $collectedArticle->author }}</span>
                    </div>
                    @endif
                    <div class="text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">Originally Published:</span>
                        <span class="text-gray-600 dark:text-gray-400">{{ $collectedArticle->published_at->format('M j, Y') }}</span>
                    </div>
                    <div class="text-sm">
                        <span class="font-medium text-gray-700 dark:text-gray-300">URL:</span>
                        <a href="{{ $collectedArticle->url }}" target="_blank" class="text-blue-600 hover:text-blue-700 break-all text-xs">
                            {{ Str::limit($collectedArticle->url, 50) }}
                        </a>
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">This information will be automatically included in the blog post metadata</p>
            </div>

            <!-- Categories & Tags -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Categories & Tags</h2>

                <div class="space-y-4">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category *
                        </label>
                        <select id="category_id" name="category_id" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('category_id') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($tags->count())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', [])) ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Create Blog Post
                    </button>
                    <a href="{{ route('admin.collected-articles.show', $collectedArticle) }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@push('scripts')
<script>
// Auto-set publish date when status changes to published
document.getElementById('status').addEventListener('change', function() {
    const publishedAtField = document.getElementById('published_at');
    
    if (this.value === 'published' && !publishedAtField.value) {
        const now = new Date();
        const localISOTime = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
        publishedAtField.value = localISOTime;
    }
});

// Convert comma-separated keywords to array on form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const keywordsField = document.getElementById('meta_keywords');
    const value = keywordsField.value.trim();
    
    if (value) {
        // Create hidden inputs for each keyword
        const keywords = value.split(',').map(keyword => keyword.trim()).filter(keyword => keyword);
        keywordsField.name = ''; // Remove original name
        
        keywords.forEach((keyword, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `meta_keywords[${index}]`;
            input.value = keyword;
            this.appendChild(input);
        });
    }
});
</script>
@endpush