<?php

namespace App\Events;

use App\Models\SportMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ScoreUpdated implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public SportMatch $match,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('sports.live'),
            new Channel('sports.match.' . $this->match->id),
            new Channel('sports.sport.' . $this->match->sport_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'score.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'slug' => $this->match->slug,
            'sport_slug' => $this->match->sport->slug,
            'sport_name' => $this->match->sport->name,
            'status' => $this->match->status,
            'home_team' => $this->match->homeTeam->short_name ?? $this->match->homeTeam->name,
            'away_team' => $this->match->awayTeam->short_name ?? $this->match->awayTeam->name,
            'home_score' => $this->match->home_score,
            'away_score' => $this->match->away_score,
            'home_display_score' => $this->match->formatted_home_score,
            'away_display_score' => $this->match->formatted_away_score,
            'current_period' => $this->match->current_period,
            'match_time' => $this->match->match_time,
            'result_summary' => $this->match->result_summary,
            'url' => route('sports.match', [$this->match->sport->slug, $this->match->slug]),
            'updated_at' => $this->match->updated_at->toIso8601String(),
        ];
    }
}
