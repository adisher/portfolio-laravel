<?php

use App\Services\AiBudgetService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Blog Automation Scheduler
|--------------------------------------------------------------------------
*/

// Sitemap generation - daily at 2 AM
Schedule::command('sitemap:generate --save')
    ->dailyAt('02:00')
    ->onOneServer()
    ->runInBackground();

// High-priority RSS sources (priority 10) - every 15 minutes
Schedule::command('rss:fetch --priority=10 --active-only')
    ->everyFifteenMinutes()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping();

// Medium-priority RSS sources (priority 8-9) - every 30 minutes
Schedule::command('rss:fetch --priority=8,9 --active-only')
    ->everyThirtyMinutes()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping();

// Low-priority RSS sources (priority 1-7) - hourly
Schedule::command('rss:fetch --priority=1,2,3,4,5,6,7 --active-only')
    ->hourly()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping();

// Process articles - hourly (score, categorize, detect duplicates, auto-approve)
Schedule::command('articles:process --auto-approve --limit=200')
    ->hourly()
    ->onOneServer()
    ->runInBackground()
    ->withoutOverlapping();

// Auto-publish — once daily at 9 AM, publishes newest article per blog category.
// Per-category quota means a single run fills every category for the day.
Schedule::command('posts:auto-publish')
    ->dailyAt('09:00')
    ->onOneServer()
    ->runInBackground();

// Weekly cleanup - Sundays at 3 AM
Schedule::command('articles:cleanup --days=30 --include-duplicates')
    ->weeklyOn(0, '03:00')
    ->onOneServer()
    ->runInBackground();

// Monthly AI budget reset - 1st of each month at midnight
Schedule::call(function () {
    app(AiBudgetService::class)->resetMonthlyUsage();
})->monthlyOn(1, '00:00')
  ->name('ai-budget-monthly-reset')
  ->onOneServer();

// Generate llms.txt daily at 4 AM
Schedule::command('sitemap:generate --save')
    ->dailyAt('04:00')
    ->onOneServer()
    ->runInBackground();

/*
|--------------------------------------------------------------------------
| Demo Scheduling Automation
|--------------------------------------------------------------------------
*/

// 24h reminders — daily at 8 AM PKT
Schedule::command('demo:send-reminders --hours=24')
    ->dailyAt('08:00')
    ->timezone('Asia/Karachi')
    ->runInBackground()
    ->withoutOverlapping();

// 1h reminders — every 30 minutes
Schedule::command('demo:send-reminders --hours=1')
    ->everyThirtyMinutes()
    ->runInBackground()
    ->withoutOverlapping();

// Follow-up emails — hourly, 2h after demo
Schedule::command('demo:send-followups')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping();

// Mark no-shows — hourly, 30min grace period
Schedule::command('demo:mark-noshows')
    ->hourly()
    ->runInBackground()
    ->withoutOverlapping();

/*
|--------------------------------------------------------------------------
| Cricbuzz Sports Scheduler — T20 World Cup 2026
|--------------------------------------------------------------------------
*/

// Discover/update all series matches every 30 minutes
Schedule::command('sports:sync matches')
    ->everyThirtyMinutes()
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();

// Sync live match details every minute when live matches exist
Schedule::command('sports:sync live')
    ->everyMinute()
    ->when(function () {
        return \App\Models\SportMatch::where('status', 'live')->exists();
    })
    ->onOneServer()
    ->withoutOverlapping()
    ->runInBackground();
