<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'description',
        'options',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    // Get setting value with caching
    public static function get($key, $default = null)
    {
        return Cache::remember("setting_{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->where('is_active', true)->first();
            
            if (!$setting) {
                return $default;
            }

            return static::castValue($setting->value, $setting->type);
        });
    }

    // Set setting value
    public static function set($key, $value, $type = 'string')
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'type' => $type]
        );

        // Clear cache
        Cache::forget("setting_{$key}");
        Cache::forget('all_settings');

        return $setting;
    }

    // Get all settings grouped
    public static function getAllGrouped()
    {
        return Cache::remember('all_settings', 3600, function () {
            return static::where('is_active', true)
                ->orderBy('group')
                ->orderBy('sort_order')
                ->get()
                ->groupBy('group')
                ->map(function ($settings) {
                    return $settings->mapWithKeys(function ($setting) {
                        return [$setting->key => static::castValue($setting->value, $setting->type)];
                    });
                });
        });
    }

    // Cast value to proper type
    public static function castValue($value, $type)
    {
        switch ($type) {
            case 'boolean':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN);
            case 'integer':
                return (int) $value;
            case 'array':
                return is_string($value) ? json_decode($value, true) : $value;
            case 'file':
                return $value ? Storage::url($value) : null;
            default:
                return $value;
        }
    }

    // Clear all settings cache
    public static function clearCache()
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("setting_{$key}");
        }
        Cache::forget('all_settings');
    }

    public function getDisplayValueAttribute()
    {
        return static::castValue($this->value, $this->type);
    }
}