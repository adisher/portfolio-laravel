<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Visitor;
use App\Models\PageView;
use App\Models\ContactAnalytic;
use App\Models\Project;
use App\Models\BlogPost;
use App\Services\GscService;
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