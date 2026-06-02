<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solution extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'title', 'slug', 'description', 'icon', 'keywords',
        'accent_color', 'is_featured', 'is_published', 'sort_order',
    ];

    protected $casts = [
        'keywords'     => 'array',
        'is_featured'  => 'boolean',
        'is_published' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title']];
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
