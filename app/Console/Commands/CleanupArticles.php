<?php

namespace App\Console\Commands;

use App\Models\CollectedArticle;
use App\Models\AiUsageLog;
use Illuminate\Console\Command;

class CleanupArticles extends Command
{
    protected $signature = 'articles:cleanup
        {--days=30 : Delete rejected articles older than this many days}
        {--dry-run : Show what would be deleted without actually deleting}
        {--include-duplicates : Also clean up duplicate articles}
        {--cleanup-logs : Clean up old AI usage logs}';

    protected $description = 'Clean up old rejected and duplicate articles';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $dryRun = $this->option('dry-run');
        $includeDuplicates = $this->option('include-duplicates');
        $cleanupLogs = $this->option('cleanup-logs');

        $cutoffDate = now()->subDays($days);

        if ($dryRun) {
            $this->info("DRY RUN - No data will be deleted");
        }

        $this->info("Cleaning up data older than {$days} days ({$cutoffDate->toDateString()})...");
        $this->newLine();

        $stats = [
            'rejected_articles' => 0,
            'duplicate_articles' => 0,
            'old_logs' => 0,
        ];

        // Clean up rejected articles
        $rejectedQuery = CollectedArticle::where('status', 'rejected')
            ->where('created_at', '<', $cutoffDate)
            ->whereNull('blog_post_id');

        $stats['rejected_articles'] = $rejectedQuery->count();

        if ($stats['rejected_articles'] > 0) {
            $this->info("Found {$stats['rejected_articles']} rejected articles to delete.");

            if (!$dryRun) {
                $rejectedQuery->delete();
                $this->info("Deleted {$stats['rejected_articles']} rejected articles.");
            }
        }

        // Clean up duplicate articles
        if ($includeDuplicates) {
            $duplicateQuery = CollectedArticle::where('is_duplicate', true)
                ->where('created_at', '<', $cutoffDate)
                ->whereNull('blog_post_id');

            $stats['duplicate_articles'] = $duplicateQuery->count();

            if ($stats['duplicate_articles'] > 0) {
                $this->info("Found {$stats['duplicate_articles']} duplicate articles to delete.");

                if (!$dryRun) {
                    $duplicateQuery->delete();
                    $this->info("Deleted {$stats['duplicate_articles']} duplicate articles.");
                }
            }
        }

        // Clean up old AI usage logs (keep 90 days of logs)
        if ($cleanupLogs) {
            $logsCutoff = now()->subDays(90);
            $logsQuery = AiUsageLog::where('created_at', '<', $logsCutoff);

            $stats['old_logs'] = $logsQuery->count();

            if ($stats['old_logs'] > 0) {
                $this->info("Found {$stats['old_logs']} old AI usage logs to delete.");

                if (!$dryRun) {
                    $logsQuery->delete();
                    $this->info("Deleted {$stats['old_logs']} old AI usage logs.");
                }
            }
        }

        $this->newLine();
        $this->info('Cleanup Summary:');
        $this->table(
            ['Type', 'Count', 'Status'],
            [
                ['Rejected Articles', $stats['rejected_articles'], $dryRun ? 'Would Delete' : 'Deleted'],
                ['Duplicate Articles', $stats['duplicate_articles'], $dryRun ? 'Would Delete' : 'Deleted'],
                ['Old AI Logs', $stats['old_logs'], $dryRun ? 'Would Delete' : 'Deleted'],
            ]
        );

        return Command::SUCCESS;
    }
}
