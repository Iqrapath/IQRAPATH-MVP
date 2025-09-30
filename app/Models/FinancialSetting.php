<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class FinancialSetting extends Model
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
     * Get a financial setting by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $cacheKey = "financial_setting_{$key}";

        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::where('setting_key', $key)->first();
            return $setting ? $setting->setting_value : $default;
        });
    }

    /**
     * Set a financial setting.
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
        Cache::forget("financial_setting_{$key}");

        return $setting->wasRecentlyCreated || $setting->wasChanged('setting_value');
    }

    /**
     * Get all financial settings as an associative array.
     *
     * @return array
     */
    public static function getAllSettings(): array
    {
        return Cache::remember('all_financial_settings', 3600, function () {
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
        Cache::forget('all_financial_settings');
        
        // Clear individual setting caches
        foreach (static::pluck('setting_key') as $key) {
            Cache::forget("financial_setting_{$key}");
        }
    }

    /**
     * Get the commission rate.
     *
     * @return float
     */
    public static function getCommissionRate(): float
    {
        return (float) static::get('commission_rate', 10);
    }

    /**
     * Check if instant payouts are enabled.
     *
     * @return bool
     */
    public static function instantPayoutsEnabled(): bool
    {
        return static::get('instant_payouts_enabled', 'true') === 'true';
    }

    /**
     * Get the minimum withdrawal amount.
     *
     * @return float
     */
    public static function getMinimumWithdrawalAmount(): float
    {
        return (float) static::get('minimum_withdrawal_amount', 10000);
    }

    /**
     * Get daily withdrawal limit.
     *
     * @return float
     */
    public static function getDailyWithdrawalLimit(): float
    {
        return (float) static::get('daily_withdrawal_limit', 500000);
    }

    /**
     * Get monthly withdrawal limit.
     *
     * @return float
     */
    public static function getMonthlyWithdrawalLimit(): float
    {
        return (float) static::get('monthly_withdrawal_limit', 5000000);
    }

    /**
     * Get withdrawal fee for a specific method.
     *
     * @param string $method
     * @return array
     */
    public static function getWithdrawalFee(string $method): array
    {
        $feeType = static::get("{$method}_fee_type", 'flat');
        $feeAmount = (float) static::get("{$method}_fee_amount", 0);
        
        return [
            'type' => $feeType,
            'amount' => $feeAmount
        ];
    }

    /**
     * Get processing time for a specific method.
     *
     * @param string $method
     * @return string
     */
    public static function getProcessingTime(string $method): string
    {
        return static::get("{$method}_processing_time", '1-3 business days');
    }
}
