<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ai_budget_settings', function (Blueprint $table) {
            $table->id();
            $table->decimal('monthly_budget_usd', 8, 2)->default(1.00);
            $table->decimal('current_month_usage_usd', 8, 4)->default(0.00);
            $table->integer('budget_reset_day')->default(1); // Day of month to reset
            $table->boolean('is_paused')->default(false); // Manual pause when limit hit
            $table->decimal('additional_budget_usd', 8, 2)->default(0.00); // Manual top-ups
            $table->string('alert_email')->nullable();
            $table->boolean('alert_at_50_percent')->default(true);
            $table->boolean('alert_at_80_percent')->default(true);
            $table->boolean('alert_at_100_percent')->default(true);
            $table->timestamp('last_50_alert_sent_at')->nullable();
            $table->timestamp('last_80_alert_sent_at')->nullable();
            $table->timestamp('last_100_alert_sent_at')->nullable();
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        DB::table('ai_budget_settings')->insert([
            'monthly_budget_usd' => 1.00,
            'current_month_usage_usd' => 0.00,
            'budget_reset_day' => 1,
            'is_paused' => false,
            'additional_budget_usd' => 0.00,
            'alert_email' => env('AI_BUDGET_ALERT_EMAIL', 'adilsher973@gmail.com'),
            'alert_at_50_percent' => true,
            'alert_at_80_percent' => true,
            'alert_at_100_percent' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_budget_settings');
    }
};
