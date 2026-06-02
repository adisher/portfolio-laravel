<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Visitor extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'ip_address',
        'user_agent',
        'device_type',
        'browser',
        'platform',
        'country',
        'city',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'first_visit_at',
        'last_activity_at',
        'page_views',
        'session_duration',
        'is_bot',
    ];

    protected $casts = [
        'first_visit_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'is_bot' => 'boolean',
    ];

    // Relationships
    public function pageViews()
    {
        return $this->hasMany(PageView::class);
    }

    public function contactAnalytics()
    {
        return $this->hasMany(ContactAnalytic::class);
    }

    // Scopes
    public function scopeNotBot($query)
    {
        return $query->where('is_bot', false);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year);
    }

    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static Methods
    public static function createFromRequest($request)
    {
        $agent = new \Jenssegers\Agent\Agent();
        $agent->setUserAgent($request->userAgent());

        return static::create([
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'country' => static::getCountryFromIP($request->ip()),
            'referrer' => $request->header('referer'),
            'utm_source' => $request->get('utm_source'),
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'first_visit_at' => now(), // Explicitly set to now()
            'last_activity_at' => now(), // Explicitly set to now()
            'is_bot' => $agent->isRobot(),
        ]);
    }

    public static function getCountryFromIP($ip)
    {
        // Simple country detection - you can integrate with services like ipapi.co
        if ($ip === '127.0.0.1' || $ip === '::1') {
            return 'Local';
        }
        
        // For now, return null - can be enhanced with IP geolocation service
        return null;
    }

    // Analytics Methods
    public static function getTodayStats()
    {
        return [
            'visitors' => static::notBot()->today()->count(),
            'page_views' => PageView::whereHas('visitor', function($q) {
                $q->notBot();
            })->whereDate('created_at', today())->count(),
            'avg_session_duration' => static::notBot()->today()->avg('session_duration') ?: 0,
            'bounce_rate' => static::getBounceRate('today'),
        ];
    }

    public static function getWeeklyStats()
    {
        return [
            'visitors' => static::notBot()->thisWeek()->count(),
            'page_views' => PageView::whereHas('visitor', function($q) {
                $q->notBot();
            })->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'avg_session_duration' => static::notBot()->thisWeek()->avg('session_duration') ?: 0,
            'bounce_rate' => static::getBounceRate('week'),
        ];
    }

    public static function getMonthlyStats()
    {
        return [
            'visitors' => static::notBot()->thisMonth()->count(),
            'page_views' => PageView::whereHas('visitor', function($q) {
                $q->notBot();
            })->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
            'avg_session_duration' => static::notBot()->thisMonth()->avg('session_duration') ?: 0,
            'bounce_rate' => static::getBounceRate('month'),
        ];
    }

    public static function getBounceRate($period = 'month')
    {
        $query = static::notBot();
        
        switch ($period) {
            case 'today':
                $query = $query->today();
                break;
            case 'week':
                $query = $query->thisWeek();
                break;
            case 'month':
                $query = $query->thisMonth();
                break;
        }

        $totalVisitors = $query->count();
        $bounceVisitors = $query->where('page_views', 1)->count();

        return $totalVisitors > 0 ? round(($bounceVisitors / $totalVisitors) * 100, 1) : 0;
    }

    public static function getTopCountries($limit = 10)
    {
        return static::notBot()
            ->whereNotNull('country')
            ->select('country', DB::raw('count(*) as visitors'))
            ->groupBy('country')
            ->orderBy('visitors', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getDeviceBreakdown()
    {
        return static::notBot()
            ->select('device_type', DB::raw('count(*) as count'))
            ->groupBy('device_type')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->device_type => $item->count];
            });
    }

    public static function getBrowserBreakdown()
    {
        return static::notBot()
            ->whereNotNull('browser')
            ->select('browser', DB::raw('count(*) as count'))
            ->groupBy('browser')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();
    }

    public static function getTrafficSources()
    {
        $organic = static::notBot()->whereNull('referrer')->whereNull('utm_source')->count();
        $social = static::notBot()->where(function($q) {
            $q->where('referrer', 'like', '%facebook%')
              ->orWhere('referrer', 'like', '%twitter%')
              ->orWhere('referrer', 'like', '%linkedin%')
              ->orWhere('referrer', 'like', '%instagram%')
              ->orWhere('utm_source', 'like', '%social%');
        })->count();
        $search = static::notBot()->where(function($q) {
            $q->where('referrer', 'like', '%google%')
              ->orWhere('referrer', 'like', '%bing%')
              ->orWhere('referrer', 'like', '%yahoo%')
              ->orWhere('utm_source', 'like', '%search%');
        })->count();
        $referral = static::notBot()->whereNotNull('referrer')->count() - $social - $search;

        return [
            'direct' => $organic,
            'search' => $search,
            'social' => $social,
            'referral' => max(0, $referral),
        ];
    }
}