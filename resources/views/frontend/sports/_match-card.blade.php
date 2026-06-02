{{-- Reusable match result card --}}
<a href="{{ route('sports.match', $match->slug) }}" class="card card-hover p-6 block">
    <div class="flex items-center justify-between mb-4">
        <span class="text-xs text-soft-dark dark:text-soft">{{ $match->match_type }}</span>
        <span class="text-xs px-2 py-1 bg-soft/20 text-soft-dark dark:text-soft rounded-full">
            {{ $match->ended_at?->diffForHumans() ?? ($match->scheduled_at?->format('M j') ?? 'Completed') }}
        </span>
    </div>

    <div class="space-y-3">
        @foreach([['team' => $match->homeTeam, 'score' => $match->formatted_home_score, 'primary' => true], ['team' => $match->awayTeam, 'score' => $match->formatted_away_score, 'primary' => false]] as $side)
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                @if($side['team']?->logo_url)
                <img src="{{ $side['team']->logo_url }}" alt="{{ $side['team']->name }}" class="w-8 h-8 object-contain rounded-sm">
                @else
                <div class="w-8 h-8 rounded bg-teal/10 flex items-center justify-center text-xs font-bold text-teal">
                    {{ $side['team']?->abbreviation ?? '?' }}
                </div>
                @endif
                <span class="font-medium {{ $side['primary'] ? 'text-midnight dark:text-soft-light' : 'text-soft-dark dark:text-soft' }}">
                    {{ $side['team']?->name ?? 'TBD' }}
                </span>
            </div>
            <span class="{{ $side['score'] === 'Yet to bat' ? 'text-sm font-normal italic text-soft/50' : 'text-lg font-bold ' . ($side['primary'] ? 'text-midnight dark:text-soft-light' : 'text-soft-dark dark:text-soft') }}">
                {{ $side['score'] }}
            </span>
        </div>
        @endforeach
    </div>

    @if($match->result_summary)
    <div class="mt-4 pt-3 border-t border-soft/10">
        <p class="text-xs text-teal font-medium">{{ $match->result_summary }}</p>
    </div>
    @endif
</a>
