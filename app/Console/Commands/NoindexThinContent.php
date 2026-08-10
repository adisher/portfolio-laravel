<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use App\Services\GscService;
use Illuminate\Console\Command;

class NoindexThinContent extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'blog:noindex-thin
                           {--apply : Actually write noindex=true instead of just listing candidates}
                           {--score= : Relevance-score threshold below which a curated post counts as thin (default 60)}
                           {--days=90 : GSC lookback window in days}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Noindex published curated posts that score below the quality threshold AND have zero GSC impressions (reversible, safe first step before unpublishing)';

    public function handle(GscService $gsc)
    {
        if (!$gsc->isConfigured()) {
            $this->error('GSC is not configured (services.gsc.site_url / credentials missing). Refusing to run — impressions can\'t be verified, so nothing would be a safe candidate.');
            return self::FAILURE;
        }

        $threshold = (float) ($this->option('score') ?? 60);
        $days = (int) $this->option('days');
        $apply = $this->option('apply');

        $metrics = $gsc->blogPageMetrics($days);

        $candidates = BlogPost::published()
            ->where('noindex', false)
            ->whereHas('collectedArticle', fn ($q) => $q->where('relevance_score', '<', $threshold))
            ->with('collectedArticle')
            ->get()
            ->filter(function (BlogPost $post) use ($metrics) {
                $impressions = $metrics[$post->slug]['impressions'] ?? 0;
                return $impressions === 0;
            });

        if ($candidates->isEmpty()) {
            $this->info('No candidates found: nothing is both below the score threshold and at zero GSC impressions.');
            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Slug', 'Score', 'Impressions'],
            $candidates->map(fn (BlogPost $post) => [
                $post->id,
                $post->slug,
                $post->collectedArticle->relevance_score ?? 'n/a',
                $metrics[$post->slug]['impressions'] ?? 0,
            ])
        );

        $this->info("{$candidates->count()} candidate(s) found (score < {$threshold}, 0 impressions over {$days}d).");

        if (!$apply) {
            $this->comment('Dry run — no changes made. Re-run with --apply to noindex these.');
            return self::SUCCESS;
        }

        foreach ($candidates as $post) {
            $post->update(['noindex' => true, 'noindex_at' => now()]);
        }

        $this->info("Noindexed {$candidates->count()} post(s). They stay live and readable — just excluded from the sitemap and marked noindex for crawlers.");

        return self::SUCCESS;
    }
}
