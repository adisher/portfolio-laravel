<?php

namespace App\Console\Commands;

use App\Models\Visitor;
use App\Support\BotDetector;
use App\Support\TrafficSource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-time (re-runnable) backfill that makes existing analytics history match
 * the new classification rules:
 *   - re-runs bot detection over stored user-agents (catches AI crawlers the
 *     old isRobot()-only path let through as "human", e.g. Claude-Web),
 *   - flags the behavioural rotating-proxy scraper class that UA matching
 *     structurally cannot catch,
 *   - fills source / source_detail from stored referrer + utm.
 *
 * Dry-run by default: prints exactly what would change. Pass --apply to write.
 * Back up first (mysqldump the visitors table) — this mutates existing rows.
 */
class ReclassifyAnalytics extends Command
{
    protected $signature = 'analytics:reclassify
                            {--apply : Write changes (default is a dry-run report)}
                            {--scraper-min-visitors=100 : Min visitors sharing a UA to consider the scraper heuristic}';

    protected $description = 'Retroactively reclassify visitor bot flags and traffic sources';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING changes…' : 'DRY RUN (no writes). Pass --apply to persist.');
        $this->newLine();

        $botChanges    = $this->reclassifyBots($apply);
        $scraperCount  = $this->flagBehaviouralScrapers($apply);
        $sourceChanges = $this->backfillSources($apply);

        $this->newLine();
        $this->info('Summary');
        $this->table(['Change', 'Rows'], [
            ['Bot flag/reason updated (UA-based)', $botChanges],
            ['Newly flagged as scraper (behavioural)', $scraperCount],
            ['Source (re)classified', $sourceChanges],
        ]);

        if (!$apply) {
            $this->newLine();
            $this->warn('Dry run only. Re-run with --apply once the numbers look right.');
        }

        return self::SUCCESS;
    }

    /**
     * Re-run UA-based bot detection over every distinct user-agent, so the
     * updated crawler list is applied to existing rows. Efficient: one pass
     * per distinct UA, not per row.
     */
    private function reclassifyBots(bool $apply): int
    {
        $this->line('› Re-running bot detection over stored user-agents…');
        $changed = 0;

        Visitor::select('user_agent')
            ->distinct()
            ->orderBy('user_agent')
            ->pluck('user_agent')
            ->each(function ($ua) use ($apply, &$changed) {
                [$isBot, $reason] = BotDetector::fromUserAgent($ua);

                $q = Visitor::where('user_agent', $ua)
                    // Never touch behavioural-'scraper' rows — that pass owns them.
                    ->where(fn ($w) => $w->whereNull('bot_reason')->orWhere('bot_reason', '<>', 'scraper'))
                    // Only rows whose verdict genuinely differs from what's stored.
                    ->where(function ($w) use ($isBot, $reason) {
                        $w->where('is_bot', '<>', $isBot);
                        if ($reason === null) {
                            $w->orWhereNotNull('bot_reason');
                        } else {
                            $w->orWhereNull('bot_reason')->orWhere('bot_reason', '<>', $reason);
                        }
                    });

                $count = (clone $q)->count();
                if ($count === 0) {
                    return;
                }
                if ($apply) {
                    $q->update(['is_bot' => $isBot, 'bot_reason' => $reason]);
                }
                $changed += $count;
            });

        $this->line("  {$changed} row(s) " . ($apply ? 'updated' : 'would change') . '.');
        return $changed;
    }

    /**
     * Behavioural scraper detection. A rotating-proxy scraper spoofs one real
     * browser UA across thousands of distinct IPs, with ~every hit a single
     * page and no referrer. Real popular browsers share a UA too, but their
     * sessions are a MIX of multi-page and referred visits — so the combination
     * of (many distinct IPs) + (almost all single-page) + (almost all
     * no-referrer) is what isolates a scraper without catching real users.
     */
    private function flagBehaviouralScrapers(bool $apply): int
    {
        $this->line('› Scanning for behavioural scrapers (spoofed UA across many IPs)…');
        $min = (int) $this->option('scraper-min-visitors');

        // Aggregate per user-agent over rows not already flagged as bots.
        $rows = Visitor::query()
            ->whereNull('bot_reason')
            ->where('is_bot', false)
            ->select('user_agent')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('COUNT(DISTINCT ip_address) as ips')
            ->selectRaw('SUM(CASE WHEN page_views <= 1 THEN 1 ELSE 0 END) as single_page')
            ->selectRaw("SUM(CASE WHEN referrer IS NULL OR referrer = '' THEN 1 ELSE 0 END) as no_ref")
            ->groupBy('user_agent')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->get();

        $flagged = 0;
        foreach ($rows as $r) {
            $ipRatio     = $r->total > 0 ? $r->ips / $r->total : 0;
            $singleRatio = $r->total > 0 ? $r->single_page / $r->total : 0;
            $noRefRatio  = $r->total > 0 ? $r->no_ref / $r->total : 0;

            $isScraper = $ipRatio >= 0.7 && $singleRatio >= 0.9 && $noRefRatio >= 0.9;
            if (!$isScraper) {
                continue;
            }

            $this->line(sprintf(
                '  • %d hits / %d IPs (%.0f%% distinct, %.0f%% single-page, %.0f%% no-ref): %s',
                $r->total, $r->ips, $ipRatio * 100, $singleRatio * 100, $noRefRatio * 100,
                \Illuminate\Support\Str::limit($r->user_agent, 60)
            ));

            if ($apply) {
                Visitor::where('user_agent', $r->user_agent)
                    ->whereNull('bot_reason')
                    ->where('is_bot', false)
                    ->update(['is_bot' => true, 'bot_reason' => 'scraper']);
            }
            $flagged += $r->total;
        }

        $this->line("  {$flagged} row(s) " . ($apply ? 'flagged' : 'would be flagged') . '.');
        return $flagged;
    }

    /**
     * Fill source / source_detail for every visitor from stored referrer+utm.
     * Chunked so it scales to large tables.
     */
    private function backfillSources(bool $apply): int
    {
        $this->line('› Classifying traffic source for all visitors…');
        $changed = 0;

        Visitor::select('id', 'referrer', 'utm_source', 'source', 'source_detail')
            ->chunkById(500, function ($visitors) use ($apply, &$changed) {
                foreach ($visitors as $v) {
                    [$source, $detail] = TrafficSource::classify($v->referrer, $v->utm_source);
                    if ($v->source === $source && $v->source_detail === $detail) {
                        continue; // already correct
                    }
                    if ($apply) {
                        $v->forceFill(['source' => $source, 'source_detail' => $detail])->save();
                    }
                    $changed++;
                }
            });

        $this->line("  {$changed} row(s) " . ($apply ? 'updated' : 'would change') . '.');
        return $changed;
    }
}
