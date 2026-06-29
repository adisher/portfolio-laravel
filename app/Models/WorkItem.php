<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'project_id', 'active', 'sort_order',
        'tagline', 'target_audience', 'how_it_helps', 'call_to_action', 'tech_stack', 'url', 'notes',
        'pain_points', 'objections', 'key_outcomes', 'proof_links',
        'differentiators', 'target_keywords', 'article_angles',
    ];

    protected $casts = [
        'active'          => 'boolean',
        'pain_points'     => 'array',
        'objections'      => 'array',
        'key_outcomes'    => 'array',
        'proof_links'     => 'array',
        'differentiators' => 'array',
        'target_keywords' => 'array',
        'article_angles'  => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
