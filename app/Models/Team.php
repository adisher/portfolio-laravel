<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Team extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name', 'slug', 'short_name', 'abbreviation', 'sport_id',
        'api_team_id', 'logo', 'country', 'country_code',
        'primary_color', 'metadata', 'is_active',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_active' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function homeMatches()
    {
        return $this->hasMany(SportMatch::class, 'home_team_id');
    }

    public function awayMatches()
    {
        return $this->hasMany(SportMatch::class, 'away_team_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the correct logo URL — handles both ESPN CDN URLs and local storage paths.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        // External URLs (ESPN CDN) — return as-is
        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        // Local storage paths
        return Storage::url($this->logo);
    }
}
