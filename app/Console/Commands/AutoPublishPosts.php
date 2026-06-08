<?php

namespace App\Console\Commands;

use App\Services\AutoPublishService;
use Illuminate\Console\Command;

class AutoPublishPosts extends Command
{
    protected $signature = 'posts:auto-publish
        {--per-category= : Override posts published per category per day}
        {--dry-run : Preview what would be published without actually publishing}';

    protected $description = 'Auto-publish newest articles, balanced per blog category';

    public function handle(AutoPublishService $publishService): int
    {
        $dryRun      = $this->option('dry-run');
        $perCategory = $this->option('per-category') ? (int) $this->option('per-category') : null;

        if ($dryRun) {
            $this->info('DRY RUN - No posts will be published');
        }

        if ($perCategory !== null) {
            $this->info("Override: publishing up to {$perCategory} post(s) per category.");
        }

        $this->info('Starting auto-publish process...');

        $result = $publishService->run($dryRun, $perCategory);

        switch ($result['status']) {
            case 'disabled':
                $this->warn('Auto-publish is disabled. Enable it in settings.');
                return Command::SUCCESS;

            case 'no_articles':
                $this->info('No eligible articles to publish.');
                return Command::SUCCESS;

            case 'success':
                $this->info("Published: {$result['published']}");
                $this->info("Skipped: {$result['skipped']}");

                if ($result['errors'] > 0) {
                    $this->warn("Errors: {$result['errors']}");
                }

                if (!empty($result['posts'])) {
                    $this->newLine();
                    $this->info('Published Posts:');

                    $tableData = [];
                    foreach ($result['posts'] as $post) {
                        if ($dryRun) {
                            $tableData[] = [
                                $post['article_id'],
                                $post['title'],
                                $post['score'] ?? '-',
                                $post['category'] ?? '-',
                                'Would Publish',
                            ];
                        } else {
                            $tableData[] = [
                                $post['post_id'] ?? '-',
                                $post['title'],
                                $post['slug'] ?? '-',
                                $post['category'] ?? '-',
                                'Published',
                            ];
                        }
                    }

                    $this->table(
                        $dryRun
                            ? ['Article ID', 'Title', 'Score', 'Category', 'Status']
                            : ['Post ID', 'Title', 'Slug', 'Category', 'Status'],
                        $tableData
                    );
                }

                return Command::SUCCESS;

            default:
                $this->error('Unknown status: ' . ($result['status'] ?? 'null'));
                return Command::FAILURE;
        }
    }
}
