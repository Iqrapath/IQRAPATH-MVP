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
        return strtoupper($currency) === 'USD' ? $this->price_dollar : $this->price_naira;
    }

    /**
     * Get the formatted price with currency symbol.
     *
     * @param string $currency
     * @return string
     */
    public function getFormattedPrice(string $currency = 'NGN'): string
    {
        if (strtoupper($currency) === 'USD') {
            return '$' . number_format($this->price_dollar, 2);
        }
        
        return '₦' . number_format($this->price_naira, 2);
    }

    /**
     * Scope to get only monthly plans.
     */
    public function scopeMonthly($query)
    {
        return $query->where('billing_cycle', 'monthly');
    }

    /**
     * Scope to get only annual plans.
     */
    public function scopeAnnual($query)
    {
        return $query->where('billing_cycle', 'annual');
    }

    /**
     * Get plans by billing cycle.
     */
    public static function getByBillingCycle(?string $cycle = null)
    {
        $query = self::where('is_active', true);
        
        if ($cycle && in_array($cycle, ['monthly', 'annual'])) {
            $query->where('billing_cycle', $cycle);
        }
        
        return $query->orderBy('price_naira', 'asc')->get();
    }

    /**
     * Calculate savings for annual vs monthly.
     */
    public function getAnnualSavingsAttribute(): ?float
    {
        if ($this->billing_cycle !== 'annual') {
            return null;
        }
        
        // Find equivalent monthly plan
        $monthlyPlan = self::where('is_active', true)
            ->where('billing_cycle', 'monthly')
            ->where('name', 'like', '%' . explode(' ', $this->name)[0] . '%')
            ->first();
            
        if (!$monthlyPlan) {
            return null;
        }
        
        $monthlyYearlyCost = $monthlyPlan->price_naira * 12;
        $savings = $monthlyYearlyCost - $this->price_naira;
        
        return $savings > 0 ? round(($savings / $monthlyYearlyCost) * 100, 0) : null;
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