<?php

namespace App\Console\Commands;

use App\Models\SportMatch;
use App\Services\Sports\CricbuzzApiService;
use Illuminate\Console\Command;

class SyncSportsData extends Command
{
    protected $signature = 'sports:sync
        {type : Type of sync: matches, live, details}
        {--match= : Specific match ID for detail sync}
        {--force : Clear cache and force fresh data}';

    protected $description = 'Sync cricket data from the Cricbuzz scraper API';

    public function handle(CricbuzzApiService $api): int
    {
        $type = $this->argument('type');

        if ($this->option('force')) {
            $this->info('Force mode: clearing caches...');
            $api->clearCache();
        }

        return match ($type) {
            'matches' => $this->syncMatches($api),
            'live' => $this->syncLive($api),
            'details' => $this->syncDetails($api),
            default => $this->invalidType($type),
        };
    }

    protected function syncMatches(CricbuzzApiService $api): int
    {
        $this->info('Syncing all series matches from Cricbuzz...');
        $count = $api->syncSeriesMatches();
        $this->info("Synced {$count} matches.");

        return 0;
    }

    protected function syncLive(CricbuzzApiService $api): int
    {
        $this->info('Syncing live match scores...');
        $updated = $api->syncLiveScores();
        $this->info('Updated ' . count($updated) . ' live matches.');

        return 0;
    }

    protected function syncDetails(CricbuzzApiService $api): int
    {
        $matchId = $this->option('match');

        if ($matchId) {
            $match = SportMatch::find($matchId);
            if (!$match) {
                $this->error("Match not found: {$matchId}");
                return 1;
            }
            $this->info("Syncing details for match: {$match->title}");
            $api->syncMatchDetails($match);
            $this->info('Done.');
        } else {
            $this->info('Syncing details for all live matches...');
            $liveMatches = SportMatch::live()->get();
            foreach ($liveMatches as $match) {
                $this->line("  → {$match->title}");
                $api->syncMatchDetails($match);
            }
            $this->info("Synced details for {$liveMatches->count()} matches.");
        }

        return 0;
    }

    protected function invalidType(string $type): int
    {
        $this->error("Invalid sync type: {$type}. Use: matches, live, or details");
        return 1;
    }
}
