<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'name',
        'details',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'details' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user that owns the payment method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active payment methods.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include default payment methods.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Get formatted display text for the payment method.
     */
    public function getDisplayTextAttribute(): string
    {
        switch ($this->type) {
            case 'bank_transfer':
                $details = $this->details;
                return "{$details['bank_name']} - {$details['account_holder']} | {$details['account_number']}";
            case 'mobile_money':
                $details = $this->details;
                return "{$details['provider']} - {$details['phone_number']}";
            default:
                return $this->name;
        }
    }
}
