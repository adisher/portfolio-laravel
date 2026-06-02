<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'collected_article_id',
        'blog_post_id',
        'request_data',
        'response_data',
        'success',
        'error_message',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'cost_usd' => 'decimal:6',
        'success' => 'boolean',
    ];

    /**
     * Get the collected article associated with this log.
     */
    public function collectedArticle(): BelongsTo
    {
        return $this->belongsTo(CollectedArticle::class);
    }

    /**
     * Get the blog post associated with this log.
     */
    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class);
    }

    /**
     * Scope for successful API calls.
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope for failed API calls.
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    /**
     * Scope for a specific service.
     */
    public function scopeForService($query, string $service)
    {
        return $query->where('service', $service);
    }

    /**
     * Get total cost for current month.
     */
    public static function getCurrentMonthCost(): float
    {
        return static::currentMonth()->successful()->sum('cost_usd');
    }

    /**
     * Get total tokens used for current month.
     */
    public static function getCurrentMonthTokens(): array
    {
        $stats = static::currentMonth()->successful()->selectRaw('
            SUM(input_tokens) as total_input,
            SUM(output_tokens) as total_output
        ')->first();

        return [
            'input' => $stats->total_input ?? 0,
            'output' => $stats->total_output ?? 0,
            'total' => ($stats->total_input ?? 0) + ($stats->total_output ?? 0),
        ];
    }
}
