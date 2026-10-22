<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One platform a social draft may go to. Approved and scheduled on its own,
 * so each platform is reviewed independently.
 */
class SocialDraftTarget extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REJECTED = 'rejected';

    public const PLATFORM_LABELS = [
        'instagram' => 'Instagram Reels',
        'youtube'   => 'YouTube Shorts',
    ];

    protected $fillable = [
        'social_draft_id', 'platform', 'status', 'scheduled_for', 'approved_at',
        'posted_at', 'external_id', 'external_url', 'error',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'approved_at'   => 'datetime',
        'posted_at'     => 'datetime',
    ];

    public function draft()
    {
        return $this->belongsTo(SocialDraft::class, 'social_draft_id');
    }

    public function label(): string
    {
        return self::PLATFORM_LABELS[$this->platform] ?? ucfirst($this->platform);
    }
}
