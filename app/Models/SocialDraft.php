<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * A piece of platform-native social content built from a blog post: a reel
 * today (script of scenes + per-platform captions + rendered video), carousels
 * later. Reviewed and approved per platform via its targets.
 */
class SocialDraft extends Model
{
    public const KIND_REEL = 'reel';

    public const STATUS_DRAFT = 'draft';             // script written, not rendered
    public const STATUS_RENDERING = 'rendering';     // render job running
    public const STATUS_READY = 'ready';             // rendered, waiting for review
    public const STATUS_RENDER_FAILED = 'render_failed';
    public const STATUS_REJECTED = 'rejected';

    /** Platforms a reel goes to, in display order. */
    public const REEL_PLATFORMS = ['instagram', 'youtube'];

    protected $fillable = [
        'blog_post_id', 'kind', 'status', 'script', 'captions',
        'video_path', 'cover_path', 'duration_seconds', 'error',
        'render_requested_at', 'rendered_at',
    ];

    protected $casts = [
        'script'              => 'array',
        'captions'            => 'array',
        'duration_seconds'    => 'integer',
        'render_requested_at' => 'datetime',
        'rendered_at'         => 'datetime',
    ];

    public function blogPost()
    {
        return $this->belongsTo(BlogPost::class);
    }

    public function targets()
    {
        return $this->hasMany(SocialDraftTarget::class);
    }

    public function target(string $platform): ?SocialDraftTarget
    {
        return $this->targets->firstWhere('platform', $platform);
    }

    /** Drafts that need the owner's attention in the admin. */
    public function scopeNeedsReview($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_READY, self::STATUS_RENDER_FAILED]);
    }

    /** @return array<int, array<string, mixed>> */
    public function scenes(): array
    {
        return $this->script['scenes'] ?? [];
    }

    /** The full voiceover, scene by scene, as it will be spoken. */
    public function voiceover(): string
    {
        return trim(collect($this->scenes())->pluck('voice_line')->filter()->implode(' '));
    }

    /** Planned length from the script, used until a render reports the real one. */
    public function plannedSeconds(): int
    {
        return (int) round(collect($this->scenes())->sum(fn ($s) => static::estimateSeconds($s)));
    }

    /** Spoken words across all scenes. */
    public function wordCount(): int
    {
        return static::countWords($this->voiceover());
    }

    public static function countWords(?string $text): int
    {
        return count(preg_split('/\s+/', trim((string) $text), -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * How long a scene runs: its spoken words at the configured pace plus a
     * short breath, with a floor so on-screen text (and code) can be read.
     * Mirrored in the review page's JavaScript; keep the two in step.
     */
    public static function estimateSeconds(array $scene): float
    {
        $words = static::countWords($scene['voice_line'] ?? '');
        $pace  = (float) config('social.reels.words_per_second', 2.5);
        $floor = ($scene['visual'] ?? null) === 'code' ? 4.0 : 2.0;

        return round(min(15, max($floor, $words / $pace + 0.4)), 1);
    }

    public function caption(string $platform, string $field, $default = null)
    {
        return data_get($this->captions, "{$platform}.{$field}", $default);
    }

    public function videoUrl(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
    }

    public function coverUrl(): ?string
    {
        return $this->cover_path ? Storage::disk('public')->url($this->cover_path) : null;
    }

    public function isRendered(): bool
    {
        return $this->video_path !== null && $this->rendered_at !== null;
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT         => 'Script ready, not rendered',
            self::STATUS_RENDERING     => 'Rendering',
            self::STATUS_READY         => 'Ready for review',
            self::STATUS_RENDER_FAILED => 'Render failed',
            self::STATUS_REJECTED      => 'Rejected',
            default                    => ucfirst(str_replace('_', ' ', $this->status)),
        };
    }
}
