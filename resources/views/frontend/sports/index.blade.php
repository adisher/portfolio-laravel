@extends('layouts.app')

@section('title', 'T20 Cricket World Cup 2026 - Live Scores & Schedule')
@section('description', 'Follow ICC T20 Cricket World Cup 2026 live scores, match schedule, results, and ball-by-ball updates.')

@push('schema')
<x-schema.breadcrumb :items="[
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'T20 World Cup 2026'],
]" />
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "SportsEvent",
    "name": "ICC T20 Cricket World Cup 2026",
    "url": "{{ route('sports.index') }}",
    "sport": "Cricket"
}
</script>
@endpush

@section('content')

{{-- WC 2026 Hero Header --}}
<section class="bg-gradient-to-br from-midnight via-ocean to-midnight-dark py-12 lg:py-16 relative overflow-hidden">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full blur-3xl bg-teal/5"></div>
        <div class="absolute bottom-0 right-1/4 w-64 h-64 rounded-full blur-3xl bg-sunset/5"></div>
    </div>

    <div class="max-w-7xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="text-center">
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-teal/10 border border-teal/20 mb-4">
                <svg class="w-4 h-4 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" stroke-width="2"/>
                    <path stroke-width="2" d="M8 12h8M12 8v8"/>
                </svg>
                <span class="text-teal text-sm font-medium">ICC Cricket</span>
            </div>
            <h1 class="text-3xl lg:text-5xl font-black text-soft-light mb-3">T20 World Cup 2026</h1>
            <p class="text-soft/60 text-lg">Live scores, schedule & results</p>

            @if($liveMatches->count())
            <div class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-500/10 border border-red-500/20">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <span class="text-red-400 text-sm font-medium">{{ $liveMatches->count() }} {{ Str::plural('match', $liveMatches->count()) }} live now</span>
            </div>
            @endif
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- LIVE MATCHES                                                   --}}
{{-- ============================================================ --}}
@if($liveMatches->count())
<section class="section-padding bg-soft-light dark:bg-midnight" x-data="liveScores()" x-init="init()">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <div class="flex items-center gap-3 mb-8">
            <div class="w-3 h-3 bg-red-500 rounded-full animate-pulse"></div>
            <h2 class="text-2xl font-bold text-midnight dark:text-soft-light">Live Now</h2>
        </div>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($liveMatches as $match)
            <a href="{{ route('sports.match', $match->slug) }}" class="card card-hover p-6 block group">
                {{-- Match Type & Status --}}
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs text-soft-dark dark:text-soft">{{ $match->match_type }}</span>
                    <span class="flex items-center gap-1.5 text-xs px-2 py-1 bg-red-500/20 text-red-400 rounded-full font-medium">
                        <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span> LIVE
                    </span>
                </div>

                {{-- Teams & Scores --}}
                <div class="space-y-3">
                    @foreach([['team' => $match->homeTeam, 'score' => $match->formatted_home_score], ['team' => $match->awayTeam, 'score' => $match->formatted_away_score]] as $side)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            @if($side['team']->logo_url)
                            <img src="{{ $side['team']->logo_url }}" alt="{{ $side['team']->name }}" class="w-8 h-8 object-contain rounded-sm">
                            @else
                            <div class="w-8 h-8 rounded bg-teal/10 flex items-center justify-center text-xs font-bold text-teal">
                                {{ $side['team']->abbreviation }}
                            </div>
                            @endif
                            <span class="font-medium text-midnight dark:text-soft-light">{{ $side['team']->abbreviation }}</span>
                        </div>
                        <span class="{{ $side['score'] === 'Yet to bat' ? 'text-sm font-normal italic text-soft/50' : 'text-lg font-bold text-midnight dark:text-soft-light' }}">{{ $side['score'] }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Status Text --}}
                @if($match->result_summary)
                <div class="mt-4 pt-3 border-t border-soft/10">
                    <p class="text-xs text-teal font-medium">{{ $match->result_summary }}</p>
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================ --}}
{{-- RESULTS (shown at top when no live matches)                    --}}
{{-- ============================================================ --}}
@if(!$liveMatches->count() && $recentResults->count())
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-8 animate-up">Latest Results</h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($recentResults as $match)
            @include('frontend.sports._match-card', ['match' => $match])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================ --}}
{{-- UPCOMING MATCHES                                               --}}
{{-- ============================================================ --}}
@if($upcomingMatches->count())
<section class="section-padding bg-white dark:bg-midnight-light">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-8 animate-up">Upcoming Matches</h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($upcomingMatches as $match)
            <a href="{{ route('sports.match', $match->slug) }}" class="card card-hover p-6 block">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs text-soft-dark dark:text-soft">{{ $match->match_type }}</span>
                </div>

                <div class="flex gap-4">
                    {{-- Date Block --}}
                    @if($match->scheduled_at)
                    <div class="flex-shrink-0 w-16 text-center rounded-lg bg-teal/10 py-2 px-1">
                        <span class="block text-xs font-semibold text-teal uppercase">{{ $match->scheduled_at->format('M') }}</span>
                        <span class="block text-2xl font-black text-midnight dark:text-soft-light leading-tight">{{ $match->scheduled_at->format('j') }}</span>
                        <span class="block text-xs text-soft-dark dark:text-soft">{{ $match->scheduled_at->format('g:i A') }}</span>
                    </div>
                    @endif

                    <div class="flex-1 space-y-2">
                        @foreach([['team' => $match->homeTeam], ['team' => $match->awayTeam]] as $i => $side)
                        <div class="flex items-center gap-2">
                            @if($side['team']?->logo_url)
                            <img src="{{ $side['team']->logo_url }}" alt="{{ $side['team']->name }}" class="w-7 h-7 object-contain rounded-sm">
                            @endif
                            <span class="text-sm font-medium text-midnight dark:text-soft-light">{{ $side['team']?->name ?? 'TBD' }}</span>
                        </div>
                        @if($i === 0)
                        <div class="text-xs text-soft-dark dark:text-soft font-medium px-1">VS</div>
                        @endif
                        @endforeach
                    </div>
                </div>

                @if($match->venue)
                <div class="mt-4 pt-3 border-t border-soft/10 text-xs text-soft-dark dark:text-soft">
                    {{ $match->venue }}
                </div>
                @endif
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================ --}}
{{-- RESULTS (shown below when live matches exist)                  --}}
{{-- ============================================================ --}}
@if($liveMatches->count() && $recentResults->count())
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-8 animate-up">Recent Results</h2>

        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 animate-stagger">
            @foreach($recentResults as $match)
            @include('frontend.sports._match-card', ['match' => $match])
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ============================================================ --}}
{{-- FULL SCHEDULE                                                  --}}
{{-- ============================================================ --}}
@if($allMatches->count())
<section class="section-padding bg-white dark:bg-midnight-light">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <h2 class="text-2xl font-bold text-midnight dark:text-soft-light mb-8 animate-up">Full Schedule</h2>

        <div class="space-y-6">
            @foreach($allMatches as $date => $matches)
            <div class="animate-up">
                <h3 class="text-sm font-semibold text-teal uppercase tracking-wider mb-3">
                    @if($date !== 'TBD')
                        {{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}
                    @else
                        Date TBD
                    @endif
                </h3>
                <div class="space-y-2">
                    @foreach($matches as $match)
                    <a href="{{ route('sports.match', $match->slug) }}"
                       class="flex items-center gap-4 p-4 rounded-xl bg-soft-light dark:bg-midnight hover:bg-soft/30 dark:hover:bg-ocean/20 transition-colors">
                        {{-- Time --}}
                        <div class="flex-shrink-0 w-16 text-center">
                            @if($match->status === 'live')
                            <span class="text-xs font-bold text-red-400 flex items-center gap-1 justify-center">
                                <span class="w-1.5 h-1.5 bg-red-500 rounded-full animate-pulse"></span> LIVE
                            </span>
                            @elseif($match->status === 'completed')
                            <span class="text-xs font-medium text-soft-dark dark:text-soft">FT</span>
                            @elseif($match->status === 'abandoned')
                            <span class="text-xs font-medium text-yellow-500">ABD</span>
                            @else
                            <span class="text-xs font-medium text-soft-dark dark:text-soft">{{ $match->scheduled_at?->format('g:i A') ?? '--' }}</span>
                            @endif
                        </div>

                        {{-- Teams --}}
                        <div class="flex-1 flex items-center gap-4 min-w-0">
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                @if($match->homeTeam?->logo_url)
                                <img src="{{ $match->homeTeam->logo_url }}" class="w-5 h-5 object-contain rounded-sm flex-shrink-0">
                                @endif
                                <span class="text-sm font-medium text-midnight dark:text-soft-light truncate">{{ $match->homeTeam?->abbreviation ?? 'TBD' }}</span>
                            </div>
                            <span class="text-xs text-soft-dark dark:text-soft flex-shrink-0">vs</span>
                            <div class="flex items-center gap-2 flex-1 min-w-0">
                                @if($match->awayTeam?->logo_url)
                                <img src="{{ $match->awayTeam->logo_url }}" class="w-5 h-5 object-contain rounded-sm flex-shrink-0">
                                @endif
                                <span class="text-sm font-medium text-midnight dark:text-soft-light truncate">{{ $match->awayTeam?->abbreviation ?? 'TBD' }}</span>
                            </div>
                        </div>

                        {{-- Scores (if played) --}}
                        @if($match->status === 'completed' || $match->status === 'live')
                        <div class="flex-shrink-0 text-right text-sm">
                            <span class="{{ $match->formatted_home_score === 'Yet to bat' ? 'font-normal italic text-soft/50' : 'font-bold text-midnight dark:text-soft-light' }}">{{ $match->formatted_home_score }}</span>
                            <span class="text-soft/50 mx-1">-</span>
                            <span class="{{ $match->formatted_away_score === 'Yet to bat' ? 'font-normal italic text-soft/50' : 'font-bold text-soft-dark dark:text-soft' }}">{{ $match->formatted_away_score }}</span>
                        </div>
                        @elseif($match->status === 'abandoned')
                        <div class="flex-shrink-0 text-right">
                            <span class="text-xs text-yellow-500">{{ $match->result_summary ?: 'Abandoned' }}</span>
                        </div>
                        @endif

                        {{-- Match Type --}}
                        <div class="flex-shrink-0 hidden md:block">
                            <span class="text-xs text-soft-dark dark:text-soft">{{ $match->match_type }}</span>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- Empty State --}}
@if(!$liveMatches->count() && !$upcomingMatches->count() && !$recentResults->count())
<section class="section-padding bg-soft-light dark:bg-midnight">
    <div class="max-w-7xl mx-auto px-6 lg:px-8 text-center">
        <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-teal/10 flex items-center justify-center">
            <svg class="w-8 h-8 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
        </div>
        <h2 class="text-xl font-bold text-midnight dark:text-soft-light mb-2">No Matches Yet</h2>
        <p class="text-soft-dark dark:text-soft">The tournament schedule hasn't been synced yet. Configure your CRICBUZZ_SERIES_ID and run the sync.</p>
    </div>
</section>
@endif

@endsection
