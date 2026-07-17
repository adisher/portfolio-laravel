<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

/**
 * Usage log for non-Claude external tools (Pexels, IndexNow, GSC, Safepay, ...).
 * Claude usage lives in AiUsageLog; this covers everything else.
 */
class ToolUsageLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tool', 'action', 'quantity', 'unit', 'cost_usd', 'success', 'meta',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'cost_usd' => 'decimal:6',
        'success'  => 'boolean',
        'meta'     => 'array',
    ];

    /**
     * Record a tool call. Never throws: usage logging must not break the caller.
     */
    public static function record(
        string $tool,
        ?string $action = null,
        int $quantity = 1,
        ?string $unit = null,
        bool $success = true,
        ?float $costUsd = null,
        array $meta = []
    ): void {
        try {
            static::create([
                'tool'     => $tool,
                'action'   => $action,
                'quantity' => max(0, $quantity),
                'unit'     => $unit,
                'cost_usd' => $costUsd,
                'success'  => $success,
                'meta'     => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::warning("ToolUsageLog::record failed for {$tool}: " . $e->getMessage());
        }
    }

    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                     ->whereYear('created_at', now()->year);
    }

    public function scopeForTool($query, string $tool)
    {
        return $query->where('tool', $tool);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }
}
