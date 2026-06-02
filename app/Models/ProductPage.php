<?php

namespace App\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use League\CommonMark\CommonMarkConverter;

class ProductPage extends Model
{
    use HasFactory, Sluggable;

    protected $fillable = [
        'project_id', 'title', 'slug', 'type', 'content',
        'is_published', 'sort_order',
    ];

    protected $casts = [
        'content'      => 'array',
        'is_published' => 'boolean',
    ];

    public function sluggable(): array
    {
        return ['slug' => ['source' => 'title']];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * Render markdown content (for custom page type).
     */
    public function getRenderedContentAttribute()
    {
        $markdown = $this->content['markdown'] ?? '';
        if (!$markdown) {
            return '';
        }

        $converter = new CommonMarkConverter([
            'html_input'         => 'strip',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($markdown)->getContent();
    }
}
