<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DemoBooking extends Model
{
    protected $fillable = [
        'project_id', 'name', 'email', 'company', 'plan_interest',
        'message', 'scheduled_at', 'duration_minutes', 'status',
        'cancellation_token', 'reminder_24h_sent_at', 'reminder_1h_sent_at',
        'follow_up_sent_at', 'admin_notes',
    ];

    protected $casts = [
        'scheduled_at'         => 'datetime',
        'reminder_24h_sent_at' => 'datetime',
        'reminder_1h_sent_at'  => 'datetime',
        'follow_up_sent_at'    => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $booking) {
            if (empty($booking->cancellation_token)) {
                $booking->cancellation_token = Str::random(64);
            }
        });
    }

    // ── Relations ────────────────────────────────────────────────────────────

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeUpcoming($query)
    {
        return $query->where('scheduled_at', '>', now())
                     ->where('status', 'confirmed')
                     ->orderBy('scheduled_at');
    }

    public function scopePast($query)
    {
        return $query->where('scheduled_at', '<', now())
                     ->whereIn('status', ['completed', 'no_show'])
                     ->orderByDesc('scheduled_at');
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled')
                     ->orderByDesc('scheduled_at');
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    public function getCancelUrl(): string
    {
        return route('demo.cancel', $this->cancellation_token);
    }

    /**
     * Scheduled_at formatted in the admin timezone (PKT by default).
     */
    public function scheduledAtFormatted(string $format = 'D, d M Y \a\t g:i A T'): string
    {
        $tz = DemoAvailability::settings()->timezone;
        return $this->scheduled_at->setTimezone($tz)->format($format);
    }

    public function isUpcoming(): bool
    {
        return $this->scheduled_at->isFuture() && $this->status === 'confirmed';
    }

    public function isPast(): bool
    {
        return $this->scheduled_at->isPast();
    }
}
