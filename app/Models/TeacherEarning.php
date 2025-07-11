<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherEarning extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'teacher_id',
        'wallet_balance',
        'total_earned',
        'total_withdrawn',
        'pending_payouts',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'wallet_balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_payouts' => 'decimal:2',
    ];

    /**
     * Get the teacher that owns the earnings.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the transactions for the teacher.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'teacher_id', 'teacher_id');
    }

    /**
     * Get the payout requests for the teacher.
     */
    public function payoutRequests(): HasMany
    {
        return $this->hasMany(PayoutRequest::class, 'teacher_id', 'teacher_id');
    }

    /**
     * Update the wallet balance based on a new transaction.
     */
    public function updateBalance(Transaction $transaction): void
    {
        if ($transaction->status !== 'completed') {
            return;
        }

        if ($transaction->transaction_type === 'withdrawal') {
            $this->wallet_balance -= $transaction->amount;
            $this->total_withdrawn += $transaction->amount;
            $this->pending_payouts -= $transaction->amount;
        } elseif (in_array($transaction->transaction_type, ['session_payment', 'referral_bonus'])) {
            $this->wallet_balance += $transaction->amount;
            $this->total_earned += $transaction->amount;
        } elseif ($transaction->transaction_type === 'refund') {
            $this->wallet_balance -= $transaction->amount;
            $this->total_earned -= $transaction->amount;
        }

        $this->save();
    }

    /**
     * Update pending payouts when a payout request is created.
     */
    public function addPendingPayout(float $amount): void
    {
        $this->pending_payouts += $amount;
        $this->wallet_balance -= $amount;
        $this->save();
    }

    /**
     * Update pending payouts when a payout request is declined.
     */
    public function removePendingPayout(float $amount): void
    {
        $this->pending_payouts -= $amount;
        $this->wallet_balance += $amount;
        $this->save();
    }
} 