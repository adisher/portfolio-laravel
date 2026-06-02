<?php

namespace App\Console\Commands;

use App\Models\CollectedArticle;
use App\Models\AutoPublishSetting;
use App\Services\ArticleScoringService;
use App\Services\CategoryAssignmentService;
use App\Services\DuplicateDetectionService;
use Illuminate\Console\Command;

class ProcessArticles extends Command
{
    protected $signature = 'articles:process
        {--auto-approve : Auto-approve articles that meet the score threshold}
        {--limit=100 : Maximum number of articles to process}
        {--force : Process all pending articles, not just new ones}';

    protected $description = 'Process collected articles: score, categorize, and detect duplicates';

    public function handle(
        ArticleScoringService $scoringService,
        CategoryAssignmentService $categoryService,
        DuplicateDetectionService $duplicateService
    ): int {
        $this->info('Processing articles...');

        $limit = (int) $this->option('limit');
        $autoApprove = $this->option('auto-approve');
        $force = $this->option('force');

        // Get articles to process
        $query = CollectedArticle::where('status', 'pending');

        if (!$force) {
            // Only process articles that haven't been scored yet
            $query->where(function ($q) {
                $q->whereNull('assigned_category_id')
                  ->orWhere('relevance_score', 0);
            });
        }

        $articles = $query->limit($limit)->get();

        if ($articles->isEmpty()) {
            $this->info('No articles to process.');
            return Command::SUCCESS;
        }

        $this->info("Found {$articles->count()} articles to process.");

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $stats = [
            'processed' => 0,
            'scored' => 0,
            'categorized' => 0,
            'duplicates' => 0,
            'approved' => 0,
            'rejected' => 0,
        ];

        $settings = AutoPublishSetting::getInstance();

        foreach ($articles as $article) {
            try {
                // 1. Check for duplicates
                $isDuplicate = $duplicateService->checkAndMark($article);
                if ($isDuplicate) {
                    $stats['duplicates']++;
                    $article->update(['status' => 'rejected']);
                    $stats['rejected']++;
                    $bar->advance();
                    continue;
                }

                // 2. Calculate relevance score
                $score = $scoringService->calculateScore($article);
                $article->update(['relevance_score' => $score]);
                $stats['scored']++;

                // 3. Assign category
                $category = $categoryService->assignCategory($article);
                if ($category) {
                    $stats['categorized']++;
                }

                // 4. Auto-approve if enabled and meets threshold
                if ($autoApprove) {
                    if ($settings->shouldAutoApprove($score)) {
                        $article->update(['status' => 'approved']);
                        $stats['approved']++;
                    } elseif ($settings->shouldReject($score)) {
                        $article->update(['status' => 'rejected']);
                        $stats['rejected']++;
                    }
                    // Otherwise, keep as pending for manual review
                }

                $stats['processed']++;

            } catch (\Exception $e) {
                $this->error("Error processing article {$article->id}: " . $e->getMessage());
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info('Processing complete!');
        $this->table(
            ['Metric', 'Count'],
            [
                ['Processed', $stats['processed']],
                ['Scored', $stats['scored']],
                ['Categorized', $stats['categorized']],
                ['Duplicates Found', $stats['duplicates']],
                ['Auto-Approved', $stats['approved']],
                ['Rejected', $stats['rejected']],
            ]
        );

        return Command::SUCCESS;
    }
}
