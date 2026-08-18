<?php

namespace App\Console\Commands;

use App\Mail\BreakingNewsPublished;
use App\Models\CollectedArticle;
use App\Models\AutoPublishSetting;
use App\Services\ArticleScoringService;
use App\Services\AutoPublishService;
use App\Services\CategoryAssignmentService;
use App\Services\DuplicateDetectionService;
use App\Services\SignificanceDetector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
        DuplicateDetectionService $duplicateService,
        SignificanceDetector $significanceDetector,
        AutoPublishService $autoPublishService
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
            'breaking_published' => 0,
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

                // 3.5. Breaking-news fast path: a trigger term (acquisition,
                // funding, IPO...) and a notable entity (major company or
                // founder/exec) co-occurring in the same sentence skips the
                // normal score gate entirely and publishes immediately in
                // this run, instead of waiting on the daily batch or getting
                // stuck pending review like ordinary sub-threshold content.
                if ($autoApprove && $category) {
                    $significance = $significanceDetector->check($article);
                    if ($significance['significant'] ?? false) {
                        Log::info("Breaking-news auto-publish: article {$article->id} matched '{$significance['matched_trigger']}' + '{$significance['matched_entity']}' in: {$significance['matched_sentence']}");

                        $article->update(['status' => 'approved']);
                        $stats['approved']++;

                        try {
                            $post = $autoPublishService->publishSpecificArticle($article->fresh());
                            $stats['breaking_published']++;

                            if ($post) {
                                $post->update(['is_breaking_news' => true]);
                                $this->notifyBreakingNewsPublished($post, $significance);
                            }
                        } catch (\Exception $e) {
                            Log::error("Breaking-news publish failed for article {$article->id}: " . $e->getMessage());
                        }

                        $stats['processed']++;
                        $bar->advance();
                        continue;
                    }
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
                ['Breaking News Auto-Published', $stats['breaking_published']],
            ]
        );

        return Command::SUCCESS;
    }

    /**
     * Best-effort email so the fast-path stays visible instead of a post
     * just quietly appearing. Never allowed to fail the publish itself.
     */
    private function notifyBreakingNewsPublished($post, array $significance): void
    {
        try {
            // A dedicated alert address, NOT the admin User's login email
            // (that's a placeholder used only for authentication).
            $recipients = array_filter([config('blog_automation.significance.alert_email')]);
            if (empty($recipients) && $from = config('mail.from.address')) {
                $recipients = [$from];
            }
            if (empty($recipients)) {
                return;
            }

            Mail::to($recipients)->send(new BreakingNewsPublished(
                $post,
                $significance['matched_entity'],
                $significance['matched_trigger'],
                $significance['matched_sentence'],
            ));
        } catch (\Exception $e) {
            Log::warning("Breaking-news notification email failed for post {$post->id}: " . $e->getMessage());
        }
    }
}
