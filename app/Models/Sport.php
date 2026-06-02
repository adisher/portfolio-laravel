<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sport extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'name', 'slug', 'icon', 'color', 'api_sport_id',
        'scoring_format', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'scoring_format' => 'array',
        'is_active' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'name']];
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function matches()
    {
        return $this->hasMany(SportMatch::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
