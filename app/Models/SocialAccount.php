<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A connected social destination. Credentials live in an encrypted cast, so
 * tokens are never stored in plaintext and never leak through array/JSON
 * serialisation of the model (the raw column is hidden).
 */
class SocialAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'platform', 'name', 'credentials', 'caption_template',
        'is_active', 'auto_post_enabled', 'min_human_views', 'last_posted_at',
    ];

    protected $casts = [
        'credentials'       => 'encrypted:array',
        'is_active'         => 'boolean',
        'auto_post_enabled' => 'boolean',
        'min_human_views'   => 'integer',
        'last_posted_at'    => 'datetime',
    ];

    // Never expose the encrypted blob when the model is serialised.
    protected $hidden = ['credentials'];

    public function socialPosts()
    {
        return $this->hasMany(SocialPost::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeAutoPosting($query)
    {
        return $query->where('is_active', true)->where('auto_post_enabled', true);
    }

    /** Read a single credential value (e.g. 'page_id', 'access_token'). */
    public function credential(string $key, $default = null)
    {
        return data_get($this->credentials, $key, $default);
    }

    /** The default caption template used when the account has none set. */
    public function captionTemplateOrDefault(): string
    {
        return $this->caption_template
            ?: "{title}\n\n{excerpt}\n\nRead more: {url}\n\n{hashtags}";
    }
}
