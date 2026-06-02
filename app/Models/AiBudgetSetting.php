<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiBudgetSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'monthly_budget_usd',
        'current_month_usage_usd',
        'budget_reset_day',
        'is_paused',
        'additional_budget_usd',
        'alert_email',
        'alert_at_50_percent',
        'alert_at_80_percent',
        'alert_at_100_percent',
        'last_50_alert_sent_at',
        'last_80_alert_sent_at',
        'last_100_alert_sent_at',
        'last_reset_at',
    ];

    protected $casts = [
        'monthly_budget_usd' => 'decimal:2',
        'current_month_usage_usd' => 'decimal:4',
        'additional_budget_usd' => 'decimal:2',
        'is_paused' => 'boolean',
        'alert_at_50_percent' => 'boolean',
        'alert_at_80_percent' => 'boolean',
        'alert_at_100_percent' => 'boolean',
        'last_50_alert_sent_at' => 'datetime',
        'last_80_alert_sent_at' => 'datetime',
        'last_reset_at' => 'datetime',
    ];

    /**
     * Get the singleton instance of budget settings.
     */
    public static function getInstance(): self
    {
        return static::firstOrCreate([], [
            'monthly_budget_usd' => 1.00,
            'current_month_usage_usd' => 0.00,
            'budget_reset_day' => 1,
            'is_paused' => false,
            'additional_budget_usd' => 0.00,
            'alert_email' => config('blog_automation.ai.budget_alert_email', 'adilsher973@gmail.com'),
        ]);
    }

    /**
     * Get total available budget (base + additional).
     */
    public function getTotalBudgetAttribute(): float
    {
        return (float) $this->monthly_budget_usd + (float) $this->additional_budget_usd;
    }

    /**
     * Get remaining budget.
     */
    public function getRemainingBudgetAttribute(): float
    {
        return max(0, $this->total_budget - (float) $this->current_month_usage_usd);
    }

    /**
     * Get usage percentage.
     */
    public function getUsagePercentAttribute(): float
    {
        if ($this->total_budget <= 0) {
            return 100;
        }

        return min(100, ((float) $this->current_month_usage_usd / $this->total_budget) * 100);
    }

    /**
     * Check if API calls can be made.
     */
    public function canMakeApiCall(): bool
    {
        return !$this->is_paused && $this->remaining_budget > 0;
    }

    /**
     * Add additional budget.
     */
    public function addBudget(float $amount = 1.00): void
    {
        $this->increment('additional_budget_usd', $amount);
        $this->update(['is_paused' => false]);
    }

    /**
     * Reset monthly usage.
     */
    public function resetMonthlyUsage(): void
    {
        $this->update([
            'current_month_usage_usd' => 0,
            'additional_budget_usd' => 0,
            'is_paused' => false,
            'last_50_alert_sent_at' => null,
            'last_80_alert_sent_at' => null,
            'last_100_alert_sent_at' => null,
            'last_reset_at' => now(),
        ]);
    }

    /**
     * Record usage.
     */
    public function recordUsage(float $cost): void
    {
        $this->increment('current_month_usage_usd', $cost);
    }

    /**
     * Get status label.
     */
    public function getStatusAttribute(): string
    {
        if ($this->is_paused) {
            return 'paused';
        }

        if ($this->usage_percent >= 100) {
            return 'exhausted';
        }

        if ($this->usage_percent >= 80) {
            return 'critical';
        }

        if ($this->usage_percent >= 50) {
            return 'warning';
        }

        return 'active';
    }

    /**
     * Get status color for UI.
     */
    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paused', 'exhausted' => 'red',
            'critical' => 'orange',
            'warning' => 'yellow',
            default => 'green',
        };
    }
}
