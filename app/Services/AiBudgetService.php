<?php

namespace App\Services;

use App\Models\AiBudgetSetting;
use App\Models\AiUsageLog;
use App\Mail\AiBudgetWarningMail;
use App\Mail\AiBudgetExhaustedMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AiBudgetService
{
    /**
     * Haiku pricing per million tokens.
     */
    protected float $inputPricePerMillion = 0.25;
    protected float $outputPricePerMillion = 0.50;

    /**
     * Get the budget settings instance.
     */
    public function getSettings(): AiBudgetSetting
    {
        return AiBudgetSetting::getInstance();
    }

    /**
     * Check if API calls can be made.
     */
    public function canMakeApiCall(): bool
    {
        $settings = $this->getSettings();
        return $settings->canMakeApiCall();
    }

    /**
     * Calculate cost for tokens.
     */
    public function calculateCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = ($inputTokens * $this->inputPricePerMillion) / 1_000_000;
        $outputCost = ($outputTokens * $this->outputPricePerMillion) / 1_000_000;

        return $inputCost + $outputCost;
    }

    /**
     * Log API usage and update budget.
     */
    public function logUsage(
        string $service,
        int $inputTokens,
        int $outputTokens,
        ?int $collectedArticleId = null,
        ?int $blogPostId = null,
        array $requestData = [],
        array $responseData = [],
        bool $success = true,
        ?string $errorMessage = null
    ): AiUsageLog {
        $cost = $this->calculateCost($inputTokens, $outputTokens);

        // Create usage log
        $log = AiUsageLog::create([
            'service' => $service,
            'model' => config('blog_automation.ai.model', 'claude-haiku-4-5-20251001'),
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'cost_usd' => $cost,
            'collected_article_id' => $collectedArticleId,
            'blog_post_id' => $blogPostId,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'success' => $success,
            'error_message' => $errorMessage,
        ]);

        // Update budget if successful
        if ($success) {
            $settings = $this->getSettings();
            $settings->recordUsage($cost);

            // Check and send alerts
            $this->checkBudgetAlerts($settings);
        }

        return $log;
    }

    /**
     * Check budget thresholds and send alerts.
     */
    public function checkBudgetAlerts(AiBudgetSetting $settings): void
    {
        $percentUsed = $settings->usage_percent;

        try {
            // 100% - Budget exhausted
            if ($percentUsed >= 100 && $settings->alert_at_100_percent) {
                if (!$settings->last_100_alert_sent_at || $settings->last_100_alert_sent_at->diffInHours(now()) >= 24) {
                    $settings->update(['is_paused' => true]);
                    $this->sendExhaustedAlert($settings);
                    $settings->update(['last_100_alert_sent_at' => now()]);
                }
            }
            // 80% - Critical warning
            elseif ($percentUsed >= 80 && $settings->alert_at_80_percent) {
                if (!$settings->last_80_alert_sent_at || $settings->last_80_alert_sent_at->diffInHours(now()) >= 24) {
                    $this->sendWarningAlert($settings, 80);
                    $settings->update(['last_80_alert_sent_at' => now()]);
                }
            }
            // 50% - Warning
            elseif ($percentUsed >= 50 && $settings->alert_at_50_percent) {
                if (!$settings->last_50_alert_sent_at || $settings->last_50_alert_sent_at->diffInHours(now()) >= 24) {
                    $this->sendWarningAlert($settings, 50);
                    $settings->update(['last_50_alert_sent_at' => now()]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to send AI budget alert: ' . $e->getMessage());
        }
    }

    /**
     * Send warning alert email.
     */
    protected function sendWarningAlert(AiBudgetSetting $settings, int $percent): void
    {
        if (!$settings->alert_email) {
            return;
        }

        Mail::to($settings->alert_email)->send(new AiBudgetWarningMail($settings, $percent));

        Log::info("AI Budget warning alert sent: {$percent}% used to {$settings->alert_email}");
    }

    /**
     * Send exhausted alert email.
     */
    protected function sendExhaustedAlert(AiBudgetSetting $settings): void
    {
        if (!$settings->alert_email) {
            return;
        }

        Mail::to($settings->alert_email)->send(new AiBudgetExhaustedMail($settings));

        Log::info("AI Budget exhausted alert sent to {$settings->alert_email}");
    }

    /**
     * Add additional budget.
     */
    public function addBudget(float $amount = 1.00): void
    {
        $settings = $this->getSettings();
        $settings->addBudget($amount);

        Log::info("Added \${$amount} to AI budget. New total: \${$settings->total_budget}");
    }

    /**
     * Reset monthly usage (called on 1st of each month).
     */
    public function resetMonthlyUsage(): void
    {
        $settings = $this->getSettings();
        $settings->resetMonthlyUsage();

        Log::info('AI budget reset for new month');
    }

    /**
     * Pause AI processing.
     */
    public function pause(): void
    {
        $settings = $this->getSettings();
        $settings->update(['is_paused' => true]);

        Log::info('AI processing paused');
    }

    /**
     * Resume AI processing.
     */
    public function resume(): void
    {
        $settings = $this->getSettings();

        // Only resume if there's budget remaining
        if ($settings->remaining_budget > 0) {
            $settings->update(['is_paused' => false]);
            Log::info('AI processing resumed');
        } else {
            Log::warning('Cannot resume AI processing: no budget remaining');
        }
    }

    /**
     * Get budget statistics.
     */
    public function getStats(): array
    {
        $settings = $this->getSettings();
        $tokens = AiUsageLog::getCurrentMonthTokens();

        return [
            'monthly_budget' => $settings->monthly_budget_usd,
            'additional_budget' => $settings->additional_budget_usd,
            'total_budget' => $settings->total_budget,
            'current_usage' => $settings->current_month_usage_usd,
            'remaining' => $settings->remaining_budget,
            'usage_percent' => $settings->usage_percent,
            'is_paused' => $settings->is_paused,
            'status' => $settings->status,
            'status_color' => $settings->status_color,
            'tokens' => $tokens,
            'api_calls_today' => AiUsageLog::whereDate('created_at', today())->count(),
            'api_calls_this_month' => AiUsageLog::currentMonth()->count(),
        ];
    }

    /**
     * Get usage logs for admin display.
     */
    public function getUsageLogs(int $limit = 50)
    {
        return AiUsageLog::with(['collectedArticle', 'blogPost'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Estimate cost for a content transformation.
     */
    public function estimateContentTransformCost(): float
    {
        // Average tokens for content transformation
        $avgInputTokens = 1500;
        $avgOutputTokens = 800;

        return $this->calculateCost($avgInputTokens, $avgOutputTokens);
    }

    /**
     * Check if there's enough budget for an operation.
     */
    public function hasEnoughBudget(float $estimatedCost): bool
    {
        $settings = $this->getSettings();
        return $settings->remaining_budget >= $estimatedCost;
    }
}
