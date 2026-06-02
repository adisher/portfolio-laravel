<?php

namespace Database\Seeders;

use App\Models\AiBudgetSetting;
use Illuminate\Database\Seeder;

class AiBudgetSettingsSeeder extends Seeder
{
    /**
     * Seed the AI budget settings.
     */
    public function run(): void
    {
        AiBudgetSetting::firstOrCreate([], [
            'monthly_budget_usd' => 1.00,
            'current_month_usage_usd' => 0.00,
            'total_tokens_used' => 0,
            'total_api_calls' => 0,
            'alert_threshold_50_sent' => false,
            'alert_threshold_80_sent' => false,
            'alert_threshold_100_sent' => false,
            'alert_email' => env('AI_BUDGET_ALERT_EMAIL', 'adilsher973@gmail.com'),
            'is_paused' => false,
            'last_reset_at' => now(),
        ]);

        $this->command->info('AI Budget settings initialized successfully!');
    }
}
