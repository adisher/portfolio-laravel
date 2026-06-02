<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RssSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'url', 'category', 'active', 'priority',
        'fetch_frequency', 'last_fetched_at', 'metadata',
        'target_category_id', 'keyword_filters', 'min_quality_score', 'auto_publish',
    ];

    protected $casts = [
        'active'          => 'boolean',
        'auto_publish'    => 'boolean',
        'metadata'        => 'array',
        'keyword_filters' => 'array',
        'last_fetched_at' => 'datetime',
    ];

    public function collectedArticles()
    {
        return $this->hasMany(CollectedArticle::class);
    }

    /**
     * Get the target category for auto-categorization.
     */
    public function targetCategory()
    {
        return $this->belongsTo(Category::class, 'target_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function needsFetch()
    {
        if (! $this->last_fetched_at) {
            return true;
        }

        return $this->last_fetched_at->addMinutes($this->fetch_frequency)->isPast();
    }
}
