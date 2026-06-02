@extends('layouts.admin')

@section('title', 'Edit Project - Admin Panel')

@section('content')
<div class="mb-6">
    <div class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 mb-2">
        <a href="{{ route('admin.projects.index') }}" class="hover:text-gray-700">Projects</a>
        <span>›</span>
        <span>{{ $project->title }}</span>
        <span>›</span>
        <span>Edit</span>
    </div>
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Edit Project</h1>
</div>

<form action="{{ route('admin.projects.update', $project) }}" method="POST" enctype="multipart/form-data"
      x-data="{ isOwnProduct: {{ old('is_own_product', $project->is_own_product) ? 'true' : 'false' }} }">
    @csrf
    @method('PUT')

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
                        <input type="text" id="title" name="title" required value="{{ old('title', $project->title) }}"
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
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('short_description') border-red-500 @enderror">{{ old('short_description', $project->short_description) }}</textarea>
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
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('description') border-red-500 @enderror">{{ old('description', $project->description) }}</textarea>
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
                        <input type="url" id="project_url" name="project_url"
                            value="{{ old('project_url', $project->project_url) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('project_url') border-red-500 @enderror">
                        @error('project_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="github_url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            GitHub URL
                        </label>
                        <input type="url" id="github_url" name="github_url"
                            value="{{ old('github_url', $project->github_url) }}"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('github_url') border-red-500 @enderror">
                        @error('github_url')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div x-show="!isOwnProduct" x-transition>
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Client Name
                        </label>
                        <input type="text" id="client_name" name="client_name" value="{{ old('client_name', $project->client_name) }}"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('client_name') border-red-500 @enderror">
                        @error('client_name')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="project_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Project Date *
                        </label>
                        <input type="date" id="project_date" name="project_date" required value="{{ old('project_date', $project->project_date->format('Y-m-d')) }}" 
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('project_date') border-red-500 @enderror">
                        @error('project_date')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
            
            <!-- Technologies -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Technologies Used</h2>

                <div>
                    <label for="technologies" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Technologies (one per line)
                    </label>
                    @php
                    $technologiesValue = '';
                    if (old('technologies')) {
                        $technologiesValue = is_array(old('technologies')) ? implode("\n", old('technologies')) : old('technologies');
                    } elseif ($project->technologies) {
                        $technologiesValue = is_array($project->technologies) ? implode("\n", $project->technologies) : '';
                    }
                    @endphp

                    <textarea id="technologies" name="technologies" rows="4" placeholder="Laravel&#10;Vue.js&#10;Tailwind CSS&#10;MySQL"
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('technologies') border-red-500 @enderror">{{ $technologiesValue }}</textarea>
                    @error('technologies')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter each technology on a new line</p>
                </div>
            </div>

            <!-- Project Metrics -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Project Metrics</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Showcase measurable results to highlight project impact.</p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="primary_metric_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Metric Value
                        </label>
                        <input type="text" id="primary_metric_value" name="primary_metric_value"
                               value="{{ old('primary_metric_value', $project->primary_metric_value) }}"
                               placeholder="e.g., +150%, 2M+, 99.9%"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('primary_metric_value')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="primary_metric_label" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Primary Metric Label
                        </label>
                        <input type="text" id="primary_metric_label" name="primary_metric_label"
                               value="{{ old('primary_metric_label', $project->primary_metric_label) }}"
                               placeholder="e.g., Revenue Increase, API Calls/Day"
                               class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('primary_metric_label')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="metrics" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Additional Metrics (JSON format)
                    </label>
                    @php
                    $metricsValue = old('metrics');
                    if (!$metricsValue && $project->metrics) {
                        $metricsValue = json_encode($project->metrics, JSON_PRETTY_PRINT);
                    }
                    @endphp
                    <textarea id="metrics" name="metrics" rows="4"
                              placeholder='[{"value": "50K", "label": "Daily Users"}, {"value": "99.9%", "label": "Uptime"}]'
                              class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ $metricsValue }}</textarea>
                    @error('metrics')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Enter as JSON array: [{"value": "X", "label": "Label"}]</p>
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
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('challenge') border-red-500 @enderror">{{ old('challenge', $project->challenge) }}</textarea>
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
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('solution') border-red-500 @enderror">{{ old('solution', $project->solution) }}</textarea>
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
                            class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('results') border-red-500 @enderror">{{ old('results', $project->results) }}</textarea>
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
                        <input type="text" id="role" name="role" value="{{ old('role', $project->role) }}"
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
                        <input type="text" id="duration" name="duration" value="{{ old('duration', $project->duration) }}"
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
                                value="{{ old('color_primary', $project->color_primary ?? '#41EAD4') }}"
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
                                value="{{ old('color_secondary', $project->color_secondary ?? '#FF6B35') }}"
                                class="h-10 w-16 rounded cursor-pointer border border-gray-300 dark:border-gray-600">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Optional gradient accent</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Screenshot Gallery -->
            <div class="admin-card p-6" x-data="galleryManager">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Screenshot Gallery</h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">Upload full-page screenshots. These appear in the project's showcase carousel.</p>

                <!-- Existing Images -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-4" id="gallery-grid">
                    @foreach($project->images as $image)
                    <div class="relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700" id="gallery-image-{{ $image->id }}">
                        <img src="{{ Storage::url($image->image_path) }}" alt="{{ $image->alt_text }}"
                             class="w-full h-32 object-cover">
                        <button type="button"
                                onclick="deleteGalleryImage({{ $project->id }}, {{ $image->id }}, this)"
                                class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">
                            &times;
                        </button>
                        <div class="p-2">
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $image->alt_text ?: 'No alt text' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>

                <!-- Upload Zone -->
                <div class="border-2 border-dashed border-gray-300 dark:border-gray-600 rounded-lg p-6 text-center hover:border-blue-400 dark:hover:border-blue-500 transition-colors">
                    <svg class="mx-auto h-10 w-10 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Click to upload screenshots</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Max 5MB per image. JPEG, PNG, WebP supported.</p>
                    <input type="file" multiple accept="image/*" class="hidden" id="gallery-upload"
                           onchange="uploadGalleryImages({{ $project->id }}, this)">
                    <button type="button" onclick="document.getElementById('gallery-upload').click()"
                            class="mt-3 px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md text-sm hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                        Add Screenshots
                    </button>
                </div>

                <!-- Upload Progress -->
                <div id="gallery-upload-progress" class="hidden mt-4">
                    <div class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-sm text-gray-600 dark:text-gray-400">Uploading...</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Current Featured Image -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Featured Image</h2>
                
                @if($project->featured_image)
                <div class="mb-4">
                    <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}" class="w-full rounded-lg">
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Current image</p>
                </div>
                @endif
                
                <div>
                    <label for="featured_image" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        {{ $project->featured_image ? 'Replace Image' : 'Featured Image *' }}
                    </label>
                    <input type="file" id="featured_image" name="featured_image" accept="image/*" {{ !$project->featured_image ? 'required' : '' }}
                           class="w-full @error('featured_image') border-red-500 @enderror">
                    @error('featured_image')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Max file size: 2MB. Leave empty to keep current image.</p>
                </div>
            </div>
            
            <!-- Project Settings -->
            <div class="admin-card p-6">
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Settings</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Category *
                        </label>
                        <select id="category_id" name="category_id" required 
                                class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white @error('category_id') border-red-500 @enderror">
                            <option value="">Select Category</option>
                            @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id', $project->category_id) == $category->id ? 'selected' : '' }}>
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
                            <option value="completed" {{ old('status', $project->status) === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="in_progress" {{ old('status', $project->status) === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                            <option value="on_hold" {{ old('status', $project->status) === 'on_hold' ? 'selected' : '' }}>On Hold</option>
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
                                <input type="checkbox" name="tags[]" value="{{ $tag->id }}" 
                                       {{ in_array($tag->id, old('tags', $project->tags->pluck('id')->toArray())) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">{{ $tag->name }}</span>
                            </label>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_featured" value="1" 
                                   {{ old('is_featured', $project->is_featured) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Featured Project</span>
                        </label>
                        
                        <label class="flex items-center">
                            <input type="checkbox" name="is_published" value="1"
                                   {{ old('is_published', $project->is_published) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Publish Project</span>
                        </label>

                        <label class="flex items-center">
                            <input type="checkbox" name="is_own_product" value="1"
                                   x-model="isOwnProduct"
                                   {{ old('is_own_product', $project->is_own_product) ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-emerald-600 shadow-sm focus:border-emerald-300 focus:ring focus:ring-emerald-200 focus:ring-opacity-50">
                            <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Own Product (not client work)</span>
                        </label>
                        <p class="text-xs text-gray-500 ml-6">Shows in "My Products" section with its own detail page</p>
                    </div>
                </div>
            </div>
            
            <!-- Product Management Links (visible when is_own_product) -->
            <div class="admin-card p-6" x-show="isOwnProduct" x-transition>
                <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Product Management</h2>
                <div class="space-y-3">
                    <a href="{{ route('admin.projects.product-content', $project) }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                        <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6Z" /></svg>
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">Manage Product Content</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Features, pricing, FAQ, CTA</p>
                        </div>
                    </a>
                    <a href="{{ route('admin.projects.product-pages.index', $project) }}"
                       class="flex items-center gap-3 px-4 py-3 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors group">
                        <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                        <div>
                            <span class="text-sm font-medium text-gray-800 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400">Product Pages ({{ $project->productPages()->count() }})</span>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Setup, deploy, docs pages</p>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Actions -->
            <div class="admin-card p-6">
                <div class="flex space-x-3">
                    <button type="submit" class="flex-1 btn-primary">
                        Update Project
                    </button>
                    <a href="{{ route('admin.projects.index') }}" class="flex-1 btn-secondary text-center">
                        Cancel
                    </a>
                </div>
                
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                    <a href="{{ $project->is_own_product ? route('products.show', $project->slug) : route('portfolio.show', $project->slug) }}"
                       target="_blank" class="w-full btn-secondary text-center">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        {{ $project->is_own_product ? 'View Product' : 'View Project' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Convert technologies textarea to array on form submission
document.querySelector('form').addEventListener('submit', function(e) {
    const technologiesField = document.getElementById('technologies');
    const value = technologiesField.value.trim();

    if (value) {
        const technologies = value.split('\n').filter(tech => tech.trim());
        technologiesField.name = '';

        technologies.forEach((tech, index) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `technologies[${index}]`;
            input.value = tech.trim();
            this.appendChild(input);
        });
    }
});

// Gallery upload
async function uploadGalleryImages(projectId, input) {
    const files = input.files;
    if (!files.length) return;

    const progress = document.getElementById('gallery-upload-progress');
    progress.classList.remove('hidden');

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
    }

    try {
        const response = await fetch(`/admin/projects/${projectId}/images`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: formData,
        });

        const data = await response.json();

        if (data.success) {
            const grid = document.getElementById('gallery-grid');
            data.images.forEach(img => {
                const div = document.createElement('div');
                div.className = 'relative group rounded-lg overflow-hidden border border-gray-200 dark:border-gray-700';
                div.id = `gallery-image-${img.id}`;
                div.innerHTML = `
                    <img src="${img.url}" alt="${img.alt_text}" class="w-full h-32 object-cover">
                    <button type="button"
                            onclick="deleteGalleryImage(${projectId}, ${img.id}, this)"
                            class="absolute top-2 right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600">
                        &times;
                    </button>
                    <div class="p-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">${img.alt_text || 'No alt text'}</p>
                    </div>
                `;
                grid.appendChild(div);
            });
        } else {
            alert('Upload failed. Please try again.');
        }
    } catch (error) {
        console.error('Upload error:', error);
        alert('Upload failed. Please try again.');
    } finally {
        progress.classList.add('hidden');
        input.value = '';
    }
}

// Gallery delete
async function deleteGalleryImage(projectId, imageId, btn) {
    if (!confirm('Delete this screenshot?')) return;

    try {
        const response = await fetch(`/admin/projects/${projectId}/images/${imageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
        });

        const data = await response.json();

        if (data.success) {
            const el = document.getElementById(`gallery-image-${imageId}`);
            if (el) el.remove();
        } else {
            alert('Delete failed. Please try again.');
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Delete failed. Please try again.');
    }
}
</script>
@endpush