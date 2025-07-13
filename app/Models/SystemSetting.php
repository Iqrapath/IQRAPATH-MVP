<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'setting_key',
        'setting_value',
    ];

    /**
     * Get a system setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "system_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('setting_key', $key)->first();
            return $setting ? $setting->setting_value : $default;
        });
    }

    /**
     * Set a system setting.
     *
     * @param string $key
     * @param mixed $value
     * @return bool
     */
    public static function set(string $key, $value): bool
    {
        $setting = static::updateOrCreate(
            ['setting_key' => $key],
            ['setting_value' => $value]
        );

        // Clear cache for this setting
        Cache::forget("system_setting_{$key}");

        return $setting->wasRecentlyCreated || $setting->wasChanged('setting_value');
    }

    /**
     * Get all system settings as an associative array.
     *
     * @return array
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('all_system_settings', 3600, function () {
            return static::pluck('setting_value', 'setting_key')->toArray();
        });
    }

    /**
     * Clear the settings cache.
     *
     * @return void
     */
    public static function clearCache(): void
    {
        Cache::forget('all_system_settings');
        
        // Clear individual setting caches
        foreach (static::pluck('setting_key') as $key) {
            Cache::forget("system_setting_{$key}");
        }
    }
}
