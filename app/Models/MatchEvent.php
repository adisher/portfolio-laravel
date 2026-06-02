<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MatchEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'sport_match_id', 'team_id', 'event_type', 'period',
        'match_time', 'player_name', 'description', 'data',
        'minute', 'occurred_at',
    ];

    protected $casts = [
        'data' => 'array',
        'occurred_at' => 'datetime',
    ];

    public function match()
    {
        return $this->belongsTo(SportMatch::class, 'sport_match_id');
    }

    public function team()
    {
        return $this->belongsTo(Team::class);
    }
}
