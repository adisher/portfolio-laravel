<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class Tournament extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name', 'slug', 'short_name', 'sport_id', 'api_tournament_id',
        'logo', 'country', 'season', 'start_date', 'end_date',
        'is_active', 'is_featured',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function sport()
    {
        return $this->belongsTo(Sport::class);
    }

    public function matches()
    {
        return $this->hasMany(SportMatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    /**
     * Get the correct logo URL — handles both ESPN CDN URLs and local storage paths.
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo) {
            return null;
        }

        if (str_starts_with($this->logo, 'http://') || str_starts_with($this->logo, 'https://')) {
            return $this->logo;
        }

        return Storage::url($this->logo);
    }

    /**
     * Ongoing tournaments — have live/scheduled matches within ±7 days, or date range includes today.
     */
    public function scopeOngoing($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('start_date')
                        ->whereNotNull('end_date')
                        ->where('start_date', '<=', Carbon::today())
                        ->where('end_date', '>=', Carbon::today());
                })
                ->orWhere(function ($sub) {
                    $sub->whereHas('matches', function ($mq) {
                        $mq->whereIn('status', ['live', 'scheduled', 'completed'])
                            ->where('scheduled_at', '>=', Carbon::today()->subDays(7))
                            ->where('scheduled_at', '<=', Carbon::today()->addDays(7));
                    });
                });
            });
    }

    /**
     * Upcoming tournaments — start in the future or only have future scheduled matches.
     */
    public function scopeUpcomingTournaments($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->where(function ($sub) {
                    $sub->whereNotNull('start_date')
                        ->where('start_date', '>', Carbon::today());
                })
                ->orWhere(function ($sub) {
                    $sub->whereNull('start_date')
                        ->whereHas('matches', function ($mq) {
                            $mq->where('status', 'scheduled')
                                ->where('scheduled_at', '>', Carbon::today()->addDays(7));
                        });
                });
            });
    }
}
