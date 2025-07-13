<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FeatureFlag extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'feature_key',
        'is_enabled',
        'description',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    /**
     * Check if a feature is enabled.
     *
     * @param string $key
     * @param bool $default
     * @return bool
     */
    public static function isEnabled(string $key, bool $default = false): bool
    {
        $cacheKey = "feature_flag_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $feature = static::where('feature_key', $key)->first();
            return $feature ? $feature->is_enabled : $default;
        });
    }

    /**
     * Enable a feature.
     *
     * @param string $key
     * @return bool
     */
    public static function enable(string $key): bool
    {
        return static::setFeatureStatus($key, true);
    }

    /**
     * Disable a feature.
     *
     * @param string $key
     * @return bool
     */
    public static function disable(string $key): bool
    {
        return static::setFeatureStatus($key, false);
    }

    /**
     * Set a feature's status.
     *
     * @param string $key
     * @param bool $status
     * @return bool
     */
    public static function setFeatureStatus(string $key, bool $status): bool
    {
        $feature = static::where('feature_key', $key)->first();
        
        if (!$feature) {
            return false;
        }
        
        $feature->is_enabled = $status;
        $result = $feature->save();
        
        // Clear cache
        Cache::forget("feature_flag_{$key}");
        Cache::forget('all_feature_flags');
        
        return $result;
    }

    /**
     * Get all features as an associative array.
     *
     * @return array
     */
    public static function getAllFeatures(): array
    {
        return Cache::remember('all_feature_flags', 3600, function () {
            $features = static::all();
            $result = [];
            
            foreach ($features as $feature) {
                $result[$feature->feature_key] = [
                    'is_enabled' => $feature->is_enabled,
                    'description' => $feature->description,
                ];
            }
            
            return $result;
        });
    }

    /**
     * Clear the features cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('all_feature_flags');
        
        // Clear individual feature caches
        foreach (static::pluck('feature_key') as $key) {
            Cache::forget("feature_flag_{$key}");
        }
    }
}
