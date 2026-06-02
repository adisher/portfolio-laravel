<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectedArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'rss_source_id', 'title', 'description', 'url', 'author',
        'published_at', 'content_data', 'relevance_score', 'status',
        'blog_post_id', 'curator_notes',
        'assigned_category_id', 'category_confidence', 'is_duplicate',
        'duplicate_of_id', 'ai_enhanced', 'ai_generated_content',
        'scheduled_publish_at', 'seo_data',
    ];

    protected $casts = [
        'content_data'       => 'array',
        'ai_generated_content' => 'array',
        'seo_data'           => 'array',
        'published_at'       => 'datetime',
        'scheduled_publish_at' => 'datetime',
        'relevance_score'    => 'decimal:2',
        'category_confidence' => 'decimal:2',
        'is_duplicate'       => 'boolean',
        'ai_enhanced'        => 'boolean',
    ];

    public function rssSource()
    {
        return $this->belongsTo(RssSource::class);
    }

    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }

    /**
     * Get the assigned category.
     */
    public function assignedCategory()
    {
        return $this->belongsTo(Category::class, 'assigned_category_id');
    }

    /**
     * Get the original article this is a duplicate of.
     */
    public function duplicateOf()
    {
        return $this->belongsTo(CollectedArticle::class, 'duplicate_of_id');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeHighScore($query, $threshold = 70)
    {
        return $query->where('relevance_score', '>=', $threshold);
    }
}
