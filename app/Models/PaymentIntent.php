<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PaymentIntent extends Model
{
    protected $fillable = [
        'intent_uuid',
        'user_id',
        'payment_method_id',
        'amount',
        'currency',
        'description',
        'gateway',
        'gateway_intent_id',
        'gateway_client_secret',
        'status',
        'reference_type',
        'reference_id',
        'failure_code',
        'failure_message',
        'metadata',
        'confirmed_at',
        'failed_at',
        'canceled_at',
        'expires_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'confirmed_at' => 'datetime',
        'failed_at' => 'datetime',
        'canceled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($intent) {
            $intent->intent_uuid = $intent->intent_uuid ?? \Illuminate\Support\Str::uuid();
            
            // Set expiry to 24 hours from now if not set
            if (!$intent->expires_at) {
                $intent->expires_at = now()->addHours(24);
            }
        });
    }

    /**
     * Get the user that owns the payment intent.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment method used.
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Get the payable model (Subscription, Booking, etc.).
     */
    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Check if payment intent is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if payment intent succeeded.
     */
    public function isSucceeded(): bool
    {
        return $this->status === 'succeeded';
    }

    /**
     * Check if payment intent failed.
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Check if payment intent is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at < now();
    }

    /**
     * Mark as succeeded.
     */
    public function markAsSucceeded(): bool
    {
        return $this->update([
            'status' => 'succeeded',
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(string $code = null, string $message = null): bool
    {
        return $this->update([
            'status' => 'failed',
            'failure_code' => $code,
            'failure_message' => $message,
            'failed_at' => now(),
        ]);
    }

    /**
     * Cancel the intent.
     */
    public function cancel(): bool
    {
        if ($this->isSucceeded()) {
            return false;
        }

        return $this->update([
            'status' => 'canceled',
            'canceled_at' => now(),
        ]);
    }

    /**
     * Mark as expired.
     */
    public function markAsExpired(): bool
    {
        return $this->update([
            'status' => 'expired',
        ]);
    }

    /**
     * Get formatted amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbol = strtoupper($this->currency) === 'USD' ? '$' : '₦';
        return number_format($this->amount, 2) . ' ' . $this->currency;
    }
}
