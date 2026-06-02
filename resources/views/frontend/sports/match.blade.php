@extends('layouts.app')

@section('title', $match->title . ' - T20 World Cup 2026')
@section('description', $match->meta_description ?? $match->title . ' live scorecard, updates, and match details.')

@push('schema')
<x-schema.sports-event :match="$match" />
<x-schema.breadcrumb :items="[
    ['name' => 'Home', 'url' => route('home')],
    ['name' => 'T20 World Cup 2026', 'url' => route('sports.index')],
    ['name' => $match->title],
]" />
@endpush

@push('meta')
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $match->title }} - T20 WC 2026">
<meta property="og:description" content="{{ $match->meta_description ?? $match->result_summary ?? 'Follow live updates for ' . $match->title }}">
<meta property="og:url" content="{{ route('sports.match', $match->slug) }}">
<meta name="twitter:card" content="summary">
<meta name="twitter:title" content="{{ $match->title }} - T20 WC 2026">
@endpush

@section('content')
<div x-data="matchDetail({{ $match->id }})" x-init="init()">

{{-- ============================================================ --}}
{{-- MATCH HEADER — Wide dramatic layout                           --}}
{{-- ============================================================ --}}
<section class="bg-gradient-to-br from-midnight via-ocean to-midnight-dark py-10 lg:py-16 relative overflow-hidden">
    {{-- Background decorative orbs --}}
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute top-0 left-1/4 w-96 h-96 rounded-full blur-3xl bg-teal/5"></div>
        <div class="absolute bottom-0 right-1/4 w-80 h-80 rounded-full blur-3xl bg-sunset/5"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[300px] rounded-full blur-3xl bg-teal/[0.02]"></div>
    </div>

    <div class="max-w-6xl mx-auto px-6 lg:px-8 relative z-10">
        {{-- Breadcrumb --}}
        <nav class="flex mb-6" aria-label="Breadcrumb">
            <ol class="inline-flex items-center space-x-2 text-sm">
                <li><a href="{{ route('home') }}" class="text-soft/70 hover:text-teal transition-colors">Home</a></li>
                <li class="text-soft/40">/</li>
                <li><a href="{{ route('sports.index') }}" class="text-soft/70 hover:text-teal transition-colors">T20 WC 2026</a></li>
                <li class="text-soft/40">/</li>
                <li class="text-soft">{{ $match->homeTeam?->abbreviation }} vs {{ $match->awayTeam?->abbreviation }}</li>
            </ol>
        </nav>

        {{-- Tournament & Status --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                @if($match->tournament)
                <span class="text-xs px-3 py-1 rounded-full bg-teal/10 text-teal border border-teal/20">
                    {{ $match->tournament->name }}
                </span>
                @endif
                @if($match->match_type)
                <span class="text-xs text-soft/60">{{ $match->match_type }}</span>
                @endif
            </div>

            @if($match->status === 'live')
            <span class="flex items-center gap-2 text-sm px-3 py-1 rounded-full bg-red-500/10 border border-red-500/20">
                <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                <span class="text-red-400 font-medium">LIVE</span>
            </span>
            @elseif($match->status === 'completed')
            <span class="text-sm text-soft/70 px-3 py-1 rounded-full bg-soft/10">Completed</span>
            @else
            <span class="text-sm text-soft/70 px-3 py-1 rounded-full bg-soft/10">{{ ucfirst($match->status) }}</span>
            @endif
        </div>

        {{-- Date & Venue --}}
        @if($match->scheduled_at)
        <div class="mb-8 text-sm text-soft/80 flex flex-wrap items-center gap-x-4 gap-y-1">
            <span>{{ $match->scheduled_at->format('l, F j, Y') }} at {{ $match->scheduled_at->format('g:i A') }}</span>
            @if($match->venue)
            <span class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                </svg>
                {{ $match->venue }}
            </span>
            @endif
        </div>
        @endif

        {{-- Wide Scorecard --}}
        <div class="bg-white/5 backdrop-blur-sm rounded-2xl p-6 lg:p-10 border border-white/10 relative overflow-hidden">
            {{-- Faint team abbreviation watermarks --}}
            <span class="absolute top-1/2 left-4 -translate-y-1/2 text-6xl lg:text-8xl font-black text-white/[0.03] select-none pointer-events-none leading-none">
                {{ $match->homeTeam?->abbreviation }}
            </span>
            <span class="absolute top-1/2 right-4 -translate-y-1/2 text-6xl lg:text-8xl font-black text-white/[0.03] select-none pointer-events-none leading-none">
                {{ $match->awayTeam?->abbreviation }}
            </span>

            <div class="grid grid-cols-3 gap-4 lg:gap-8 items-center relative z-10">
                {{-- Home Team --}}
                <div class="text-center">
                    <div class="relative inline-block mb-3">
                        {{-- Radial glow behind flag --}}
                        <div class="absolute inset-0 scale-[2] rounded-full bg-gradient-radial from-teal/15 to-transparent blur-xl"></div>
                        @if($match->homeTeam?->logo_url)
                        <img src="{{ str_replace('/w80/', '/w160/', $match->homeTeam->logo_url) }}"
                             alt="{{ $match->homeTeam->name }}"
                             class="w-20 h-20 md:w-28 md:h-28 lg:w-32 lg:h-32 object-contain relative z-10 drop-shadow-lg">
                        @else
                        <div class="w-20 h-20 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-full bg-teal/10 flex items-center justify-center text-2xl lg:text-3xl font-black text-teal relative z-10">
                            {{ $match->homeTeam?->abbreviation }}
                        </div>
                        @endif
                    </div>
                    <h2 class="text-base lg:text-xl font-bold text-soft-light">{{ $match->homeTeam?->name ?? 'TBD' }}</h2>
                    <span class="text-xs text-soft/50">{{ $match->homeTeam?->abbreviation }}</span>
                </div>

                {{-- Scores --}}
                <div class="text-center space-y-1">
                    <div class="space-y-0.5">
                        <div class="text-xl md:text-2xl lg:text-3xl font-black text-soft-light inline-block"
                             :class="{
                                 'score-pop': homeScoreAnimating,
                                 'wicket-flash': wicketFlash
                             }">
                            @php $homeDisplay = $match->formatted_home_score; @endphp
                            <span x-text="homeScore || '{{ $homeDisplay }}'"
                                  class="{{ $homeDisplay === 'Yet to bat' ? 'text-sm md:text-base font-normal italic text-soft/50' : '' }}">{{ $homeDisplay }}</span>
                        </div>
                    </div>

                    <div class="text-soft/30 text-sm font-medium tracking-wider">VS</div>

                    <div class="space-y-0.5">
                        <div class="text-xl md:text-2xl lg:text-3xl font-black text-soft-light inline-block"
                             :class="{
                                 'score-pop': awayScoreAnimating,
                                 'wicket-flash': wicketFlash
                             }">
                            @php $awayDisplay = $match->formatted_away_score; @endphp
                            <span x-text="awayScore || '{{ $awayDisplay }}'"
                                  class="{{ $awayDisplay === 'Yet to bat' ? 'text-sm md:text-base font-normal italic text-soft/50' : '' }}">{{ $awayDisplay }}</span>
                        </div>
                    </div>
                </div>

                {{-- Away Team --}}
                <div class="text-center">
                    <div class="relative inline-block mb-3">
                        {{-- Radial glow behind flag --}}
                        <div class="absolute inset-0 scale-[2] rounded-full bg-gradient-radial from-sunset/15 to-transparent blur-xl"></div>
                        @if($match->awayTeam?->logo_url)
                        <img src="{{ str_replace('/w80/', '/w160/', $match->awayTeam->logo_url) }}"
                             alt="{{ $match->awayTeam->name }}"
                             class="w-20 h-20 md:w-28 md:h-28 lg:w-32 lg:h-32 object-contain relative z-10 drop-shadow-lg">
                        @else
                        <div class="w-20 h-20 md:w-28 md:h-28 lg:w-32 lg:h-32 rounded-full bg-sunset/10 flex items-center justify-center text-2xl lg:text-3xl font-black text-sunset relative z-10">
                            {{ $match->awayTeam?->abbreviation }}
                        </div>
                        @endif
                    </div>
                    <h2 class="text-base lg:text-xl font-bold text-soft-light">{{ $match->awayTeam?->name ?? 'TBD' }}</h2>
                    <span class="text-xs text-soft/50">{{ $match->awayTeam?->abbreviation }}</span>
                </div>
            </div>

            {{-- Result / Match Status --}}
            <div class="mt-6 text-center relative z-10">
                <p class="text-teal font-medium" x-text="resultSummary || '{{ $match->result_summary ?? '' }}'">
                    {{ $match->result_summary }}
                </p>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- MATCH DETAILS — Cricket background themed                     --}}
{{-- ============================================================ --}}
<section class="section-padding bg-soft-light dark:bg-midnight cricket-bg">
    {{-- Cricket bat decorative element --}}
    <div class="cricket-bat-decor" aria-hidden="true"></div>

    <div class="max-w-4xl mx-auto px-6 lg:px-8 relative z-10">
        <div class="grid lg:grid-cols-3 gap-8">
            {{-- Main Content --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- ============ LIVE: Batsmen at Crease ============ --}}
                <template x-if="status === 'live' && batsmen.length > 0">
                    <div class="card p-6">
                        <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-4 flex items-center gap-2">
                            <span class="w-2 h-2 bg-red-500 rounded-full animate-pulse"></span>
                            Batsmen at Crease
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-soft/20 text-soft-dark dark:text-soft">
                                        <th class="text-left py-2">Batsman</th>
                                        <th class="text-center py-2">R</th>
                                        <th class="text-center py-2">B</th>
                                        <th class="text-center py-2">4s</th>
                                        <th class="text-center py-2">6s</th>
                                        <th class="text-center py-2">SR</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="bat in batsmen" :key="bat.name">
                                        <tr class="border-b border-soft/10">
                                            <td class="py-3 font-medium text-midnight dark:text-soft-light">
                                                <span x-text="bat.name"></span>
                                                <template x-if="bat.striker">
                                                    <span class="text-teal ml-1">*</span>
                                                </template>
                                            </td>
                                            <td class="py-3 text-center font-bold text-midnight dark:text-soft-light" x-text="bat.runs"></td>
                                            <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bat.balls"></td>
                                            <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bat.fours || 0"></td>
                                            <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bat.sixes || 0"></td>
                                            <td class="py-3 text-center text-soft-dark dark:text-soft"
                                                x-text="bat.strike_rate || (bat.balls > 0 ? ((bat.runs / bat.balls) * 100).toFixed(1) : '0.0')"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                {{-- ============ LIVE: Current Bowler ============ --}}
                <template x-if="status === 'live' && bowler && bowler.name">
                    <div class="card p-6">
                        <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-4">Current Bowler</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="border-b border-soft/20 text-soft-dark dark:text-soft">
                                        <th class="text-left py-2">Bowler</th>
                                        <th class="text-center py-2">O</th>
                                        <th class="text-center py-2">M</th>
                                        <th class="text-center py-2">R</th>
                                        <th class="text-center py-2">W</th>
                                        <th class="text-center py-2">Econ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr class="border-b border-soft/10">
                                        <td class="py-3 font-medium text-midnight dark:text-soft-light" x-text="bowler.name"></td>
                                        <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bowler.overs"></td>
                                        <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bowler.maidens"></td>
                                        <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bowler.runs"></td>
                                        <td class="py-3 text-center font-bold text-midnight dark:text-soft-light" x-text="bowler.wickets"></td>
                                        <td class="py-3 text-center text-soft-dark dark:text-soft" x-text="bowler.economy"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </template>

                {{-- ============ LIVE: Recent Balls ============ --}}
                <template x-if="status === 'live' && recentBalls.length > 0">
                    <div class="card p-6">
                        <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-4">Recent Balls</h3>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <template x-for="(ball, idx) in recentBalls.slice().reverse()" :key="idx">
                                <div class="flex items-center gap-3 p-2 rounded-lg"
                                     :class="{
                                        'bg-green-500/10': ball.event === 'FOUR',
                                        'bg-purple-500/10': ball.event === 'SIX',
                                        'bg-red-500/10': ball.event === 'WICKET',
                                        'bg-soft/5 dark:bg-ocean/10': !ball.event
                                     }">
                                    <span class="flex-shrink-0 w-12 text-xs font-bold text-center rounded px-1 py-0.5"
                                          :class="ballColor(ball.event)"
                                          x-text="ball.over"></span>
                                    <span class="text-sm text-midnight dark:text-soft-light flex-1" x-text="ball.text"></span>
                                    <template x-if="ball.event">
                                        <span class="text-xs font-bold px-2 py-0.5 rounded-full"
                                              :class="ballColor(ball.event)"
                                              x-text="ball.event"></span>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                {{-- ============ COMPLETED: Player of Match ============ --}}
                @if($match->status === 'completed' && !empty($match->metadata['player_of_match']['name'] ?? ''))
                <div class="card p-6">
                    <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-3">Player of the Match</h3>
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-teal/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-midnight dark:text-soft-light text-lg">{{ $match->metadata['player_of_match']['name'] }}</p>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ============ UPCOMING: Start Info ============ --}}
                @if($match->status === 'scheduled')
                <div class="card p-6 text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-full bg-teal/10 flex items-center justify-center">
                        <svg class="w-8 h-8 text-teal" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-midnight dark:text-soft-light mb-2">Match Starts Soon</h3>
                    @if($match->scheduled_at)
                    <p class="text-soft-dark dark:text-soft">{{ $match->scheduled_at->format('l, F j, Y \a\t g:i A') }}</p>
                    @if($match->scheduled_at->isFuture())
                    <p class="text-teal font-medium mt-2">{{ $match->scheduled_at->diffForHumans() }}</p>
                    @endif
                    @endif
                    @if(!empty($match->metadata['start_info']['start_text'] ?? ''))
                    <p class="text-soft/70 mt-2">{{ $match->metadata['start_info']['start_text'] }}</p>
                    @endif
                </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <div class="space-y-6">
                {{-- Match Info --}}
                <div class="card p-6">
                    <h3 class="text-lg font-bold text-midnight dark:text-soft-light mb-4">Match Info</h3>
                    <dl class="space-y-3 text-sm">
                        @if($match->match_type)
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Match</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light">{{ $match->match_type }}</dd>
                        </div>
                        @endif
                        @if($match->scheduled_at)
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Date</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light">{{ $match->scheduled_at->format('M j, Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Time</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light">{{ $match->scheduled_at->format('g:i A') }}</dd>
                        </div>
                        @endif
                        @if($match->venue)
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Venue</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light text-right max-w-[60%]">{{ $match->venue }}</dd>
                        </div>
                        @endif
                        @if($match->toss)
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Toss</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light text-right max-w-[60%]">{{ $match->toss }}</dd>
                        </div>
                        @endif
                        <div class="flex justify-between">
                            <dt class="text-soft-dark dark:text-soft">Views</dt>
                            <dd class="font-medium text-midnight dark:text-soft-light">{{ number_format($match->views) }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Share --}}
                <div class="card p-6">
                    <h3 class="text-sm font-bold text-midnight dark:text-soft-light mb-3">Share</h3>
                    <div class="flex gap-3">
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode(route('sports.match', $match->slug)) }}&text={{ urlencode($match->title . ' - T20 WC 2026') }}"
                           target="_blank" rel="noopener" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/></svg>
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('sports.match', $match->slug)) }}"
                           target="_blank" rel="noopener" class="text-soft-dark dark:text-soft hover:text-teal transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ============================================================ --}}
{{-- RELATED MATCHES                                                --}}
{{-- ============================================================ --}}
@if($relatedMatches->count())
<section class="section-padding bg-white dark:bg-midnight-light">
    <div class="max-w-7xl mx-auto px-6 lg:px-8">
        <h2 class="text-xl font-bold text-midnight dark:text-soft-light mb-6">More WC Matches</h2>
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 animate-stagger">
            @foreach($relatedMatches as $related)
            <a href="{{ route('sports.match', $related->slug) }}" class="card card-hover p-4 block">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs px-2 py-0.5 rounded-full {{ $related->status === 'live' ? 'bg-red-500/20 text-red-400' : ($related->status === 'completed' ? 'bg-soft/20 text-soft-dark dark:text-soft' : 'bg-teal/20 text-teal') }}">
                        {{ $related->status === 'live' ? 'LIVE' : ($related->status === 'completed' ? 'FT' : ($related->scheduled_at?->format('M j') ?? 'TBD')) }}
                    </span>
                    <span class="text-xs text-soft-dark dark:text-soft">{{ $related->match_type }}</span>
                </div>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between">
                        <div class="flex items-center gap-2">
                            @if($related->homeTeam?->logo_url)
                            <img src="{{ $related->homeTeam->logo_url }}" class="w-4 h-4 object-contain rounded-sm">
                            @endif
                            <span class="text-midnight dark:text-soft-light">{{ $related->homeTeam?->abbreviation ?? 'TBD' }}</span>
                        </div>
                        @php $relHomeScore = $related->formatted_home_score; @endphp
                        <span class="{{ $relHomeScore === 'Yet to bat' ? 'text-xs italic text-soft/60' : 'font-bold text-midnight dark:text-soft-light' }}">{{ $relHomeScore }}</span>
                    </div>
                    <div class="flex justify-between">
                        <div class="flex items-center gap-2">
                            @if($related->awayTeam?->logo_url)
                            <img src="{{ $related->awayTeam->logo_url }}" class="w-4 h-4 object-contain rounded-sm">
                            @endif
                            <span class="text-soft-dark dark:text-soft">{{ $related->awayTeam?->abbreviation ?? 'TBD' }}</span>
                        </div>
                        @php $relAwayScore = $related->formatted_away_score; @endphp
                        <span class="{{ $relAwayScore === 'Yet to bat' ? 'text-xs italic text-soft/60' : 'font-bold text-soft-dark dark:text-soft' }}">{{ $relAwayScore }}</span>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

</div>{{-- end x-data wrapper --}}
@endsection
