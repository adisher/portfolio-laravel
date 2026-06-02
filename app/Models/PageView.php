<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class PageView extends Model
{
    use HasFactory;

    protected $fillable = [
        'visitor_id',
        'url',
        'page_title',
        'page_type',
        'content_type',
        'content_id',
        'method',
        'load_time',
        'time_on_page',
        'is_bounce',
        'exit_page',
    ];

    protected $casts = [
        'is_bounce' => 'boolean',
        'load_time' => 'integer',
        'time_on_page' => 'integer',
    ];

    // Relationships
    public function visitor()
    {
        return $this->belongsTo(Visitor::class);
    }

    public function content()
    {
        if ($this->content_type === 'project') {
            return $this->belongsTo(Project::class, 'content_id');
        } elseif ($this->content_type === 'blog_post') {
            return $this->belongsTo(BlogPost::class, 'content_id');
        }
        
        return null;
    }

    // Scopes
    public function scopeNotBot($query)
    {
        return $query->whereHas('visitor', function($q) {
            $q->where('is_bot', false);
        });
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeLastDays($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Static Methods
    public static function getPopularPages($limit = 10, $days = 30)
    {
        return static::notBot()
            ->lastDays($days)
            ->select('url', 'page_title', 'page_type', DB::raw('count(*) as views'))
            ->groupBy('url', 'page_title', 'page_type')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getPopularContent($type, $limit = 10, $days = 30)
    {
        return static::notBot()
            ->where('content_type', $type)
            ->whereNotNull('content_id')
            ->lastDays($days)
            ->select('content_id', 'page_title', DB::raw('count(*) as views'))
            ->groupBy('content_id', 'page_title')
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getTrafficTrends($days = 30)
    {
        return static::notBot()
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(DISTINCT visitor_id) as unique_visitors'),
                DB::raw('COUNT(*) as page_views')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();
    }

    public static function getHourlyTraffic($date = null)
    {
        $date = $date ?: today();
        
        return static::notBot()
            ->whereDate('created_at', $date)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as views')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get();
    }

    public static function getAverageLoadTime($days = 30)
    {
        return static::notBot()
            ->lastDays($days)
            ->whereNotNull('load_time')
            ->avg('load_time');
    }

    public static function getSlowestPages($limit = 10, $days = 30)
    {
        return static::notBot()
            ->lastDays($days)
            ->whereNotNull('load_time')
            ->select('url', 'page_title', DB::raw('AVG(load_time) as avg_load_time'), DB::raw('COUNT(*) as views'))
            ->groupBy('url', 'page_title')
            ->orderBy('avg_load_time', 'desc')
            ->limit($limit)
            ->get();
    }
}