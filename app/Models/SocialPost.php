<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One publish attempt of an article to a specific social account.
 */
class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'blog_post_id', 'social_account_id', 'platform', 'status', 'trigger',
        'message', 'external_id', 'external_url', 'error', 'posted_at',
    ];

    protected $casts = [
        'posted_at' => 'datetime',
    ];

    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function socialAccount()
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function scopePosted($query)
    {
        return $query->where('status', 'posted');
    }
}
