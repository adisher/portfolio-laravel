<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiBudgetSetting;
use App\Models\AiUsageLog;
use App\Models\AutoPublishSetting;
use App\Models\BlogPost;
use App\Models\CollectedArticle;
use App\Models\Contact;
use App\Models\Project;
use App\Models\RssSource;
use App\Models\SportMatch;
use App\Services\AutoPublishService;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_projects'     => Project::count(),
            'published_projects' => Project::published()->count(),
            'total_posts'        => BlogPost::count(),
            'published_posts'    => BlogPost::published()->count(),
            'unread_contacts'    => Contact::unread()->count(),
            'total_contacts'     => Contact::count(),
        ];

        $recentProjects = Project::latest()->take(5)->get();
        $recentPosts    = BlogPost::latest()->take(5)->get();
        $recentContacts = Contact::latest()->take(5)->get();

        // Content Pipeline Stats
        $pipelineStats = $this->getPipelineStats();

        // AI Budget Stats
        $budgetStats = $this->getBudgetStats();

        // Sports Stats
        $sportsStats = $this->getSportsStats();

        return view('admin.dashboard', compact(
            'stats',
            'recentProjects',
            'recentPosts',
            'recentContacts',
            'pipelineStats',
            'budgetStats',
            'sportsStats'
        ));
    }

    /**
     * Get content pipeline statistics.
     */
    protected function getPipelineStats(): array
    {
        $autoPublishSettings = AutoPublishSetting::getInstance();

        return [
            'active_sources' => RssSource::where('active', true)->count(),
            'total_sources' => RssSource::count(),
            'articles_today' => CollectedArticle::whereDate('created_at', today())->count(),
            'pending_review' => CollectedArticle::where('status', 'pending')->count(),
            'approved' => CollectedArticle::where('status', 'approved')->whereNull('blog_post_id')->count(),
            'published_today' => $autoPublishSettings->posts_published_today,
            'remaining_today' => $autoPublishSettings->remaining_posts,
            'max_per_day' => $autoPublishSettings->max_posts_per_day,
            'auto_publish_enabled' => $autoPublishSettings->enabled,
            'high_score_ready' => CollectedArticle::where('status', 'approved')
                ->where('relevance_score', '>=', $autoPublishSettings->min_score_for_auto_publish)
                ->where('is_duplicate', false)
                ->whereNull('blog_post_id')
                ->count(),
        ];
    }

    /**
     * Get sports statistics.
     */
    protected function getSportsStats(): array
    {
        return [
            'live_matches' => SportMatch::live()->count(),
            'today_matches' => SportMatch::today()->count(),
            'upcoming_matches' => SportMatch::upcoming()->count(),
        ];
    }

    /**
     * Get AI budget statistics.
     */
    protected function getBudgetStats(): array
    {
        $settings = AiBudgetSetting::first();

        if (!$settings) {
            return [
                'configured' => false,
                'monthly_budget' => 1.00,
                'current_usage' => 0.00,
                'percentage_used' => 0,
                'is_paused' => false,
                'api_calls_today' => 0,
                'api_calls_month' => 0,
            ];
        }

        $totalBudget = $settings->monthly_budget_usd + $settings->additional_budget_usd;
        $percentageUsed = $totalBudget > 0
            ? round(($settings->current_month_usage_usd / $totalBudget) * 100, 1)
            : 0;

        return [
            'configured' => true,
            'monthly_budget' => $settings->monthly_budget_usd,
            'additional_budget' => $settings->additional_budget_usd,
            'total_budget' => $totalBudget,
            'current_usage' => $settings->current_month_usage_usd,
            'percentage_used' => $percentageUsed,
            'is_paused' => $settings->is_paused,
            'api_calls_today' => AiUsageLog::whereDate('created_at', today())->count(),
            'api_calls_month' => AiUsageLog::currentMonth()->count(),
            'tokens_used_month' => AiUsageLog::currentMonth()->sum('input_tokens') + AiUsageLog::currentMonth()->sum('output_tokens'),
        ];
    }
}
