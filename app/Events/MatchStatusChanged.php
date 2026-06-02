<?php

namespace App\Events;

use App\Models\SportMatch;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class MatchStatusChanged implements ShouldBroadcast
{
    use SerializesModels;

    public function __construct(
        public SportMatch $match,
        public string $previousStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('sports.live'),
            new Channel('sports.match.' . $this->match->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'match.status_changed';
    }

    public function broadcastWith(): array
    {
        return [
            'match_id' => $this->match->id,
            'slug' => $this->match->slug,
            'sport_slug' => $this->match->sport->slug,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->match->status,
            'title' => $this->match->title,
            'result_summary' => $this->match->result_summary,
            'url' => route('sports.match', [$this->match->sport->slug, $this->match->slug]),
        ];
    }
}
