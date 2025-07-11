<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SubscriptionTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_uuid',
        'subscription_id',
        'user_id',
        'amount',
        'currency',
        'type',
        'status',
        'payment_method',
        'payment_reference',
        'payment_details',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->transaction_uuid = $transaction->transaction_uuid ?? Str::uuid();
        });
    }

    /**
     * Get the subscription that owns the transaction.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    /**
     * Get the user that owns the transaction.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark the transaction as completed.
     *
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        if ($this->status !== 'completed') {
            $this->status = 'completed';
            return $this->save();
        }

        return false;
    }

    /**
     * Mark the transaction as failed.
     *
     * @param string|null $reason
     * @return bool
     */
    public function markAsFailed(?string $reason = null): bool
    {
        if ($this->status !== 'failed') {
            $this->status = 'failed';
            
            if ($reason) {
                $details = $this->payment_details ?? [];
                $details['failure_reason'] = $reason;
                $this->payment_details = $details;
            }
            
            return $this->save();
        }

        return false;
    }

    /**
     * Process a refund for this transaction.
     *
     * @param string|null $reason
     * @return SubscriptionTransaction|null
     */
    public function processRefund(?string $reason = null): ?SubscriptionTransaction
    {
        if ($this->status !== 'completed' || $this->type === 'refund') {
            return null;
        }

        // Mark this transaction as refunded
        $this->status = 'refunded';
        $this->save();

        // Create a refund transaction
        return self::create([
            'subscription_id' => $this->subscription_id,
            'user_id' => $this->user_id,
            'amount' => -1 * $this->amount, // Negative amount for refund
            'currency' => $this->currency,
            'type' => 'refund',
            'status' => 'completed',
            'payment_method' => $this->payment_method,
            'payment_reference' => 'refund_' . $this->payment_reference,
            'payment_details' => [
                'original_transaction_id' => $this->id,
                'original_transaction_uuid' => $this->transaction_uuid,
                'refund_reason' => $reason ?? 'Manual refund',
            ],
        ]);
    }

    /**
     * Get formatted amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmount(): string
    {
        $symbol = strtolower($this->currency) === 'dollar' ? '$' : '₦';
        return $symbol . number_format($this->amount, 2);
    }
} 