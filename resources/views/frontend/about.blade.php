@extends('layouts.app')

@section('title', 'About - My Story & Experience')
@section('description', 'Learn more about my background, experience, and passion for web development')

@push('schema')
<x-schema.person name="Adil Sher" jobTitle="Full Stack Developer" />
<x-schema.breadcrumb :items="[
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'About'],
]" />
@endpush

@section('content')
{{-- Hero Section --}}
<section class="relative bg-midnight dark:bg-midnight-dark text-soft-light overflow-hidden min-h-[560px] lg:min-h-[620px]">
    {{-- Background glows --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-20 left-10 w-72 h-72 bg-teal/10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-0 right-1/3 w-80 h-80 bg-sunset/10 rounded-full blur-3xl"></div>
    </div>

    {{-- Text content: constrained to left half on desktop via w-1/2 --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10 pt-16 pb-10 lg:pt-24 lg:pb-24">
        <div class="lg:w-1/2">
            <span class="badge badge-teal mb-4 animate-up">About Me</span>
            <h1 class="animate-up text-4xl lg:text-5xl font-black mb-6">
                About <span class="text-gradient">Me</span>
            </h1>
            <p class="animate-up text-lg text-soft mb-8">
                {{ $about['hero_bio'] }}
            </p>
            <div class="animate-up flex flex-wrap gap-4">
                @if($about['resume_url'])
                <a href="{{ $about['resume_url'] }}" target="_blank" class="btn-primary">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-4-4m4 4l4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Download Resume
                </a>
                @endif
                <a href="{{ route('contact') }}"
                    class="btn-secondary border-soft/30 text-soft-light hover:border-teal hover:text-teal">
                    Get In Touch
                </a>
            </div>
        </div>
    </div>

    {{-- Desktop image: absolutely pinned bottom-right, never disrupts flow --}}
    <div class="hidden lg:block absolute bottom-0 right-0 w-5/12 h-full pointer-events-none">
        {{-- Left-edge fade to blend with text area --}}
        <img src="{{ asset('storage/media/home-header-2.webp') }}"
             alt="Adil Sher"
             class="w-full h-full object-cover object-top"
             loading="eager">
    </div>

    {{-- Mobile image: flows below text, capped height --}}
    <div class="lg:hidden relative w-full h-72 overflow-hidden">
        <div class="absolute inset-x-0 bottom-0 h-20 z-10 bg-gradient-to-t from-midnight to-transparent"></div>
        <img src="{{ asset('storage/media/home-header-2.webp') }}"
             alt="Adil Sher"
             class="w-full h-full object-cover object-top"
             loading="eager">
    </div>
</section>

{{-- Skills Section --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-header animate-up">
            <span class="badge badge-sunset mb-4">Expertise</span>
            <h2 class="section-title">
                Technical <span class="text-gradient">Skills</span>
            </h2>
            <p class="section-subtitle">
                Technologies and tools I use to build amazing applications
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 animate-stagger">
            @foreach($about['skills'] as $skill)
            @php $color = $skill['color'] === 'sunset' ? 'sunset' : 'teal'; @endphp
            <div class="card card-hover p-8 text-center">
                <div class="w-16 h-16 bg-{{ $color }}/10 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-{{ $color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-midnight dark:text-soft-light mb-2">{{ $skill['title'] }}</h3>
                <p class="text-soft-dark dark:text-soft mb-4">{{ $skill['description'] }}</p>
                <div class="flex flex-wrap justify-center gap-2">
                    @foreach($skill['tags'] as $tag)
                    <span class="inline-block px-3 py-1 bg-soft-light dark:bg-midnight-light text-midnight dark:text-soft-light text-sm rounded-full">{{ $tag }}</span>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Experience Timeline --}}
<section class="section-padding bg-white dark:bg-midnight-light">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-header animate-up">
            <span class="badge badge-teal mb-4">Experience</span>
            <h2 class="section-title">
                Professional <span class="text-gradient">Journey</span>
            </h2>
            <p class="section-subtitle">
                My career path and key milestones
            </p>
        </div>

        {{-- Mobile-First Timeline --}}
        <div class="relative">
            <div class="absolute left-4 md:left-1/2 md:transform md:-translate-x-px top-0 bottom-0 w-0.5 bg-gradient-to-b from-teal to-sunset"></div>
            <div class="space-y-8 md:space-y-12 animate-stagger">
                @foreach($about['experience'] as $i => $exp)
                @php $color = $exp['color'] === 'sunset' ? 'sunset' : 'teal'; $even = $i % 2 !== 0; @endphp
                <div class="relative">
                    <div class="ml-12 md:ml-0 md:grid md:grid-cols-2 md:gap-8 md:items-center">
                        <div class="{{ $even ? 'md:col-start-2 md:pl-8' : 'md:text-right md:pr-8' }}">
                            <div class="card p-6 hover:shadow-lg transition-shadow duration-300 border-l-4 border-{{ $color }}">
                                <div class="flex items-center mb-3 {{ $even ? '' : 'md:justify-end' }}">
                                    <div class="w-2 h-2 bg-{{ $color }} rounded-full mr-3 md:hidden"></div>
                                    <span class="text-sm font-medium text-{{ $color }}">{{ $exp['period'] }}</span>
                                </div>
                                <h3 class="text-xl font-bold text-midnight dark:text-soft-light mb-2">{{ $exp['title'] }}</h3>
                                <p class="text-{{ $color }} font-semibold mb-3">{{ $exp['company'] }}</p>
                                <p class="text-soft-dark dark:text-soft text-sm leading-relaxed">{{ $exp['description'] }}</p>
                                @if(!empty($exp['tags']))
                                <div class="flex flex-wrap gap-2 mt-4 {{ $even ? '' : 'md:justify-end' }}">
                                    @foreach($exp['tags'] as $tag)
                                    <span class="px-3 py-1 bg-{{ $color }}/10 text-{{ $color }} text-xs rounded-full">{{ $tag }}</span>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        <div class="absolute left-3.5 md:left-1/2 md:transform md:-translate-x-1/2 w-3 h-3 bg-{{ $color }} rounded-full border-4 border-white dark:border-midnight-light shadow-md"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</section>

{{-- Values & Approach --}}
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="section-header animate-up">
            <span class="badge badge-sunset mb-4">Philosophy</span>
            <h2 class="section-title">
                My <span class="text-gradient">Approach</span>
            </h2>
            <p class="section-subtitle">
                The principles that guide my work and ensure project success
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 animate-stagger">
            @foreach($about['values'] as $val)
            @php $color = $val['color'] === 'sunset' ? 'sunset' : 'teal'; @endphp
            <div class="card card-hover p-6 text-center">
                <div class="w-16 h-16 bg-{{ $color }}/10 rounded-full mx-auto mb-4 flex items-center justify-center">
                    <svg class="w-8 h-8 text-{{ $color }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-midnight dark:text-soft-light mb-2">{{ $val['title'] }}</h3>
                <p class="text-soft-dark dark:text-soft text-sm">{{ $val['description'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Fun Facts / Stats --}}
<section class="section-padding bg-gradient-to-br from-midnight via-ocean to-midnight-dark text-soft-light relative overflow-hidden">
    {{-- Background Elements --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="float-element absolute top-10 left-10 w-32 h-32 bg-teal/20 rounded-full blur-2xl"></div>
        <div class="float-element absolute bottom-10 right-10 w-40 h-40 bg-sunset/20 rounded-full blur-2xl"></div>
    </div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
        <div class="section-header animate-up">
            <h2 class="text-3xl lg:text-4xl font-bold text-soft-light mb-4">By the <span class="text-gradient">Numbers</span></h2>
            <p class="text-lg text-soft max-w-2xl mx-auto">Some interesting facts about my journey</p>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center animate-stagger">
            @foreach($about['stats'] as $stat)
            @php $color = $stat['color'] === 'sunset' ? 'sunset' : 'teal'; @endphp
            <div>
                <div class="text-4xl font-black text-{{ $color }} mb-2">{{ $stat['value'] }}</div>
                <div class="text-soft">{{ $stat['label'] }}</div>
            </div>
            @endforeach
        </div>
    </div>
</section>

{{-- Personal Touch --}}
<section class="section-padding bg-white dark:bg-midnight-light">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="animate-up text-3xl font-bold text-midnight dark:text-soft-light mb-6">
            Beyond <span class="text-gradient">Coding</span>
        </h2>
        <p class="animate-up text-lg text-soft-dark dark:text-soft mb-8">
            {{ $about['personal_bio'] }}
        </p>

        @if(!empty($about['interests']))
        <div class="animate-up flex flex-wrap justify-center gap-4 mb-8">
            @foreach($about['interests'] as $interest)
            <span class="px-4 py-2 card rounded-full text-soft-dark dark:text-soft">
                {{ $interest }}
            </span>
            @endforeach
        </div>
        @endif

        <div class="animate-up flex flex-col sm:flex-row justify-center gap-4">
            <a href="{{ route('contact') }}" class="btn-primary">
                Let's Work Together
                <svg class="ml-2 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3">
                    </path>
                </svg>
            </a>
            <a href="{{ route('blog.index') }}" class="btn-secondary">
                Read My Articles
            </a>
        </div>
    </div>
</section>
@endsection