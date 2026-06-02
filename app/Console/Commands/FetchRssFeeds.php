<?php

namespace App\Console\Commands;

use App\Models\RssSource;
use App\Services\RssFeedService;
use Illuminate\Console\Command;

class FetchRssFeeds extends Command
{
    protected $signature = 'rss:fetch
        {--source= : Specific source ID to fetch}
        {--priority= : Comma-separated priority levels to fetch (e.g., 10 or 8,9,10)}
        {--force : Ignore fetch frequency limits}
        {--active-only : Only fetch from active sources}';

    protected $description = 'Fetch articles from RSS sources';

    public function handle(RssFeedService $rssService): int
    {
        $this->info('Starting RSS feed collection...');

        // Fetch specific source
        if ($sourceId = $this->option('source')) {
            return $this->fetchSingleSource($rssService, $sourceId);
        }

        // Fetch by priority
        if ($priorityOption = $this->option('priority')) {
            return $this->fetchByPriority($rssService, $priorityOption);
        }

        // Fetch all sources
        return $this->fetchAllSources($rssService);
    }

    /**
     * Fetch a single source by ID.
     */
    protected function fetchSingleSource(RssFeedService $rssService, int $sourceId): int
    {
        $source = RssSource::find($sourceId);

        if (!$source) {
            $this->error("RSS source with ID {$sourceId} not found.");
            return Command::FAILURE;
        }

        if (!$source->active && !$this->option('force')) {
            $this->warn("Source '{$source->name}' is inactive. Use --force to fetch anyway.");
            return Command::SUCCESS;
        }

        $collected = $rssService->fetchSource($source);
        $this->info("Collected {$collected} articles from {$source->name}");

        return Command::SUCCESS;
    }

    /**
     * Fetch sources by priority level(s).
     */
    protected function fetchByPriority(RssFeedService $rssService, string $priorityOption): int
    {
        $priorities = array_map('intval', explode(',', $priorityOption));
        $force = $this->option('force');

        $query = RssSource::whereIn('priority', $priorities);

        if ($this->option('active-only') || !$force) {
            $query->where('active', true);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info("No sources found with priority: {$priorityOption}");
            return Command::SUCCESS;
        }

        $this->info("Fetching {$sources->count()} sources with priority: {$priorityOption}");

        $totalCollected = 0;
        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();

        foreach ($sources as $source) {
            // Check if source needs fetching (unless forced)
            if (!$force && !$source->needsFetch()) {
                $bar->advance();
                continue;
            }

            try {
                $collected = $rssService->fetchSource($source);
                $totalCollected += $collected;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error fetching {$source->name}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Collected {$totalCollected} total articles from {$sources->count()} sources");

        return Command::SUCCESS;
    }

    /**
     * Fetch all active sources.
     */
    protected function fetchAllSources(RssFeedService $rssService): int
    {
        $force = $this->option('force');

        if ($force) {
            $this->info('Force mode: ignoring fetch frequency limits');
        }

        $query = RssSource::query();

        if ($this->option('active-only') || !$force) {
            $query->where('active', true);
        }

        $sources = $query->get();

        if ($sources->isEmpty()) {
            $this->info('No active RSS sources found.');
            return Command::SUCCESS;
        }

        $this->info("Processing {$sources->count()} sources...");

        $totalCollected = 0;
        $fetchedCount = 0;
        $skippedCount = 0;

        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();

        foreach ($sources as $source) {
            if (!$force && !$source->needsFetch()) {
                $skippedCount++;
                $bar->advance();
                continue;
            }

            try {
                $collected = $rssService->fetchSource($source);
                $totalCollected += $collected;
                $fetchedCount++;
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error fetching {$source->name}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Fetched: {$fetchedCount} sources");
        $this->info("Skipped: {$skippedCount} sources (not due for fetch)");
        $this->info("Collected: {$totalCollected} total articles");

        return Command::SUCCESS;
    }
}
