@extends('layouts.app')

@section('title', $page->title . ' - ' . $product->title)

@php
    $accent = $product->color_primary ?? '#41EAD4';
@endphp

@push('styles')
<style>
    .prose { max-width: none; }
    .prose img { border-radius: 0.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .prose pre { background: #1e293b; border-radius: 0.5rem; padding: 1rem; overflow-x: auto; }
    .prose code { background: rgba(110, 118, 129, 0.1); padding: 0.2em 0.4em; border-radius: 0.25rem; font-size: 0.875em; }
    .prose pre code { background: transparent; padding: 0; border-radius: 0; font-size: 0.875em; }
    .prose h2 { font-size: 1.875rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; }
    .prose h3 { font-size: 1.5rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; }
    .prose a { color: #3b82f6; text-decoration: underline; }
    .prose a:hover { color: #2563eb; }
    .prose ul, .prose ol { padding-left: 1.5rem; }
    .prose li { margin: 0.5rem 0; }
</style>
@endpush

@section('content')

{{-- Hero --}}
<section class="relative overflow-hidden" style="background: linear-gradient(135deg, #0D1B2A 0%, {{ $accent }}08 50%, #1B3A4B 100%);">
    <div class="relative z-10 max-w-4xl mx-auto px-6 lg:px-8 py-20 pt-32">
        <nav class="flex mb-8 animate-up" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-soft/60 hover:text-teal transition-colors">Home</a></li>
                <li class="text-soft/40">/</li>
                <li><a href="{{ route('products.show', $product->slug) }}" class="text-soft/60 hover:text-teal transition-colors">{{ $product->title }}</a></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft/80">{{ $page->title }}</li>
            </ol>
        </nav>

        <h1 class="text-3xl sm:text-4xl font-black text-white animate-up">{{ $page->title }}</h1>
    </div>
</section>

{{-- Content --}}
<section class="py-16 lg:py-24 bg-soft-light dark:bg-midnight">
    <div class="max-w-4xl mx-auto px-6 lg:px-8">
        <div class="prose prose-lg dark:prose-invert max-w-none animate-up">
            {!! $page->rendered_content !!}
        </div>
    </div>
</section>

{{-- Back to product --}}
<section class="py-12 bg-white dark:bg-ocean/10">
    <div class="max-w-4xl mx-auto px-6 lg:px-8 text-center">
        <a href="{{ route('products.show', $product->slug) }}"
           class="inline-flex items-center text-sm font-medium transition-colors hover:underline"
           style="color: {{ $accent }};">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            Back to {{ $product->title }}
        </a>
    </div>
</section>

@endsection
