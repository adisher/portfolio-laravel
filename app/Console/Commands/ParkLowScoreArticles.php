<?php

namespace App\Console\Commands;

use App\Models\CollectedArticle;
use Illuminate\Console\Command;

/**
 * Diverts approved-but-below-quality-bar articles into the reuse pool by
 * stamping `parked_at`. Parked articles are kept intact and exportable, but
 * never published to this blog. This runs both as a one-time backfill for the
 * existing ~13k pile and (scheduled daily) to catch the ~90/day the
 * auto-approver keeps waving through below the publish bar.
 *
 * Threshold defaults to the live auto-publish min_score, so parking and
 * publishing always agree: an article publishes, or it parks, never neither.
 *
 * Dry-run by default; --apply to write. Reversible: unpark with
 *   CollectedArticle::parked()->update(['parked_at' => null])
 * or export the pool with CollectedArticle::parked()->get().
 */
class ParkLowScoreArticles extends Command
{
    protected $signature = 'articles:park
                            {--apply : Write changes (default is a dry-run report)}
                            {--threshold= : Score below which approved articles are parked (default: live min_score)}';

    protected $description = 'Divert approved sub-threshold articles into the reuse pool (parked_at)';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $threshold = $this->option('threshold') !== null
            ? (float) $this->option('threshold')
            : (float) \App\Models\AutoPublishSetting::getInstance()->min_score_for_auto_publish;

        // Approved, below the publish bar, not already parked, and not already
        // turned into a blog post — those stay linked to their post.
        $query = CollectedArticle::where('status', 'approved')
            ->whereNull('parked_at')
            ->whereNull('blog_post_id')
            ->where('relevance_score', '<', $threshold);

        $count = (clone $query)->count();

        $this->info($apply ? 'APPLYING…' : 'DRY RUN (no writes). Pass --apply to persist.');
        $this->line("Threshold: articles scoring < {$threshold} that are approved & unpublished.");
        $this->line(($apply ? 'Parking ' : 'Would park ') . number_format($count) . ' article(s) into the reuse pool.');

        if ($apply && $count > 0) {
            $parked = 0;
            $query->toBase()->orderBy('id')->chunkById(1000, function ($rows) use (&$parked) {
                $ids = collect($rows)->pluck('id');
                $parked += CollectedArticle::whereIn('id', $ids)->update(['parked_at' => now()]);
            });
            $this->info("Parked {$parked} article(s).");
        }

        // Always show the resulting pool size so the effect is visible.
        $poolSize = CollectedArticle::parked()->count();
        $this->line('Reuse pool now holds ' . number_format($poolSize) . ' parked article(s).');

        if (!$apply) {
            $this->newLine();
            $this->warn('Dry run only. Re-run with --apply once the number looks right.');
        }

        return self::SUCCESS;
    }
}
