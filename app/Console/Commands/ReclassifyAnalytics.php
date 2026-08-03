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
                            {--scraper-min-visitors=25 : Min visitors sharing a UA to consider the distributed-scraper heuristic}
                            {--flood-min-hits=25 : Min single-page no-referrer hits from one IP to flag an IP flood}';

    protected $description = 'Retroactively reclassify visitor bot flags and traffic sources';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'APPLYING changes…' : 'DRY RUN (no writes). Pass --apply to persist.');
        $this->newLine();

        $botChanges    = $this->reclassifyBots($apply);
        $scraperCount  = $this->flagBehaviouralScrapers($apply);
        $floodCount    = $this->flagSingleIpFloods($apply);
        $counterFixed  = $this->backfillPageViewCounter($apply);
        $sourceChanges = $this->backfillSources($apply);

        $this->newLine();
        $this->info('Summary');
        $this->table(['Change', 'Rows'], [
            ['Bot flag/reason updated (UA-based)', $botChanges],
            ['Flagged: distributed scraper (UA across many IPs)', $scraperCount],
            ['Flagged: single-IP flood', $floodCount],
            ['page_views counter corrected', $counterFixed],
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
        // single_page is computed from the ACTUAL page_views table (COUNT per
        // visitor), NOT the visitors.page_views counter — that counter is
        // off-by-one (inserted at DB-default 1, then the tracker increments it),
        // so a genuine single-page visit reads as 2. Counting real rows avoids
        // depending on that bug.
        $pvCounts = DB::raw('(SELECT visitor_id, COUNT(*) AS cnt FROM page_views GROUP BY visitor_id) pv');

        $rows = DB::table('visitors as v')
            ->leftJoin($pvCounts, 'pv.visitor_id', '=', 'v.id')
            ->whereNull('v.bot_reason')
            ->where('v.is_bot', false)
            ->groupBy('v.user_agent')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->get([
                DB::raw('v.user_agent as user_agent'),
                DB::raw('COUNT(*) as total'),
                DB::raw('COUNT(DISTINCT v.ip_address) as ips'),
                DB::raw('SUM(CASE WHEN COALESCE(pv.cnt, 0) <= 1 THEN 1 ELSE 0 END) as single_page'),
                DB::raw("SUM(CASE WHEN v.referrer IS NULL OR v.referrer = '' THEN 1 ELSE 0 END) as no_ref"),
            ]);

        $flagged = 0;
        foreach ($rows as $r) {
            $ipRatio     = $r->total > 0 ? $r->ips / $r->total : 0;
            $singleRatio = $r->total > 0 ? $r->single_page / $r->total : 0;
            $noRefRatio  = $r->total > 0 ? $r->no_ref / $r->total : 0;

            // Strict signature: near-100% distinct IPs AND near-100% single-page
            // AND near-100% no-referrer. Real people don't share a byte-identical
            // UA across dozens of distinct IPs with zero referrers, so even small
            // groups matching all three are almost certainly a scraper. The 0.9
            // IP-ratio (tightened from 0.7) keeps false positives near zero at the
            // lower visitor threshold.
            $isScraper = $ipRatio >= 0.9 && $singleRatio >= 0.9 && $noRefRatio >= 0.9;
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
     * Single-IP flood: the scraper heuristic's blind spot. One machine that
     * does NOT rotate IPs (an uptime monitor, a non-distributed scraper) pulls
     * many pages from a single address, each a fresh single-page session with
     * no referrer. Real users — even behind a shared/NAT IP — carry referrers
     * and browse multiple pages, so a high volume of single-page + no-referrer
     * hits from one IP is automated.
     */
    private function flagSingleIpFloods(bool $apply): int
    {
        $this->line('› Scanning for single-IP floods (one IP, many single-page no-ref hits)…');
        $min = (int) $this->option('flood-min-hits');

        $pvCounts = DB::raw('(SELECT visitor_id, COUNT(*) AS cnt FROM page_views GROUP BY visitor_id) pv');

        $rows = DB::table('visitors as v')
            ->leftJoin($pvCounts, 'pv.visitor_id', '=', 'v.id')
            ->whereNull('v.bot_reason')
            ->where('v.is_bot', false)
            ->whereNotNull('v.ip_address')
            ->groupBy('v.ip_address')
            ->havingRaw('COUNT(*) >= ?', [$min])
            ->get([
                DB::raw('v.ip_address as ip_address'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN COALESCE(pv.cnt, 0) <= 1 THEN 1 ELSE 0 END) as single_page'),
                DB::raw("SUM(CASE WHEN v.referrer IS NULL OR v.referrer = '' THEN 1 ELSE 0 END) as no_ref"),
            ]);

        $flagged = 0;
        foreach ($rows as $r) {
            $singleRatio = $r->total > 0 ? $r->single_page / $r->total : 0;
            $noRefRatio  = $r->total > 0 ? $r->no_ref / $r->total : 0;
            if ($singleRatio < 0.9 || $noRefRatio < 0.9) {
                continue;
            }

            $this->line(sprintf('  • %d single-page no-ref hits from %s', $r->total, $r->ip_address));

            if ($apply) {
                Visitor::where('ip_address', $r->ip_address)
                    ->whereNull('bot_reason')
                    ->where('is_bot', false)
                    ->update(['is_bot' => true, 'bot_reason' => 'ip_flood']);
            }
            $flagged += $r->total;
        }

        $this->line("  {$flagged} row(s) " . ($apply ? 'flagged' : 'would be flagged') . '.');
        return $flagged;
    }

    /**
     * Correct the visitors.page_views counter to the real number of page-view
     * rows, undoing the historical default(1)+increment off-by-one so bounce
     * rate (which counts page_views == 1) is accurate on existing data too.
     */
    private function backfillPageViewCounter(bool $apply): int
    {
        $this->line('› Correcting page_views counters to actual row counts…');

        // Rows whose stored counter disagrees with COUNT(page_views).
        $pvCounts = DB::raw('(SELECT visitor_id, COUNT(*) AS cnt FROM page_views GROUP BY visitor_id) pv');
        $mismatched = DB::table('visitors as v')
            ->leftJoin($pvCounts, 'pv.visitor_id', '=', 'v.id')
            ->whereRaw('v.page_views <> COALESCE(pv.cnt, 0)')
            ->count();

        if ($apply && $mismatched > 0) {
            // Single set-based UPDATE from the row-count subquery.
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql') {
                DB::statement('
                    UPDATE visitors v
                    LEFT JOIN (SELECT visitor_id, COUNT(*) AS cnt FROM page_views GROUP BY visitor_id) pv
                        ON pv.visitor_id = v.id
                    SET v.page_views = COALESCE(pv.cnt, 0)
                    WHERE v.page_views <> COALESCE(pv.cnt, 0)
                ');
            } else {
                // SQLite/Postgres correlated-subquery form.
                DB::statement('
                    UPDATE visitors
                    SET page_views = (SELECT COUNT(*) FROM page_views WHERE page_views.visitor_id = visitors.id)
                    WHERE page_views <> (SELECT COUNT(*) FROM page_views WHERE page_views.visitor_id = visitors.id)
                ');
            }
        }

        $this->line("  {$mismatched} counter(s) " . ($apply ? 'corrected' : 'would change') . '.');
        return $mismatched;
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
