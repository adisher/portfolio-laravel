<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Models\PageView;
use App\Models\ContactAnalytic;
use App\Models\Project;
use App\Models\BlogPost;
use App\Services\GscService;
use App\Support\TrafficSource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $days = (int) $period;

        // Get overview stats
        $stats = $this->getOverviewStats($days);
        
        // Get traffic trends
        $trafficTrends = $this->getTrafficTrends($days);
        
        // Get popular content
        $popularPages = PageView::getPopularPages(10, $days);
        $popularProjects = $this->getPopularProjects($days);
        $popularBlogPosts = $this->getPopularBlogPosts($days);
        
        // Get visitor insights
        $deviceBreakdown = Visitor::getDeviceBreakdown();
        $topCountries = Visitor::getTopCountries(10);
        $trafficSources = Visitor::getTrafficSources();
        $browserStats = Visitor::getBrowserBreakdown();

        // Country breakdown (first-party visits)
        $countryBreakdown = PageView::getCountryBreakdown($days);
        $countryByPage = PageView::getCountryByPage($days);
        
        // Get contact analytics
        $contactStats = $this->getContactStats($days);
        $contactSources = ContactAnalytic::getContactSources($days);
        $contactTrends = ContactAnalytic::getContactTrends($days);
        
        // Get performance metrics
        $performanceStats = $this->getPerformanceStats($days);

        return view('admin.analytics.index', compact(
            'stats',
            'trafficTrends',
            'popularPages',
            'popularProjects',
            'popularBlogPosts',
            'deviceBreakdown',
            'topCountries',
            'trafficSources',
            'browserStats',
            'contactStats',
            'contactSources',
            'contactTrends',
            'performanceStats',
            'countryBreakdown',
            'countryByPage',
            'days'
        ));
    }

    /**
     * Google Search Console search performance.
     */
    public function search(Request $request, GscService $gsc)
    {
        $days = (int) $request->get('period', 28);

        if (!$gsc->isConfigured()) {
            return view('admin.analytics.search', [
                'configured' => false,
                'days'       => $days,
            ]);
        }

        return view('admin.analytics.search', [
            'configured' => true,
            'days'       => $days,
            'summary'    => $gsc->summary($days),
            'trend'      => $gsc->trend($days),
            'topQueries' => $gsc->topQueries($days),
            'topPages'   => $gsc->topPages($days),
            'byCountry'  => $gsc->byCountry($days),
        ]);
    }

    /**
     * Blog performance report: rolls up per-article search + on-site metrics
     * to niche (category) and to source_type (curated vs original).
     *
     * The join is the whole point — GSC is keyed by URL, first-party views by
     * content_id, and the axes the user actually cares about (niche, curated
     * vs original) live on blog_posts. Nothing in the stock dashboard rolls up
     * to those axes, so we do it here.
     */
    public function blogReport(Request $request, GscService $gsc)
    {
        $days = (int) $request->get('period', 28);

        // ── On-site views per published post (first-party) ──────────────
        // Keyed by post id: count of non-bot page views in window.
        $viewsByPost = PageView::notBot()
            ->where('content_type', 'blog_post')
            ->whereNotNull('content_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->select('content_id', DB::raw('count(*) as views'))
            ->groupBy('content_id')
            ->pluck('views', 'content_id');

        // ── All published posts with their category ─────────────────────
        $posts = BlogPost::published()
            ->with('category:id,name,slug')
            ->get(['id', 'title', 'slug', 'category_id', 'source_type', 'published_at']);

        // ── Search metrics per blog slug (GSC), if connected ────────────
        $gscConfigured = $gsc->isConfigured();
        $searchBySlug = $gscConfigured ? $gsc->blogPageMetrics($days) : [];

        // ── Assemble a per-article row joining all three sources ────────
        $matchedSlugs = [];
        $articles = $posts->map(function ($post) use ($viewsByPost, $searchBySlug, &$matchedSlugs) {
            $search = $searchBySlug[$post->slug] ?? null;
            if ($search) {
                $matchedSlugs[$post->slug] = true;
            }
            return [
                'id'          => $post->id,
                'title'       => $post->title,
                'slug'        => $post->slug,
                'category'    => $post->category?->name ?? 'Uncategorized',
                'category_id' => $post->category_id,
                'source_type' => $post->source_type ?: 'unknown',
                'age_days'    => $post->published_at ? (int) $post->published_at->diffInDays(now()) : null,
                'views'       => (int) ($viewsByPost[$post->id] ?? 0),
                'impressions' => (int) ($search['impressions'] ?? 0),
                'clicks'      => (int) ($search['clicks'] ?? 0),
                'position'    => $search['position'] ?? null,
            ];
        });

        // GSC blog URLs that matched no current post (renamed/deleted slugs) —
        // surfaced so the totals are transparent rather than silently dropped.
        $unmatched = collect($searchBySlug)
            ->reject(fn($_, $slug) => isset($matchedSlugs[$slug]))
            ->values()
            ->all();

        return view('admin.analytics.blog-report', array_merge([
            'days'          => $days,
            'gscConfigured' => $gscConfigured,
            'byNiche'       => $this->rollup($articles, 'category'),
            'bySource'      => $this->rollup($articles, 'source_type'),
            'topArticles'   => $articles->sortByDesc('impressions')->take(15)->values(),
            'opportunities' => $this->opportunityArticles($articles),
            'totals'        => $this->blogTotals($articles),
            'unmatchedCount' => count($unmatched),
            'unmatchedImpr'  => array_sum(array_column($unmatched, 'impressions')),
        ], $this->blogAudience($days)));
    }

    /**
     * Who actually reaches the blog: the human traffic-source split (incl. AI
     * assistants), the AI-crawler activity that precedes citations, and the
     * bot-vs-human view split — so one scraper can never again masquerade as
     * real readership. All keyed off blog_post page-views in the window.
     */
    private function blogAudience(int $days)
    {
        $base = fn () => PageView::query()
            ->join('visitors', 'page_views.visitor_id', '=', 'visitors.id')
            ->where('page_views.content_type', 'blog_post')
            ->where('page_views.created_at', '>=', now()->subDays($days));

        // Human views split by acquisition source (persisted `source` column).
        $sourceRows = (clone $base())->where('visitors.is_bot', false)
            ->select('visitors.source', DB::raw('count(*) as c'))
            ->groupBy('visitors.source')->pluck('c', 'source');

        $sourceBreakdown = collect([
            TrafficSource::AI, TrafficSource::SEARCH, TrafficSource::REFERRAL,
            TrafficSource::SOCIAL, TrafficSource::DIRECT,
        ])->map(fn ($s) => [
            'source' => $s,
            'label'  => TrafficSource::label($s),
            'views'  => (int) ($sourceRows[$s] ?? 0),
        ])->filter(fn ($r) => $r['views'] > 0)->sortByDesc('views')->values();

        // Which specific AI assistants sent human readers.
        $aiAssistants = (clone $base())->where('visitors.is_bot', false)
            ->where('visitors.source', TrafficSource::AI)
            ->select('visitors.source_detail', DB::raw('count(*) as c'))
            ->groupBy('visitors.source_detail')->orderByDesc('c')->pluck('c', 'source_detail');

        // AI-crawler activity: bots reading the blog, named. Crawler name is
        // derived from the stored UA (few distinct bot UAs, so this is cheap).
        $crawlerRows = (clone $base())->where('visitors.bot_reason', 'ai_crawler')
            ->select('visitors.user_agent', DB::raw('count(*) as c'))
            ->groupBy('visitors.user_agent')->get();

        $aiCrawlers = $crawlerRows
            ->groupBy(fn ($r) => \App\Support\BotDetector::crawlerName($r->user_agent) ?? 'Other AI crawler')
            ->map(fn ($g) => $g->sum('c'))
            ->sortDesc();

        // Bot-vs-human view split (transparency: bot views are excluded from
        // every other number on the page, this shows how much was excluded).
        $humanViews = (int) (clone $base())->where('visitors.is_bot', false)->count();
        $botViews   = (int) (clone $base())->where('visitors.is_bot', true)->count();

        return [
            'sourceBreakdown' => $sourceBreakdown,
            'aiAssistants'    => $aiAssistants,
            'aiCrawlers'      => $aiCrawlers,
            'humanViews'      => $humanViews,
            'botViews'        => $botViews,
        ];
    }

    /**
     * Group article rows by a key and aggregate. Average position is
     * impression-weighted (an unweighted mean of positions is meaningless —
     * a page with 2 impressions would count as much as one with 2,000).
     */
    private function rollup($articles, string $key)
    {
        return $articles->groupBy($key)->map(function ($group, $label) {
            $impr   = $group->sum('impressions');
            $clicks = $group->sum('clicks');

            // Impression-weighted average position, over rows that have a position.
            $withPos = $group->filter(fn($a) => $a['position'] !== null && $a['impressions'] > 0);
            $weightedPos = $withPos->sum('impressions') > 0
                ? $withPos->sum(fn($a) => $a['position'] * $a['impressions']) / $withPos->sum('impressions')
                : null;

            $count = $group->count();

            return [
                'label'         => $label ?: 'Uncategorized',
                'articles'      => $count,
                'views'         => $group->sum('views'),
                'impressions'   => $impr,
                'clicks'        => $clicks,
                'ctr'           => $impr > 0 ? $clicks / $impr : 0,
                'avg_position'  => $weightedPos,
                // Per-article normalisation: the honest way to compare a niche
                // with 40 posts against one with 5.
                'impr_per_post' => $count > 0 ? round($impr / $count) : 0,
                'views_per_post' => $count > 0 ? round($group->sum('views') / $count) : 0,
            ];
        })->sortByDesc('impressions')->values();
    }

    /**
     * Actionable articles: ranking on page 1-2 (position 4-20) with real
     * impression volume but a weak click-through — i.e. a title/meta tweak or
     * a small ranking push is the cheapest available win.
     */
    private function opportunityArticles($articles)
    {
        return $articles
            ->filter(fn($a) =>
                $a['impressions'] >= 30
                && $a['position'] !== null
                && $a['position'] >= 4
                && $a['position'] <= 20
            )
            ->map(function ($a) {
                // CTR is computed here (not on the base row) so the view can
                // read $a['ctr'] and we can sort worst-CTR-first below.
                $a['ctr'] = $a['impressions'] > 0 ? $a['clicks'] / $a['impressions'] : 0;
                return $a;
            })
            // Biggest impression pools with the worst CTR first.
            ->sortBy(fn($a) => [$a['ctr'], -$a['impressions']])
            ->take(15)
            ->values();
    }

    private function blogTotals($articles)
    {
        $impr   = $articles->sum('impressions');
        $clicks = $articles->sum('clicks');
        return [
            'articles'    => $articles->count(),
            'views'       => $articles->sum('views'),
            'impressions' => $impr,
            'clicks'      => $clicks,
            'ctr'         => $impr > 0 ? $clicks / $impr : 0,
        ];
    }

    public function realtime()
    {
        $activeVisitors = $this->getActiveVisitors();
        $todayStats = Visitor::getTodayStats();
        $recentPageViews = $this->getRecentPageViews();
        $hourlyTraffic = PageView::getHourlyTraffic();

        return view('admin.analytics.realtime', compact(
            'activeVisitors',
            'todayStats',
            'recentPageViews',
            'hourlyTraffic'
        ));
    }

    public function export(Request $request)
    {
        $period = $request->get('period', '30');
        $type = $request->get('type', 'visitors');

        $filename = "analytics-{$type}-" . now()->format('Y-m-d') . ".csv";
        
        return response()->streamDownload(function () use ($type, $period) {
            $this->generateCSV($type, (int) $period);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function getOverviewStats($days)
    {
        $current = [
            'visitors' => Visitor::notBot()->lastDays($days)->count(),
            'page_views' => PageView::notBot()->lastDays($days)->count(),
            'avg_session_duration' => Visitor::notBot()->lastDays($days)->avg('session_duration') ?: 0,
            'bounce_rate' => Visitor::getBounceRate(),
            'contacts' => ContactAnalytic::lastDays($days)->count(),
            'conversion_rate' => ContactAnalytic::getConversionRate($days),
        ];

        // Get previous period for comparison
        $previous = [
            'visitors' => Visitor::notBot()->whereBetween('created_at', [
                now()->subDays($days * 2), now()->subDays($days)
            ])->count(),
            'page_views' => PageView::notBot()->whereBetween('created_at', [
                now()->subDays($days * 2), now()->subDays($days)
            ])->count(),
            'contacts' => ContactAnalytic::whereBetween('created_at', [
                now()->subDays($days * 2), now()->subDays($days)
            ])->count(),
        ];

        // Calculate percentage changes
        $changes = [];
        foreach (['visitors', 'page_views', 'contacts'] as $metric) {
            if ($previous[$metric] > 0) {
                $changes[$metric] = round((($current[$metric] - $previous[$metric]) / $previous[$metric]) * 100, 1);
            } else {
                $changes[$metric] = $current[$metric] > 0 ? 100 : 0;
            }
        }

        return [
            'current' => $current,
            'previous' => $previous,
            'changes' => $changes,
        ];
    }

    private function getTrafficTrends($days)
    {
        return PageView::getTrafficTrends($days)->map(function ($item) {
            return [
                'date' => $item->date,
                'visitors' => $item->unique_visitors,
                'page_views' => $item->page_views,
            ];
        });
    }

    private function getPopularProjects($days)
    {
        $projectViews = PageView::getPopularContent('project', 10, $days);
        
        return $projectViews->map(function ($view) {
            $project = Project::find($view->content_id);
            return [
                'id' => $view->content_id,
                'title' => $project ? $project->title : $view->page_title,
                'slug' => $project ? $project->slug : null,
                'views' => $view->views,
                'url' => $project ? route('portfolio.show', $project->slug) : null,
            ];
        });
    }

    private function getPopularBlogPosts($days)
    {
        $postViews = PageView::getPopularContent('blog_post', 10, $days);
        
        return $postViews->map(function ($view) {
            $post = BlogPost::find($view->content_id);
            return [
                'id' => $view->content_id,
                'title' => $post ? $post->title : $view->page_title,
                'slug' => $post ? $post->slug : null,
                'views' => $view->views,
                'url' => $post ? route('blog.show', $post->slug) : null,
            ];
        });
    }

    private function getContactStats($days)
    {
        return [
            'total_contacts' => ContactAnalytic::lastDays($days)->count(),
            'conversion_rate' => ContactAnalytic::getConversionRate($days),
            'avg_pages_before_contact' => round(ContactAnalytic::getAveragePageViewsBeforeContact($days), 1),
            'popular_projects_before_contact' => ContactAnalytic::getPopularProjectsBeforeContact($days),
        ];
    }

    private function getPerformanceStats($days)
    {
        return [
            'avg_load_time' => round(PageView::getAverageLoadTime($days) ?: 0, 0),
            'slowest_pages' => PageView::getSlowestPages(5, $days),
            'total_page_views' => PageView::notBot()->lastDays($days)->count(),
            'unique_pages' => PageView::notBot()->lastDays($days)->distinct('url')->count(),
        ];
    }

    private function getActiveVisitors()
    {
        // Visitors active in the last 5 minutes
        return Visitor::notBot()
            ->where('last_activity_at', '>=', now()->subMinutes(5))
            ->count();
    }

    private function getRecentPageViews($limit = 20)
    {
        return PageView::notBot()
            ->with('visitor')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($pageView) {
                return [
                    'url' => $pageView->url,
                    'title' => $pageView->page_title ?: 'Untitled',
                    'time' => $pageView->created_at->diffForHumans(),
                    'country' => $pageView->visitor->country,
                    'device' => $pageView->visitor->device_type,
                    'browser' => $pageView->visitor->browser,
                ];
            });
    }

    private function generateCSV($type, $days)
    {
        $handle = fopen('php://output', 'w');
        
        switch ($type) {
            case 'visitors':
                fputcsv($handle, ['Date', 'Visitors', 'Page Views', 'Bounce Rate']);
                $data = PageView::getTrafficTrends($days);
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row->date,
                        $row->unique_visitors,
                        $row->page_views,
                        Visitor::getBounceRate(),
                    ]);
                }
                break;
                
            case 'pages':
                fputcsv($handle, ['URL', 'Title', 'Views', 'Type']);
                $data = PageView::getPopularPages(100, $days);
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row->url,
                        $row->page_title,
                        $row->views,
                        $row->page_type,
                    ]);
                }
                break;
                
            case 'contacts':
                fputcsv($handle, ['Date', 'Contacts', 'Source Page', 'UTM Source']);
                $data = ContactAnalytic::lastDays($days)
                    ->with('contact')
                    ->orderBy('created_at', 'desc')
                    ->get();
                foreach ($data as $row) {
                    fputcsv($handle, [
                        $row->created_at->format('Y-m-d'),
                        $row->contact->name ?? 'N/A',
                        $row->source_page ?? 'N/A',
                        $row->utm_source ?? 'Direct',
                    ]);
                }
                break;
        }
        
        fclose($handle);
    }
}