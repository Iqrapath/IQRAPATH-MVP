<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'price_naira',
        'price_dollar',
        'billing_cycle',
        'duration_months',
        'features',
        'tags',
        'image_path',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'features' => 'array',
        'tags' => 'array',
        'price_naira' => 'decimal:2',
        'price_dollar' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Get the subscriptions for the plan.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    /**
     * Get the price based on currency.
     *
     * @param string $currency
     * @return float
     */
    public function getPriceForCurrency(string $currency): float
    {
        return strtolower($currency) === 'dollar' ? $this->price_dollar : $this->price_naira;
    }

    /**
     * Get the formatted price with currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedPrice(string $currency = 'naira'): string
    {
        if (strtolower($currency) === 'dollar') {
            return '$' . number_format($this->price_dollar, 2);
        }
        
        return '₦' . number_format($this->price_naira, 2);
    }

    /**
     * Get active plans.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActivePlans()
    {
        return self::where('is_active', true)->get();
    }
} 