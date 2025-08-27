<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Subscription extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'subscription_uuid',
        'user_id',
        'subscription_plan_id',
        'start_date',
        'end_date',
        'amount_paid',
        'currency',
        'status',
        'next_billing_date',
        'auto_renew',
        'payment_method',
        'payment_reference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'next_billing_date' => 'date',
        'amount_paid' => 'decimal:2',
        'auto_renew' => 'boolean',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($subscription) {
            $subscription->subscription_uuid = $subscription->subscription_uuid ?? Str::uuid();
        });
    }

    /**
     * Get the user that owns the subscription.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan that the subscription belongs to.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(SubscriptionPlan::class, 'subscription_plan_id');
    }

    /**
     * Get the transactions for the subscription.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(SubscriptionTransaction::class);
    }

    /**
     * Get the subscription plan name.
     */
    public function getPlanNameAttribute(): string
    {
        return $this->plan ? $this->plan->name : 'Unknown Plan';
    }

    /**
     * Check if the subscription is active.
     */
    public function isActive(): bool
    {
        return $this->status === 'active' && 
               $this->end_date >= now()->toDateString();
    }

    /**
     * Check if the subscription is expired.
     */
    public function isExpired(): bool
    {
        return $this->end_date < now()->toDateString();
    }

    /**
     * Check if the subscription is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Get days remaining in subscription.
     */
    public function getDaysRemainingAttribute(): int
    {
        if ($this->isExpired()) {
            return 0;
        }
        
        return now()->diffInDays($this->end_date, false);
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return $this->currency . ' ' . number_format($this->amount_paid, 2);
    }

    /**
     * Check if the subscription is active (legacy method).
     *
     * @return bool
     */
    public function isActiveLegacy(): bool
    {
        return $this->status === 'active' && now()->lte($this->end_date);
    }

    /**
     * Check if the subscription has expired.
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        return now()->gt($this->end_date);
    }

    /**
     * Get days remaining in the subscription.
     *
     * @return int
     */
    public function daysRemaining(): int
    {
        if ($this->hasExpired()) {
            return 0;
        }

        return now()->diffInDays($this->end_date);
    }

    /**
     * Mark the subscription as expired.
     *
     * @return bool
     */
    public function markAsExpired(): bool
    {
        if ($this->status !== 'expired') {
            $this->status = 'expired';
            return $this->save();
        }

        return false;
    }

    /**
     * Cancel the subscription.
     *
     * @return bool
     */
    public function cancel(): bool
    {
        if ($this->status === 'active') {
            $this->status = 'cancelled';
            $this->auto_renew = false;
            return $this->save();
        }

        return false;
    }

    /**
     * Renew the subscription.
     *
     * @param int|null $durationMonths
     * @return bool
     */
    public function renew(?int $durationMonths = null): bool
    {
        if ($this->status !== 'active' && $this->status !== 'expired') {
            return false;
        }

        $duration = $durationMonths ?? $this->plan->duration_months;
        
        $this->start_date = now();
        $this->end_date = now()->addMonths($duration);
        $this->status = 'active';
        $this->next_billing_date = $this->auto_renew ? $this->end_date : null;
        
        return $this->save();
    }
} 