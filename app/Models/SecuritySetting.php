<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SecuritySetting extends Model
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
     * Get a security setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "security_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('setting_key', $key)->first();
            return $setting ? $setting->setting_value : $default;
        });
    }

    /**
     * Set a security setting.
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
        Cache::forget("security_setting_{$key}");

        return $setting->wasRecentlyCreated || $setting->wasChanged('setting_value');
    }

    /**
     * Get all security settings as an associative array.
     *
     * @return array
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('all_security_settings', 3600, function () {
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
        Cache::forget('all_security_settings');
        
        // Clear individual setting caches
        foreach (static::pluck('setting_key') as $key) {
            Cache::forget("security_setting_{$key}");
        }
    }

    /**
     * Check if two-factor authentication is enabled.
     *
     * @return bool
     */
    public static function isTwoFactorEnabled(): bool
    {
        return static::get('two_factor_auth_enabled', 'true') === 'true';
    }

    /**
     * Get the maximum login attempts allowed.
     *
     * @return int
     */
    public static function getMaxLoginAttempts(): int
    {
        return (int) static::get('max_login_attempts', 5);
    }

    /**
     * Get the session timeout duration in minutes.
     *
     * @return int
     */
    public static function getSessionTimeoutDuration(): int
    {
        return (int) static::get('session_timeout_duration', 20);
    }
}
