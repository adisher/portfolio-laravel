<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Models\CollectedArticle;
use App\Services\AiContentService;
use Illuminate\Console\Command;

class RegeneratePostContent extends Command
{
    protected $signature = 'posts:regenerate-content
                            {--post= : Specific post ID to regenerate}
                            {--all : Regenerate all posts with basic content}
                            {--dry-run : Show what would be regenerated without making changes}
                            {--force : Skip confirmation prompt}';

    protected $description = 'Regenerate blog post content using AI enhancement';

    public function __construct(
        protected AiContentService $aiContentService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if (!$this->aiContentService->isEnabled()) {
            $this->error('AI Content Service is not enabled. Check your API key and budget settings.');
            return Command::FAILURE;
        }

        $postId = $this->option('post');
        $all = $this->option('all');
        $dryRun = $this->option('dry-run');

        if (!$postId && !$all) {
            $this->error('Please specify --post=ID or --all');
            return Command::FAILURE;
        }

        // Query through CollectedArticle since it has the blog_post_id
        $query = CollectedArticle::query()
            ->whereNotNull('blog_post_id')
            ->with('blogPost');

        if ($postId) {
            $query->where('blog_post_id', $postId);
        } elseif ($all) {
            // Find articles whose linked blog posts have short content
            $query->whereHas('blogPost', function ($q) {
                $q->whereRaw("LENGTH(content) < 1000");
            });
        }

        $articles = $query->get();

        if ($articles->isEmpty()) {
            $this->info('No posts found to regenerate.');
            return Command::SUCCESS;
        }

        $this->info("Found {$articles->count()} post(s) to regenerate.");

        if ($dryRun) {
            $this->table(
                ['Post ID', 'Title', 'Content Length', 'AI Enhanced'],
                $articles->map(fn($a) => [
                    $a->blog_post_id,
                    \Str::limit($a->blogPost->title ?? $a->title, 50),
                    strlen($a->blogPost->content ?? ''),
                    $a->ai_enhanced ? 'Yes' : 'No',
                ])
            );
            $this->info('Dry run complete. No changes made.');
            return Command::SUCCESS;
        }

        $this->info('Estimated cost: ~$' . number_format($articles->count() * 0.002, 4) . ' (Haiku model)');

        if (!$this->option('force') && !$this->confirm('Proceed with regeneration?')) {
            $this->info('Cancelled.');
            return Command::SUCCESS;
        }

        $bar = $this->output->createProgressBar($articles->count());
        $bar->start();

        $success = 0;
        $failed = 0;

        foreach ($articles as $article) {
            $post = $article->blogPost;

            if (!$post) {
                $this->newLine();
                $this->warn("Article {$article->id}: No linked blog post found, skipping.");
                $failed++;
                $bar->advance();
                continue;
            }

            $aiContent = $this->aiContentService->transformArticle($article);

            if ($aiContent) {
                $content = $article->ai_generated_content['full_content']
                    ?? $article->ai_generated_content['content']
                    ?? null;

                if ($content) {
                    $post->update([
                        'content' => $content,
                        'excerpt' => $article->ai_generated_content['summary'] ?? $post->excerpt,
                    ]);
                    $success++;
                } else {
                    $this->newLine();
                    $this->warn("Post {$post->id}: AI content generated but no full_content found.");
                    $failed++;
                }
            } else {
                $this->newLine();
                $this->warn("Post {$post->id}: AI transformation failed.");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Regeneration complete: {$success} success, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
