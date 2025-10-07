<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuardianWalletTransaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'wallet_id',
        'transaction_type',
        'amount',
        'description',
        'status',
        'transaction_date',
        'metadata',
        'reference',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'transaction_date' => 'datetime',
        'metadata' => 'array',
        'amount' => 'decimal:2',
    ];

    /**
     * Get the guardian wallet that owns the transaction.
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(GuardianWallet::class, 'wallet_id');
    }

    /**
     * Get the guardian user that owns the transaction through the wallet.
     */
    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'wallet_id', 'id')->where('role', 'guardian');
    }

    /**
     * Scope a query to only include credit transactions.
     */
    public function scopeCredits($query)
    {
        return $query->where('transaction_type', 'credit');
    }

    /**
     * Scope a query to only include debit transactions.
     */
    public function scopeDebits($query)
    {
        return $query->where('transaction_type', 'debit');
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}