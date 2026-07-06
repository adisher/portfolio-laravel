@extends('layouts.app')

@section('title', 'Proof of Work - Adil Sher')
@section('description', 'Original deep dives on the real systems I have built: the problems, the engineering decisions, and what I learned shipping them.')

@section('content')

{{-- Hero --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light overflow-hidden py-16 lg:py-24">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-10 left-10 w-64 h-64 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/4 w-72 h-72 bg-sunset/10 rounded-full blur-3xl"></div>
    </div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 text-center">
        <span class="badge badge-teal mb-4">Original Writing</span>
        <h1 class="text-4xl lg:text-5xl font-black mb-5 text-soft-light">
            Proof of <span class="text-gradient">Work</span>
        </h1>
        <p class="text-lg text-soft max-w-2xl mx-auto">
            Deep dives on the real systems I have built. Not summaries or hot takes: the actual problems,
            the engineering decisions behind them, and what I learned shipping them.
        </p>
    </div>
</section>

{{-- Articles --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        @if($posts->count())
        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($posts as $post)
            <article class="card card-hover overflow-hidden flex flex-col">
                @if($post->featured_image)
                <a href="{{ route('blog.show', $post->slug) }}" class="block overflow-hidden">
                    <img src="{{ Storage::url($post->featured_image) }}" alt="{{ $post->title }}"
                        class="w-full aspect-video object-cover img-zoom" loading="lazy">
                </a>
                @endif
                <div class="p-6 flex flex-col flex-1">
                    <div class="flex items-center flex-wrap gap-2 mb-3">
                        @if($post->category)
                        <span class="inline-block px-3 py-1 text-xs font-medium rounded-full"
                            style="background-color: {{ $post->category->color }}20; color: {{ $post->category->color }}">
                            {{ $post->category->name }}
                        </span>
                        @endif
                        <span class="text-sm text-soft-dark dark:text-soft">{{ $post->published_at->format('M j, Y') }}</span>
                        <span class="text-soft/30">•</span>
                        <span class="text-sm text-soft-dark dark:text-soft">{{ $post->reading_time }} min read</span>
                    </div>
                    <h2 class="text-xl font-bold text-midnight dark:text-soft-light mb-3 leading-snug">
                        <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-teal dark:hover:text-teal transition-colors">
                            {{ $post->title }}
                        </a>
                    </h2>
                    <p class="text-soft-dark dark:text-soft text-sm mb-4 line-clamp-3 flex-1">{{ $post->excerpt }}</p>
                    <a href="{{ route('blog.show', $post->slug) }}"
                        class="text-teal hover:text-teal-dark font-medium text-sm transition-colors mt-auto pt-2">
                        Read More →
                    </a>
                </div>
            </article>
            @endforeach
        </div>

        @if($posts->hasPages())
        <div class="mt-12">{{ $posts->links() }}</div>
        @endif
        @else
        <div class="text-center py-16">
            <h3 class="text-2xl font-bold text-midnight dark:text-soft-light mb-3">Original pieces are on the way</h3>
            <p class="text-soft-dark dark:text-soft max-w-md mx-auto">
                This is where my own deep dives will live. Check back soon, or
                <a href="{{ route('blog.index') }}" class="text-teal hover:underline">browse the blog</a> in the meantime.
            </p>
        </div>
        @endif
    </div>
</section>
@endsection
