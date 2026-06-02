<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\SportMatch;
use App\Models\Tournament;
use App\Services\Sports\CricbuzzApiService;

class SportsController extends Controller
{
    public function __construct(
        protected CricbuzzApiService $cricbuzz
    ) {}

    /**
     * T20 Cricket World Cup 2026 — dedicated page.
     */
    public function index()
    {
        // Auto-fetch fresh data when cache is stale
        $this->cricbuzz->lazySyncIfStale();

        $seriesId = config('sports.cricbuzz.series_id');
        $tournament = Tournament::where('api_tournament_id', "cricbuzz:{$seriesId}")->first();
        $tournamentId = $tournament?->id;

        $liveMatches = SportMatch::live()
            ->when($tournamentId, fn($q) => $q->where('tournament_id', $tournamentId))
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->latest('started_at')
            ->get();

        $upcomingMatches = SportMatch::upcoming()
            ->when($tournamentId, fn($q) => $q->where('tournament_id', $tournamentId))
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->orderBy('scheduled_at')
            ->take(12)
            ->get();

        $recentResults = SportMatch::completed()
            ->when($tournamentId, fn($q) => $q->where('tournament_id', $tournamentId))
            ->with(['homeTeam', 'awayTeam', 'tournament'])
            ->latest('ended_at')
            ->take(12)
            ->get();

        // All matches grouped by date for schedule view
        $allMatches = SportMatch::query()
            ->when($tournamentId, fn($q) => $q->where('tournament_id', $tournamentId))
            ->with(['homeTeam', 'awayTeam'])
            ->orderBy('scheduled_at')
            ->get()
            ->groupBy(fn($m) => $m->scheduled_at?->format('Y-m-d') ?? 'TBD');

        return view('frontend.sports.index', compact(
            'tournament', 'liveMatches', 'upcomingMatches', 'recentResults', 'allMatches'
        ));
    }

    /**
     * Individual match detail page.
     */
    public function match($matchSlug)
    {
        $match = SportMatch::where('slug', $matchSlug)
            ->with(['sport', 'homeTeam', 'awayTeam', 'tournament'])
            ->firstOrFail();

        // If live, fetch fresh detail data
        if ($match->status === 'live') {
            $this->cricbuzz->syncMatchDetails($match);
            $match->refresh();
        }

        $match->increment('views');

        $relatedMatches = SportMatch::where('tournament_id', $match->tournament_id)
            ->where('id', '!=', $match->id)
            ->with(['homeTeam', 'awayTeam'])
            ->latest('scheduled_at')
            ->take(6)
            ->get();

        return view('frontend.sports.match', compact('match', 'relatedMatches'));
    }
}
