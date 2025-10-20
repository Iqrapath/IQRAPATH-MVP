<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\PaymentMethod;
use Exception;

class TeacherWallet extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'balance',
        'total_earned',
        'total_withdrawn',
        'pending_payouts',
        'default_payment_method_id',
        'auto_withdrawal_enabled',
        'auto_withdrawal_threshold',
        'withdrawal_settings',
        'paypal_email',
        'last_sync_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_earned' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_payouts' => 'decimal:2',
        'auto_withdrawal_threshold' => 'decimal:2',
        'withdrawal_settings' => 'array',
        'auto_withdrawal_enabled' => 'boolean',
        'last_sync_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::created(function ($wallet) {
            // Sync with TeacherEarning on creation
            $wallet->syncWithTeacherEarning();
        });
    }

    /**
     * Get the teacher that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the teacher that owns the wallet (alias).
     */
    public function teacher(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the default payment method for this wallet.
     */
    public function defaultPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

    /**
     * Get the unified transactions for this wallet.
     */
    public function unifiedTransactions(): MorphMany
    {
        return $this->morphMany(UnifiedTransaction::class, 'wallet');
    }

    /**
     * Get the legacy transactions for this teacher.
     */
    public function legacyTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'teacher_id', 'user_id');
    }

    /**
     * Get the teacher earnings record.
     */
    public function teacherEarning(): BelongsTo
    {
        return $this->belongsTo(TeacherEarning::class, 'user_id', 'teacher_id');
    }

    /**
     * Add earnings to the wallet.
     *
     * @param float $amount
     * @param string $description
     * @param array $metadata
     * @return UnifiedTransaction
     */
    public function addEarnings(float $amount, string $description = 'Session payment', array $metadata = []): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        $this->balance += $amount;
        $this->total_earned += $amount;
        $this->save();

        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'session_payment',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Process withdrawal from wallet.
     *
     * @param float $amount
     * @param string $description
     * @param array $metadata
     * @return UnifiedTransaction
     */
    public function processWithdrawal(float $amount, string $description = 'Withdrawal', array $metadata = []): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        if ($this->balance < $amount) {
            throw new Exception('Insufficient wallet balance');
        }

        $this->balance -= $amount;
        $this->total_withdrawn += $amount;
        $this->save();

        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'withdrawal',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Add pending payout (reduces available balance).
     *
     * @param float $amount
     * @return void
     */
    public function addPendingPayout(float $amount): void
    {
        if ($this->balance < $amount) {
            throw new Exception('Insufficient balance for payout request');
        }

        $this->balance -= $amount;
        $this->pending_payouts += $amount;
        $this->save();
    }

    /**
     * Remove pending payout (restores available balance).
     *
     * @param float $amount
     * @return void
     */
    public function removePendingPayout(float $amount): void
    {
        $this->balance += $amount;
        $this->pending_payouts -= $amount;
        $this->save();
    }

    /**
     * Get available balance (excluding pending payouts).
     *
     * @return float
     */
    public function getAvailableBalance(): float
    {
        return (float) $this->balance;
    }

    /**
     * Check if withdrawal threshold is met for auto-withdrawal.
     *
     * @return bool
     */
    public function canAutoWithdraw(): bool
    {
        return $this->auto_withdrawal_enabled 
            && $this->auto_withdrawal_threshold 
            && $this->balance >= $this->auto_withdrawal_threshold;
    }

    /**
     * Sync this wallet with the legacy TeacherEarning record.
     *
     * @return void
     */
    public function syncWithTeacherEarning(): void
    {
        $earning = TeacherEarning::firstOrCreate(
            ['teacher_id' => $this->user_id],
            [
                'wallet_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_payouts' => 0,
            ]
        );

        // Update earning record to match wallet
        $earning->update([
            'wallet_balance' => $this->balance,
            'total_earned' => $this->total_earned,
            'total_withdrawn' => $this->total_withdrawn,
            'pending_payouts' => $this->pending_payouts,
        ]);

        $this->update(['last_sync_at' => now()]);
    }

    /**
     * Import data from TeacherEarning record.
     *
     * @return void
     */
    public function importFromTeacherEarning(): void
    {
        $earning = TeacherEarning::where('teacher_id', $this->user_id)->first();
        
        if ($earning) {
            $this->update([
                'balance' => $earning->wallet_balance,
                'total_earned' => $earning->total_earned,
                'total_withdrawn' => $earning->total_withdrawn,
                'pending_payouts' => $earning->pending_payouts,
                'last_sync_at' => now(),
            ]);
        }
    }

    /**
     * Generate a unique transaction UUID.
     *
     * @return string
     */
    private function generateTransactionUuid(): string
    {
        return 'TWL-' . date('ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Get formatted balance with currency.
     *
     * @return string
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '₦' . number_format($this->balance, 2);
    }

    /**
     * Get formatted total earned with currency.
     *
     * @return string
     */
    public function getFormattedTotalEarnedAttribute(): string
    {
        return '₦' . number_format($this->total_earned, 2);
    }
}
