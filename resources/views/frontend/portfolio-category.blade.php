@extends('layouts.app')

@section('title', $category->name . ' Projects - Portfolio')
@section('description', 'Browse ' . $category->name . ' projects: ' . $category->description)

@section('content')
<!-- Page Header -->
<section class="bg-gradient-to-r from-blue-600 to-purple-600 text-white section-padding">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <div class="mb-4">
            <span class="inline-block px-4 py-2 bg-white bg-opacity-20 rounded-full text-sm font-medium">
                {{ $projects->total() }} {{ Str::plural('Project', $projects->total()) }}
            </span>
        </div>
        <h1 class="text-4xl lg:text-5xl font-bold mb-4">
            {{ $category->name }} Projects
        </h1>
        @if($category->description)
        <p class="text-xl text-blue-100 max-w-2xl mx-auto">
            {{ $category->description }}
        </p>
        @endif
    </div>
</section>

<!-- Breadcrumb -->
<section class="bg-gray-50 dark:bg-gray-800 py-4">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <nav class="flex" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                <li class="inline-flex items-center">
                    <a href="{{ route('home') }}" class="text-gray-500 hover:text-blue-600">Home</a>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <a href="{{ route('portfolio.index') }}" class="text-gray-500 hover:text-blue-600">Portfolio</a>
                    </div>
                </li>
                <li>
                    <div class="flex items-center">
                        <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                        <span class="text-gray-500">{{ $category->name }}</span>
                    </div>
                </li>
            </ol>
        </nav>
    </div>
</section>

<!-- Category Navigation -->
<section class="py-8 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-2">
            <a href="{{ route('portfolio.index') }}" class="filter-btn">
                All Projects
            </a>
            @foreach(\App\Models\Category::active()->forProjects()->withCount('projects')->having('projects_count', '>', 0)->get() as $cat)
            <a href="{{ route('portfolio.category', $cat->slug) }}" 
               class="filter-btn {{ $cat->id === $category->id ? 'active' : '' }}">
                {{ $cat->name }} ({{ $cat->projects_count }})
            </a>
            @endforeach
        </div>
    </div>
</section>

<!-- Projects Grid -->
<section class="section-padding bg-gray-50 dark:bg-gray-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($projects->count())
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach($projects as $project)
            <div class="card overflow-hidden hover:shadow-lg transition-all duration-300">
                <div class="relative group">
                    <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}" class="w-full h-64 object-cover transition-transform group-hover:scale-105">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 transition-opacity flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <div class="flex space-x-2">
                            <a href="{{ route('portfolio.show', $project->slug) }}" class="bg-white text-gray-900 px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition-colors">
                                View Details
                            </a>
                            @if($project->project_url)
                            <a href="{{ $project->project_url }}" target="_blank" class="bg-blue-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-700 transition-colors">
                                Live Demo
                            </a>
                            @endif
                        </div>
                    </div>
                    @if($project->is_featured)
                    <div class="absolute top-4 left-4">
                        <span class="bg-yellow-400 text-yellow-900 px-2 py-1 text-xs font-medium rounded-full">
                            Featured
                        </span>
                    </div>
                    @endif
                </div>
                
                <div class="p-6">
                    <div class="flex items-center mb-3">
                        <span class="inline-block px-3 py-1 text-sm font-medium rounded-full" style="background-color: {{ $project->category->color }}20; color: {{ $project->category->color }}">
                            {{ $project->category->name }}
                        </span>
                    </div>
                    
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        <a href="{{ route('portfolio.show', $project->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                            {{ $project->title }}
                        </a>
                    </h3>
                    
                    <p class="text-gray-600 dark:text-gray-300 mb-4 line-clamp-3">
                        {{ $project->short_description }}
                    </p>
                    
                    @if($project->technologies)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach(array_slice($project->technologies, 0, 4) as $tech)
                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                            {{ $tech }}
                        </span>
                        @endforeach
                        @if(count($project->technologies) > 4)
                        <span class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded">
                            +{{ count($project->technologies) - 4 }} more
                        </span>
                        @endif
                    </div>
                    @endif
                    
                    <div class="flex items-center justify-between">
                        <a href="{{ route('portfolio.show', $project->slug) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium">
                            View Details →
                        </a>
                        <div class="flex space-x-2">
                            @if($project->github_url)
                            <a href="{{ $project->github_url }}" target="_blank" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z" clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        
        <!-- Pagination -->
        @if($projects->hasPages())
        <div class="mt-12">
            {{ $projects->links() }}
        </div>
        @endif
        @else
        <!-- Empty State -->
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
            </svg>
            <h3 class="mt-2 text-lg font-medium text-gray-900 dark:text-white">No projects in this category</h3>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Check back soon for new {{ $category->name }} projects, or browse other categories.
            </p>
            <div class="mt-6">
                <a href="{{ route('portfolio.index') }}" class="btn-primary">
                    View All Projects
                </a>
            </div>
        </div>
        @endif
    </div>
</section>

<!-- Related Categories -->
@if($relatedCategories->count())
<section class="section-padding bg-white dark:bg-gray-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8 text-center">
            Explore Other Categories
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($relatedCategories as $relatedCategory)
            <a href="{{ route('portfolio.category', $relatedCategory->slug) }}" class="card p-6 hover:shadow-lg transition-shadow text-center group">
                <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center" style="background-color: {{ $relatedCategory->color }}20;">
                    <div class="w-8 h-8 rounded-full" style="background-color: {{ $relatedCategory->color }};"></div>
                </div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 group-hover:text-blue-600 dark:group-hover:text-blue-400">
                    {{ $relatedCategory->name }}
                </h3>
                <p class="text-gray-600 dark:text-gray-300 text-sm mb-3">
                    {{ $relatedCategory->description }}
                </p>
                <span class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $relatedCategory->projects_count }} {{ Str::plural('project', $relatedCategory->projects_count) }}
                </span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif
@endsection