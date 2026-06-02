<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SportMatch;
use App\Services\Sports\CricbuzzApiService;
use Illuminate\Support\Facades\Cache;

class SportsApiController extends Controller
{
    public function live()
    {
        $matches = Cache::remember('sports:live_matches', 15, function () {
            $liveMatches = SportMatch::live()
                ->with(['sport', 'homeTeam', 'awayTeam', 'tournament'])
                ->latest('started_at')
                ->get();

            $upcomingToday = SportMatch::upcoming()
                ->whereDate('scheduled_at', today())
                ->with(['sport', 'homeTeam', 'awayTeam', 'tournament'])
                ->orderBy('scheduled_at')
                ->take(5)
                ->get();

            return $liveMatches->merge($upcomingToday)->map(function ($match) {
                return [
                    'match_id' => $match->id,
                    'slug' => $match->slug,
                    'title' => $match->title,
                    'status' => $match->status,
                    'home_team' => $match->homeTeam?->short_name ?? $match->homeTeam?->name ?? 'TBD',
                    'away_team' => $match->awayTeam?->short_name ?? $match->awayTeam?->name ?? 'TBD',
                    'home_abbr' => $match->homeTeam?->abbreviation ?? '',
                    'away_abbr' => $match->awayTeam?->abbreviation ?? '',
                    'home_logo' => $match->homeTeam?->logo_url,
                    'away_logo' => $match->awayTeam?->logo_url,
                    'home_display_score' => $match->formatted_home_score,
                    'away_display_score' => $match->formatted_away_score,
                    'match_type' => $match->match_type,
                    'result_summary' => $match->result_summary,
                    'tournament' => $match->tournament?->short_name ?? $match->tournament?->name,
                    'scheduled_at' => $match->scheduled_at?->toIso8601String(),
                    'url' => route('sports.match', $match->slug),
                ];
            })->values();
        });

        return response()->json(['matches' => $matches]);
    }

    public function matchScore(int $id)
    {
        $match = SportMatch::with(['sport', 'homeTeam', 'awayTeam'])
            ->findOrFail($id);

        $ttl = $match->status === 'live' ? 10 : 60;

        $data = Cache::remember("sports:match_score:{$id}", $ttl, function () use ($match) {
            return [
                'match_id' => $match->id,
                'status' => $match->status,
                'home_score' => $match->home_score,
                'away_score' => $match->away_score,
                'home_display_score' => $match->formatted_home_score,
                'away_display_score' => $match->formatted_away_score,
                'result_summary' => $match->result_summary,
            ];
        });

        return response()->json($data);
    }

    /**
     * Detailed match data for Alpine.js polling (cricket-specific).
     * Returns batsmen, bowler, recent balls, etc.
     */
    public function matchDetail(int $id, CricbuzzApiService $cricbuzz)
    {
        $match = SportMatch::with(['homeTeam', 'awayTeam'])
            ->findOrFail($id);

        // If live, trigger fresh sync
        if ($match->status === 'live') {
            $cricbuzz->syncMatchDetails($match);
            $match->refresh();
        }

        $metadata = $match->metadata ?? [];

        return response()->json([
            'match_id' => $match->id,
            'status' => $match->status,
            'home_display_score' => $match->formatted_home_score,
            'away_display_score' => $match->formatted_away_score,
            'home_score' => $match->home_score,
            'away_score' => $match->away_score,
            'result_summary' => $match->result_summary,
            'match_status' => $metadata['match_status'] ?? null,
            'batsmen' => $metadata['batsmen'] ?? [],
            'bowler' => $metadata['bowler'] ?? [],
            'recent_balls' => $metadata['recent_balls'] ?? [],
            'latest_ball' => $metadata['latest_ball'] ?? null,
            'player_of_match' => $metadata['player_of_match'] ?? [],
            'result' => $metadata['result'] ?? null,
            'team_scores' => $metadata['team_scores'] ?? [],
        ]);
    }

    public function matchEvents(int $id)
    {
        $match = SportMatch::with(['events.team'])->findOrFail($id);

        $events = $match->events->map(function ($event) {
            return [
                'id' => $event->id,
                'event_type' => $event->event_type,
                'period' => $event->period,
                'match_time' => $event->match_time,
                'minute' => $event->minute,
                'player_name' => $event->player_name,
                'description' => $event->description,
                'team' => $event->team?->short_name ?? $event->team?->name,
                'occurred_at' => $event->occurred_at?->toIso8601String(),
            ];
        });

        return response()->json(['events' => $events]);
    }
}
