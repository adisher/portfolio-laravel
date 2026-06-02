<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    protected $fillable = [
        'key', 'group', 'label', 'description',
        'is_enabled', 'metadata', 'sort_order',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'metadata'   => 'array',
    ];

    // ── Cache ──────────────────────────────────────────────────────────

    protected static string $cacheKey = 'feature_flags_map';
    protected static int    $cacheTtl = 86400; // 24 hours

    /**
     * Load all flags as key → is_enabled map from cache.
     */
    protected static function flagMap(): array
    {
        return Cache::remember(static::$cacheKey, static::$cacheTtl, function () {
            return static::all()->mapWithKeys(fn($f) => [$f->key => $f->is_enabled])->all();
        });
    }

    /**
     * Bust the cache — called automatically on save/delete.
     */
    public static function flushFlagCache(): void
    {
        Cache::forget(static::$cacheKey);
    }

    // ── Model events ──────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(fn() => static::flushFlagCache());
        static::deleted(fn() => static::flushFlagCache());
    }

    // ── Static helpers ────────────────────────────────────────────────

    public static function enabled(string $key): bool
    {
        return static::flagMap()[$key] ?? true; // unknown keys default to enabled
    }

    public static function disabled(string $key): bool
    {
        return ! static::enabled($key);
    }

    /**
     * Return decoded metadata for a flag key (e.g. banner content).
     */
    public static function meta(string $key): array
    {
        return static::where('key', $key)->value('metadata') ?? [];
    }
}
