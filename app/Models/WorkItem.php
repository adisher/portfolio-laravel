<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'type', 'project_id', 'blog_category_id', 'active', 'sort_order',
        'tagline', 'target_audience', 'how_it_helps', 'call_to_action', 'tech_stack', 'url', 'notes', 'stories',
        'pain_points', 'objections', 'key_outcomes', 'proof_links',
        'differentiators', 'target_keywords', 'article_angles', 'hooks', 'voices', 'screenshots',
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
        'hooks'           => 'array',
        'voices'          => 'array',
        'screenshots'     => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function blogCategory()
    {
        return $this->belongsTo(Category::class, 'blog_category_id');
    }

    public function voiceRecords()
    {
        return $this->hasMany(WorkItemVoice::class)->orderBy('sort_order')->orderByDesc('id');
    }

    public function approvedVoices()
    {
        return $this->voiceRecords()->where('status', 'approved');
    }

    public function voiceSearchRuns()
    {
        return $this->hasMany(VoiceSearchRun::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
