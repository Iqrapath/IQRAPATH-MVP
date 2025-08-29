<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class UnifiedTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_uuid',
        'wallet_type',
        'wallet_id',
        'transaction_type',
        'amount',
        'currency',
        'description',
        'status',
        'session_id',
        'subscription_id',
        'payout_request_id',
        'from_wallet_type',
        'from_wallet_id',
        'to_wallet_type',
        'to_wallet_id',
        'metadata',
        'created_by_id',
        'transaction_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'transaction_date' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (!$transaction->transaction_uuid) {
                $transaction->transaction_uuid = Str::uuid();
            }
            
            if (!$transaction->transaction_date) {
                $transaction->transaction_date = now();
            }
        });
    }

    /**
     * Get the wallet that owns the transaction (polymorphic).
     */
    public function wallet(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the session associated with the transaction.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'session_id');
    }

    /**
     * Get the subscription associated with the transaction.
     */
    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class, 'subscription_id');
    }

    /**
     * Get the payout request associated with the transaction.
     */
    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class, 'payout_request_id');
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the source wallet for transfer transactions.
     */
    public function fromWallet()
    {
        if ($this->from_wallet_type && $this->from_wallet_id) {
            return $this->from_wallet_type::find($this->from_wallet_id);
        }
        return null;
    }

    /**
     * Get the destination wallet for transfer transactions.
     */
    public function toWallet()
    {
        if ($this->to_wallet_type && $this->to_wallet_id) {
            return $this->to_wallet_type::find($this->to_wallet_id);
        }
        return null;
    }

    /**
     * Check if this is a credit transaction.
     *
     * @return bool
     */
    public function isCredit(): bool
    {
        return in_array($this->transaction_type, [
            'credit',
            'session_payment',
            'refund',
            'bonus',
        ]);
    }

    /**
     * Check if this is a debit transaction.
     *
     * @return bool
     */
    public function isDebit(): bool
    {
        return in_array($this->transaction_type, [
            'debit',
            'withdrawal',
            'family_transfer',
            'subscription_payment',
            'fee',
        ]);
    }

    /**
     * Check if this is a transfer transaction.
     *
     * @return bool
     */
    public function isTransfer(): bool
    {
        return $this->transaction_type === 'family_transfer';
    }

    /**
     * Get formatted amount with currency symbol.
     *
     * @return string
     */
    public function getFormattedAmountAttribute(): string
    {
        $symbol = $this->currency === 'NGN' ? '₦' : '$';
        return $symbol . number_format($this->amount, 2);
    }

    /**
     * Get transaction type display name.
     *
     * @return string
     */
    public function getTypeDisplayAttribute(): string
    {
        $types = [
            'credit' => 'Credit',
            'debit' => 'Debit',
            'session_payment' => 'Session Payment',
            'withdrawal' => 'Withdrawal',
            'family_transfer' => 'Family Transfer',
            'subscription_payment' => 'Subscription Payment',
            'refund' => 'Refund',
            'bonus' => 'Bonus',
            'fee' => 'Fee',
            'adjustment' => 'Adjustment',
        ];

        return $types[$this->transaction_type] ?? ucfirst($this->transaction_type);
    }

    /**
     * Get wallet type display name.
     *
     * @return string
     */
    public function getWalletTypeDisplayAttribute(): string
    {
        $types = [
            'App\Models\StudentWallet' => 'Student Wallet',
            'App\Models\TeacherWallet' => 'Teacher Wallet',
            'App\Models\GuardianWallet' => 'Guardian Wallet',
        ];

        return $types[$this->wallet_type] ?? 'Unknown Wallet';
    }

    /**
     * Scope a query to only include credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->whereIn('transaction_type', [
            'credit',
            'session_payment',
            'refund',
            'bonus',
        ]);
    }

    /**
     * Scope a query to only include debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->whereIn('transaction_type', [
            'debit',
            'withdrawal',
            'family_transfer',
            'subscription_payment',
            'fee',
        ]);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope a query to only include transfer transactions.
     */
    public function scopeTransfers($query)
    {
        return $query->where('transaction_type', 'family_transfer');
    }

    /**
     * Scope a query to filter by wallet type.
     */
    public function scopeForWalletType($query, string $walletType)
    {
        return $query->where('wallet_type', $walletType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }
}
