<?php

namespace App\Console\Commands;

use App\Models\BlogPost;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Re-derives both blog view counters from the PageView analytics, which are the
 * source of truth:
 *   views      = non-bot page views  (human)
 *   raw_views  = all page views      (bot-inclusive)
 *
 * Runs as a one-time backfill (fixing the old raw/bot-inclusive `views` values)
 * and daily thereafter, so the counters stay correct as the reclassify job
 * re-flags behavioural bots that a real-time UA check in show() can't catch.
 *
 * Dry-run by default; --apply to write.
 */
class RecountBlogViews extends Command
{
    protected $signature = 'blog:recount-views {--apply : Write changes (default is a dry-run summary)}';

    protected $description = 'Re-derive blog_posts.views (human) and raw_views (total) from PageView analytics';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING…' : 'DRY RUN (no writes). Pass --apply to persist.');

        // Per-post totals from the analytics: total loads and non-bot loads.
        $counts = DB::table('page_views as pv')
            ->join('visitors as v', 'pv.visitor_id', '=', 'v.id')
            ->where('pv.content_type', 'blog_post')
            ->whereNotNull('pv.content_id')
            ->groupBy('pv.content_id')
            ->select(
                'pv.content_id',
                DB::raw('COUNT(*) as raw_views'),
                DB::raw('SUM(CASE WHEN v.is_bot = 0 THEN 1 ELSE 0 END) as human_views')
            )
            ->get()
            ->keyBy('content_id');

        $changed = 0;
        $totalHuman = 0;
        $totalRaw = 0;

        BlogPost::select('id', 'views', 'raw_views')->chunkById(500, function ($posts) use ($counts, $apply, &$changed, &$totalHuman, &$totalRaw) {
            foreach ($posts as $post) {
                $row = $counts[$post->id] ?? null;
                $human = (int) ($row->human_views ?? 0);
                $raw   = (int) ($row->raw_views ?? 0);
                $totalHuman += $human;
                $totalRaw   += $raw;

                if ((int) $post->views === $human && (int) $post->raw_views === $raw) {
                    continue;
                }
                if ($apply) {
                    BlogPost::whereKey($post->id)->update(['views' => $human, 'raw_views' => $raw]);
                }
                $changed++;
            }
        });

        $this->line(($apply ? 'Updated ' : 'Would update ') . number_format($changed) . ' post(s).');
        $this->line('Totals from analytics — human: ' . number_format($totalHuman) . ', raw: ' . number_format($totalRaw)
            . ($totalRaw > 0 ? ' (' . round($totalHuman / $totalRaw * 100) . '% human)' : ''));

        if (!$apply) {
            $this->warn('Dry run only. Re-run with --apply to persist.');
        }

        return self::SUCCESS;
    }
}
