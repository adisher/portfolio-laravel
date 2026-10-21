@extends('layouts.app')

@section('title', 'Cinema - Latest ' . $types[$type])
@section('description', 'Browse the latest ' . strtolower($types[$type]) . ' added to Local Cinema, with posters, synopses and watch links.')
{{-- Kept out of search results on purpose: third-party listings, not portfolio content --}}
@section('robots', 'noindex, nofollow')

@section('content')

{{-- Hero --}}
<section class="bg-gradient-to-br from-midnight via-ocean to-midnight-dark py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full blur-3xl bg-teal/5"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 rounded-full blur-3xl bg-sunset/5"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10 text-center">
        <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal/10 border border-teal/20 mb-4">
            <svg class="w-4 h-4 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <rect x="3" y="5" width="18" height="14" rx="2" stroke-width="2"/>
                <path stroke-width="2" d="M10 9l5 3-5 3V9z"/>
            </svg>
            <span class="text-teal text-sm font-medium">Powered by Local Cinema</span>
        </div>
        <h1 class="text-3xl lg:text-5xl font-black text-soft-light mb-3">Cinema</h1>
        <p class="text-soft/60 text-lg">Fresh movies and TV shows, updated through the day</p>

        {{-- Movies / TV tabs --}}
        <div class="mt-8 inline-flex p-1 rounded-full bg-midnight-dark/60 border border-soft/10">
            @foreach($types as $key => $label)
            <a href="{{ route('cinema.index', $key === 'movies' ? [] : ['type' => $key]) }}"
               class="px-5 py-2 rounded-full text-sm font-medium transition-colors {{ $type === $key ? 'bg-teal text-midnight' : 'text-soft hover:text-soft-light' }}"
               @if($type === $key) aria-current="page" @endif>
                {{ $label }}
            </a>
            @endforeach
        </div>
    </div>
</section>

<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        @if(count($items))
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-6">
            @foreach($items as $item)
            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer nofollow"
               class="card card-hover overflow-hidden flex flex-col group">
                <div class="relative aspect-[2/3] bg-gradient-to-br from-ocean to-midnight-dark">
                    {{-- Placeholder sits underneath; the poster covers it when it loads --}}
                    <div class="absolute inset-0 flex items-center justify-center p-4 text-center">
                        <span class="text-soft-light/80 font-bold text-lg leading-snug">{{ $item['title'] }}</span>
                    </div>
                    @if($item['image'])
                    <img src="{{ $item['image'] }}" alt="{{ $item['title'] }} poster"
                         loading="lazy" decoding="async" referrerpolicy="no-referrer"
                         onerror="this.remove()"
                         class="absolute inset-0 w-full h-full object-cover transition-transform duration-300 group-hover:scale-105">
                    @endif
                    @if($item['year'])
                    <span class="absolute top-2 right-2 text-xs font-semibold px-2 py-1 rounded-full bg-midnight/80 text-soft-light">
                        {{ $item['year'] }}
                    </span>
                    @endif
                </div>

                <div class="p-4 flex flex-col flex-1">
                    <h2 class="font-semibold text-midnight dark:text-soft-light leading-snug line-clamp-2">{{ $item['title'] }}</h2>
                    @if($item['description'])
                    <p class="mt-2 text-sm text-soft-dark dark:text-soft line-clamp-3">{{ $item['description'] }}</p>
                    @endif
                    <span class="mt-auto pt-3 inline-flex items-center gap-1 text-sm font-medium text-teal">
                        Watch now
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </span>
                </div>
            </a>
            @endforeach
        </div>

        <p class="mt-10 text-center text-sm text-soft-dark dark:text-soft">
            Listings and links provided by <a href="https://localcinema.xyz/" target="_blank" rel="noopener noreferrer" class="text-teal hover:underline">Local Cinema</a>.
        </p>
        @else
        <div class="card p-10 text-center max-w-xl mx-auto">
            <h2 class="text-xl font-semibold text-midnight dark:text-soft-light mb-2">Nothing to show right now</h2>
            <p class="text-soft-dark dark:text-soft">The cinema feed is temporarily unavailable. Please check back shortly.</p>
        </div>
        @endif
    </div>
</section>

@endsection
