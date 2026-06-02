<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'order_token',
        'customer_email',
        'customer_name',
        'tier_name',
        'amount',
        'currency',
        'safepay_tracker',
        'safepay_reference',
        'status',
        'paid_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'paid_at'  => 'datetime',
        'amount'   => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_token)) {
                $order->order_token = Str::random(64);
            }
        });
    }

    // ── Relationships ──────────────────────────────────────

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    // ── Scopes ─────────────────────────────────────────────

    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    // ── Status Helpers ───────────────────────────────────────

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['paid', 'failed', 'cancelled']);
    }

    public function markAsPaid(?string $reference = null): void
    {
        $previousStatus = $this->status;
        $this->update([
            'status'            => 'paid',
            'safepay_reference' => $reference,
            'paid_at'           => now(),
        ]);
        $this->logEvent('status_changed', [
            'from'      => $previousStatus,
            'to'        => 'paid',
            'reference' => $reference,
        ]);
    }

    public function markAsFailed(): void
    {
        $previousStatus = $this->status;
        $this->update(['status' => 'failed']);
        $this->logEvent('status_changed', [
            'from' => $previousStatus,
            'to'   => 'failed',
        ]);
    }

    public function markAsCancelled(): void
    {
        $previousStatus = $this->status;
        $this->update(['status' => 'cancelled']);
        $this->logEvent('status_changed', [
            'from' => $previousStatus,
            'to'   => 'cancelled',
        ]);
    }

    // ── Timeline / Audit Trail ──────────────────────────────

    /**
     * Append an event to the order's timeline (stored in metadata.timeline).
     *
     * Every significant action is logged here, creating a complete audit trail
     * that's visible in the admin panel for dispute resolution and debugging.
     */
    public function logEvent(string $event, array $details = []): void
    {
        $meta = $this->metadata ?? [];
        $timeline = $meta['timeline'] ?? [];

        $timeline[] = [
            'event'     => $event,
            'timestamp' => now()->toIso8601String(),
            'details'   => $details,
        ];

        $meta['timeline'] = $timeline;

        $this->update(['metadata' => $meta]);
    }

    /**
     * Get the order's timeline events.
     */
    public function getTimeline(): array
    {
        return $this->metadata['timeline'] ?? [];
    }

    // ── Access URL ──────────────────────────────────────────

    /**
     * Build the access URL for the product setup page with order token.
     */
    public function getAccessUrl(): string
    {
        $product = $this->project;

        // Find the setup page for this product
        $setupPage = $product->productPages()
            ->published()
            ->where('type', 'setup')
            ->first();

        if ($setupPage) {
            return route('products.page', [$product->slug, $setupPage->slug]) . '?token=' . $this->order_token;
        }

        // Fallback: link to product page
        return route('products.show', $product->slug);
    }
}
