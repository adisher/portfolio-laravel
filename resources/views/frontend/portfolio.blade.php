@extends('layouts.app')

@section('title', 'Portfolio - My Projects')
@section('description', 'Browse my portfolio of web development projects including full-stack applications, APIs, and more')

@section('content')
{{-- Page Header --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light section-padding overflow-hidden">
    {{-- Floating shapes --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 left-10 w-72 h-72 bg-sunset/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-64 h-64 bg-teal/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center relative z-10">
        <span class="badge badge-sunset mb-4 animate-up">Projects</span>
        <h1 class="animate-up text-4xl lg:text-5xl font-black mb-4">
            My <span class="text-gradient">Portfolio</span>
        </h1>
        <p class="animate-up text-lg text-soft max-w-2xl mx-auto">
            A collection of projects I've worked on, showcasing different technologies and solutions
        </p>
    </div>
</section>

{{-- Filters --}}
<section class="py-8 bg-white dark:bg-midnight border-b border-soft/20 dark:border-ocean">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex flex-wrap justify-center gap-2">
            <button class="filter-btn active" data-category="all">
                All Projects
            </button>
            @foreach($categories as $category)
            <button class="filter-btn" data-category="{{ $category->slug }}">
                {{ $category->name }} ({{ $category->projects_count }})
            </button>
            @endforeach
        </div>
    </div>
</section>

{{-- Projects Grid --}}
<section class="section-padding bg-soft-light dark:bg-midnight-light">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 animate-stagger" id="projects-grid">
            @foreach($projects as $project)
            @php
                $detailUrl = $project->is_own_product
                    ? route('products.show', $project->slug)
                    : route('portfolio.show', $project->slug);
            @endphp
            <div class="project-card card card-hover overflow-hidden"
                data-category="{{ $project->category->slug }}">
                <div class="relative group overflow-hidden aspect-video">
                    <img src="{{ Storage::url($project->featured_image) }}" alt="{{ $project->title }}"
                        class="w-full h-full object-cover img-zoom">
                    <div
                        class="absolute inset-0 bg-midnight/0 group-hover:bg-midnight/40 transition-all duration-300 flex items-center justify-center opacity-0 group-hover:opacity-100">
                        <div class="flex space-x-2">
                            <a href="{{ $detailUrl }}"
                                class="bg-white text-midnight px-4 py-2 rounded-lg font-medium hover:bg-soft-light transition-colors">
                                {{ $project->is_own_product ? 'View Product' : 'View Details' }}
                            </a>
                            @if(!$project->is_own_product && $project->project_url)
                            <a href="{{ $project->project_url }}" target="_blank"
                                class="bg-teal text-midnight px-4 py-2 rounded-lg font-medium hover:bg-teal-dark transition-colors">
                                Live Demo
                            </a>
                            @endif
                        </div>
                    </div>
                    <div class="absolute top-4 left-4 flex gap-2">
                        @if($project->is_own_product)
                        <span class="badge badge-teal">Product</span>
                        @endif
                        @if($project->is_featured)
                        <span class="badge badge-sunset">Featured</span>
                        @endif
                    </div>
                </div>

                <div class="p-6">
                    <div class="flex items-center justify-between mb-3">
                        <span class="inline-block px-3 py-1 text-sm font-medium rounded-full"
                            style="background-color: {{ $project->category->color }}20; color: {{ $project->category->color }}">
                            {{ $project->category->name }}
                        </span>
                    </div>

                    <h3 class="text-xl font-bold text-midnight dark:text-soft-light mb-2">
                        <a href="{{ $detailUrl }}"
                            class="hover:text-teal dark:hover:text-teal transition-colors">
                            {{ $project->title }}
                        </a>
                    </h3>

                    <p class="text-soft-dark dark:text-soft mb-4 line-clamp-3">
                        {{ $project->short_description }}
                    </p>

                    @if(!$project->is_own_product && $project->technologies)
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach(array_slice($project->technologies, 0, 4) as $tech)
                        <span
                            class="text-xs px-2 py-1 bg-ocean/10 dark:bg-ocean/30 text-midnight dark:text-soft-light rounded">
                            {{ $tech }}
                        </span>
                        @endforeach
                        @if(count($project->technologies) > 4)
                        <span
                            class="text-xs px-2 py-1 bg-ocean/10 dark:bg-ocean/30 text-midnight dark:text-soft-light rounded">
                            +{{ count($project->technologies) - 4 }} more
                        </span>
                        @endif
                    </div>
                    @elseif($project->is_own_product && $project->tags->count())
                    <div class="flex flex-wrap gap-2 mb-4">
                        @foreach($project->tags as $tag)
                        <span class="text-xs px-2 py-1 bg-teal/10 dark:bg-teal/20 text-teal rounded">
                            {{ $tag->name }}
                        </span>
                        @endforeach
                    </div>
                    @endif

                    <div class="flex items-center justify-between">
                        <a href="{{ $detailUrl }}"
                            class="text-teal hover:text-teal-dark font-medium transition-colors">
                            {{ $project->is_own_product ? 'View Product →' : 'View Details →' }}
                        </a>
                        <div class="flex space-x-2">
                            @if(!$project->is_own_product && $project->github_url)
                            <a href="{{ $project->github_url }}" target="_blank"
                                class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                        clip-rule="evenodd"></path>
                                </svg>
                            </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        <div class="mt-12">
            {{ $projects->links() }}
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterBtns = document.querySelectorAll('.filter-btn');
    const projectCards = document.querySelectorAll('.project-card');
    const FADE_MS = 180;

    // Ensure cards have a CSS transition set
    projectCards.forEach(card => {
        card.style.transition = 'opacity ' + FADE_MS + 'ms ease';
    });

    function applyFilter(category) {
        // Step 1: fade everything out
        projectCards.forEach(card => { card.style.opacity = '0'; });

        setTimeout(() => {
            // Step 2: toggle visibility after fade completes — no visible reflow
            projectCards.forEach(card => {
                const matches = category === 'all' || card.dataset.category === category;
                card.style.display = matches ? '' : 'none';
            });

            // Step 3: fade visible cards back in on next frame
            requestAnimationFrame(() => {
                projectCards.forEach(card => {
                    if (card.style.display !== 'none') {
                        card.style.opacity = '1';
                    }
                });
            });
        }, FADE_MS);
    }

    filterBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            applyFilter(this.dataset.category);
        });
    });
});
</script>
@endpush