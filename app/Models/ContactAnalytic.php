<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ContactAnalytic extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_id',
        'visitor_id',
        'source_page',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'pages_viewed_before_contact',
        'time_on_site_before_contact',
        'viewed_projects',
        'viewed_blog_posts',
        'is_returning_visitor',
    ];

    protected $casts = [
        'viewed_projects' => 'array',
        'viewed_blog_posts' => 'array',
        'is_returning_visitor' => 'boolean',
    ];

    // Relationships
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    // Scopes
    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static Methods
    public static function getContactSources($days = 30)
    {
        return static::lastDays($days)
            ->whereNotNull('source_page')
            ->select('source_page', DB::raw('count(*) as contacts'))
            ->groupBy('source_page')
            ->orderBy('contacts', 'desc')
            ->get();
    }

    public static function getContactTrends($days = 30)
    {
        return static::lastDays($days)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as contacts')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public static function getConversionRate($days = 30)
    {
        $totalVisitors = Visitor::notBot()->lastDays($days)->count();
        $contactSubmissions = static::lastDays($days)->count();

        return $totalVisitors > 0 ? round(($contactSubmissions / $totalVisitors) * 100, 2) : 0;
    }

    public static function getAveragePageViewsBeforeContact($days = 30)
    {
        return static::lastDays($days)->avg('pages_viewed_before_contact') ?: 0;
    }

    public static function getPopularProjectsBeforeContact($days = 30)
    {
        $analytics = static::lastDays($days)
            ->whereNotNull('viewed_projects')
            ->get();

        $projectViews = [];
        foreach ($analytics as $analytic) {
            if ($analytic->viewed_projects) {
                foreach ($analytic->viewed_projects as $projectId) {
                    $projectViews[$projectId] = ($projectViews[$projectId] ?? 0) + 1;
                }
            }
        }

        arsort($projectViews);
        return array_slice($projectViews, 0, 10, true);
    }

    public static function getUTMPerformance($days = 30)
    {
        return static::lastDays($days)
            ->whereNotNull('utm_source')
            ->select('utm_source', 'utm_medium', 'utm_campaign', DB::raw('count(*) as contacts'))
            ->groupBy('utm_source', 'utm_medium', 'utm_campaign')
            ->orderBy('contacts', 'desc')
            ->get();
    }
}