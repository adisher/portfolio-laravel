@extends('layouts.admin')

@section('title', 'Create Project - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.projects.index') }}" class="hover:text-gray-700">Projects</a>
        <span>›</span>
        <span>Create New</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Create New Project</h1>
</div>

<form action="{{ route('admin.projects.store') }}" method="POST" enctype="multipart/form-data"
      x-data="{ isOwnProduct: {{ old('is_own_product') ? 'true' : 'false' }} }">
    @csrf

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Basic Information -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Basic Information</h2>

                <div class="space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Project Title *
                        </label>
                        <input type="text" id="title" name="title" required value="{{ old('title') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('title') border-red-500 @enderror">
                        @error('title')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="short_description"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Short Description *
                        </label>
                        <textarea id="short_description" name="short_description" rows="3" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('short_description') border-red-500 @enderror">{{ old('short_description') }}</textarea>
                        @error('short_description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="description"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Full Description *
                        </label>
                        <textarea id="description" name="description" rows="8" required
                            :rows="isOwnProduct ? 15 : 8"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
                        @error('description')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                        <div x-show="isOwnProduct" x-transition class="mt-2 p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg">
                            <p class="text-xs font-medium text-emerald-700 dark:text-emerald-300 mb-1">Markdown Formatting Supported</p>
                            <p class="text-xs text-emerald-600 dark:text-emerald-400">
                                Use <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">**bold**</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">*italic*</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">## Headings</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">- bullet lists</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">1. numbered lists</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">`code`</code>,
                                <code class="bg-emerald-100 dark:bg-emerald-800 px-1 rounded">[links](url)</code>,
                                tables, and more. This will be rendered as rich HTML on the product page.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Project Details -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Details</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="project_url"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Live URL
                        </label>
                        <input type="url" id="project_url" name="project_url" value="{{ old('project_url') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('project_url') border-red-500 @enderror">
                        @error('project_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="github_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            GitHub URL
                        </label>
                        <input type="url" id="github_url" name="github_url" value="{{ old('github_url') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('github_url') border-red-500 @enderror">
                        @error('github_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="!isOwnProduct" x-transition>
                        <label for="client_name"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Client Name
                        </label>
                        <input type="text" id="client_name" name="client_name" value="{{ old('client_name') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('client_name') border-red-500 @enderror">
                        @error('client_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="project_date"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Project Date *
                        </label>
                        <input type="date" id="project_date" name="project_date" required
                            value="{{ old('project_date') }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('project_date') border-red-500 @enderror">
                        @error('project_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Project Metrics -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Metrics</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Add measurable results to showcase project impact</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="primary_metric_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Metric Value
                        </label>
                        <input type="text" id="primary_metric_value" name="primary_metric_value"
                            value="{{ old('primary_metric_value') }}"
                            placeholder="e.g., +150%, 2M+, 99.9%"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">The main number/stat to highlight</p>
                    </div>

                    <div>
                        <label for="primary_metric_label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Metric Label
                        </label>
                        <input type="text" id="primary_metric_label" name="primary_metric_label"
                            value="{{ old('primary_metric_label') }}"
                            placeholder="e.g., Revenue Increase, Daily Users"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Description of what the metric measures</p>
                    </div>
                </div>

                <div>
                    <label for="metrics" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Additional Metrics (JSON)
                    </label>
                    <textarea id="metrics" name="metrics" rows="4"
                        placeholder='[{"value": "50K+", "label": "Daily Users"}, {"value": "99.9%", "label": "Uptime"}]'
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ old('metrics') }}</textarea>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Optional: Array of additional metrics in JSON format</p>
                </div>
            </div>

            <!-- Technologies -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Technologies Used</h2>

                <div>
                    <label for="technologies" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Technologies (one per line)
                    </label>
                    <textarea name="technologies" id="technologies" rows="3" placeholder="Enter technologies used (one per line)"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('technologies') border-red-500 @enderror">{{ old('technologies') }}</textarea>
                    @error('technologies')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter each technology on a new line</p>
                </div>
            </div>

            <!-- Case Study Narrative (hidden for own products) -->
            <div class="admin-card p-6" x-show="!isOwnProduct" x-transition>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Case Study</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Tell the story of this project. These sections appear on the project detail page as a structured case study.</p>

                <div class="space-y-4">
                    <div>
                        <label for="challenge" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            The Challenge
                        </label>
                        <textarea id="challenge" name="challenge" rows="5"
                            placeholder="Describe the problem, need, or opportunity that led to this project..."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('challenge') border-red-500 @enderror">{{ old('challenge') }}</textarea>
                        @error('challenge')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="solution" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            The Solution
                        </label>
                        <textarea id="solution" name="solution" rows="5"
                            placeholder="Describe your approach, architecture decisions, and how you solved the problem..."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('solution') border-red-500 @enderror">{{ old('solution') }}</textarea>
                        @error('solution')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="results" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            The Results
                        </label>
                        <textarea id="results" name="results" rows="5"
                            placeholder="Describe the outcomes, impact, and measurable results..."
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('results') border-red-500 @enderror">{{ old('results') }}</textarea>
                        @error('results')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Project Identity -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Identity</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Define the visual identity and your role in this project.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Your Role
                        </label>
                        <input type="text" id="role" name="role" value="{{ old('role') }}"
                            placeholder="e.g., Lead Developer, Full Stack Engineer"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('role') border-red-500 @enderror">
                        @error('role')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Duration
                        </label>
                        <input type="text" id="duration" name="duration" value="{{ old('duration') }}"
                            placeholder="e.g., 3 months, 6 weeks"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('duration') border-red-500 @enderror">
                        @error('duration')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="color_primary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Accent Color
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="color_primary" name="color_primary"
                                value="{{ old('color_primary', '#41EAD4') }}"
                                class="h-10 w-16 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Used as the project's theme color</span>
                        </div>
                    </div>

                    <div>
                        <label for="color_secondary" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Secondary Accent Color
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" id="color_secondary" name="color_secondary"
                                value="{{ old('color_secondary', '#FF6B35') }}"
                                class="h-10 w-16 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Optional gradient accent</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Screenshot Gallery Note -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Screenshot Gallery</h2>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <p class="text-sm text-blue-700 dark:text-blue-300">
                            Save the project first, then add gallery screenshots from the edit page. Screenshots will appear in the project's showcase carousel.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Featured Image -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Featured Image</h2>

                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Featured Image *
                    </label>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*" required
                        class="w-full @error('featured_image') border-red-500 @enderror">
                    @error('featured_image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Max file size: 2MB</p>
                </div>
            </div>

            <!-- Project Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>

                <div class="space-y-4">
                    <div>
                        <label for="category_id"
                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category *
                        </label>
                        <select id="category_id" name="category_id" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('category_id') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id')==$category->id ? 'selected' : ''
                                }}>
                                {{ $category->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('category_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Status *
                        </label>
                        <select id="status" name="status" required
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('status') border-red-500 @enderror">
                            <option value="completed" {{ old('status')==='completed' ? 'selected' : '' }}>Completed
                            </option>
                            <option value="in_progress" {{ old('status')==='in_progress' ? 'selected' : '' }}>In
                                Progress</option>
                            <option value="on_hold" {{ old('status')==='on_hold' ? 'selected' : '' }}>On Hold</option>
                        </select>
                        @error('status')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @if($tags->count())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tags</label>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            @foreach($tags as $tag)
                            <label class="flex items-center">
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" {{ in_array($tag->id,
                                old('tags', [])) ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring
                                focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" {{ old('is_featured') ? 'checked' : ''
                                }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Project</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_published" value="1" {{ old('is_published', true)
                                ? 'checked' : '' }}
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Publish Project</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_own_product" value="1"
                                x-model="isOwnProduct"
                                {{ old('is_own_product') ? 'checked' : '' }}
                                class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Own Product (not client work)</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">Shows in "My Products" section with its own detail page</p>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Create Project
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="flex-1 btn-secondary text-center">
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

</script>
@endpush