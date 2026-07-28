<?php

namespace App\Models;

use App\Support\BotDetector;
use App\Support\TrafficSource;
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
        'source',
        'source_detail',
        'first_visit_at',
        'last_activity_at',
        'page_views',
        'session_duration',
        'is_bot',
        'bot_reason',
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

        $referrer = $request->header('referer');
        $utmSource = $request->get('utm_source');

        // Layered bot detection (catches AI crawlers isRobot() misses) and
        // source classification, both resolved once at creation and persisted.
        [$isBot, $botReason] = BotDetector::fromUserAgent($request->userAgent());
        [$source, $sourceDetail] = TrafficSource::classify($referrer, $utmSource);

        return static::create([
            'session_id' => session()->getId(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
            'browser' => $agent->browser(),
            'platform' => $agent->platform(),
            'country' => static::getCountryFromIP($request->ip()),
            'referrer' => $referrer,
            'utm_source' => $utmSource,
            'utm_medium' => $request->get('utm_medium'),
            'utm_campaign' => $request->get('utm_campaign'),
            'source' => $source,
            'source_detail' => $sourceDetail,
            'first_visit_at' => now(), // Explicitly set to now()
            'last_activity_at' => now(), // Explicitly set to now()
            'is_bot' => $isBot,
            'bot_reason' => $botReason,
        ]);
    }

    public static function getCountryFromIP($ip)
    {
        if (empty($ip) || $ip === '127.0.0.1' || $ip === '::1') {
            return 'Local';
        }

        // Cache per IP for 30 days so repeat visitors and rate limits are handled
        return \Illuminate\Support\Facades\Cache::remember("geoip:{$ip}", now()->addDays(30), function () use ($ip) {
            try {
                $response = \Illuminate\Support\Facades\Http::timeout(2)
                    ->get("http://ip-api.com/json/{$ip}", ['fields' => 'status,country']);

                if ($response->successful() && $response->json('status') === 'success') {
                    return $response->json('country');
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::info("GeoIP lookup failed for {$ip}: " . $e->getMessage());
            }
            return null;
        });
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

    /**
     * Human traffic split by acquisition source, from the persisted `source`
     * column (App\Support\TrafficSource). Includes an AI-assistant bucket that
     * the old referrer-substring version could not see. `$days` optionally
     * scopes to a recent window.
     */
    public static function getTrafficSources(?int $days = null)
    {
        $q = static::notBot();
        if ($days !== null) {
            $q->where('created_at', '>=', now()->subDays($days));
        }

        $counts = $q->select('source', DB::raw('count(*) as c'))
            ->groupBy('source')
            ->pluck('c', 'source');

        return [
            'direct'   => (int) ($counts[TrafficSource::DIRECT] ?? 0),
            'search'   => (int) ($counts[TrafficSource::SEARCH] ?? 0),
            'ai'       => (int) ($counts[TrafficSource::AI] ?? 0),
            'social'   => (int) ($counts[TrafficSource::SOCIAL] ?? 0),
            'referral' => (int) ($counts[TrafficSource::REFERRAL] ?? 0),
        ];
    }
}