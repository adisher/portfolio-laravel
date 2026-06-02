<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SportMatch extends Model
{
    use HasFactory, Sluggable;

    protected $table = 'sport_matches';

    protected $fillable = [
        'title', 'slug', 'sport_id', 'tournament_id', 'home_team_id',
        'away_team_id', 'api_match_id', 'status', 'match_type', 'venue',
        'city', 'country', 'scheduled_at', 'started_at', 'ended_at',
        'current_period', 'match_time', 'home_score', 'away_score',
        'period_scores', 'result_summary', 'toss', 'metadata',
        'featured_image', 'meta_description', 'views', 'is_featured',
    ];

    protected $casts = [
        'home_score' => 'array',
        'away_score' => 'array',
        'period_scores' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'is_featured' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title']];
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function tournament()
    {
        return $this->belongsTo(Tournament::class);
    }

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function awayTeam()
    {
        return $this->belongsTo(Team::class, 'away_team_id');
    }

    public function events()
    {
        return $this->hasMany(MatchEvent::class, 'sport_match_id');
    }

    public function scopeLive($query)
    {
        return $query->where('status', 'live');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'scheduled');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === 'live';
    }

    public function getFormattedHomeScoreAttribute(): string
    {
        return $this->formatScore($this->home_score, $this->status === 'live');
    }

    public function getFormattedAwayScoreAttribute(): string
    {
        return $this->formatScore($this->away_score, $this->status === 'live');
    }

    protected function formatScore(?array $score, bool $yetToBat = false): string
    {
        if (!$score) {
            return $yetToBat ? 'Yet to bat' : '-';
        }

        // Cricket format: runs/wickets (overs)
        if (isset($score['runs'])) {
            $runs = $score['runs'];
            $wickets = $score['wickets'] ?? 0;
            $overs = $score['overs'] ?? '0';
            return "{$runs}/{$wickets} ({$overs})";
        }

        return $yetToBat ? 'Yet to bat' : '-';
    }
}
