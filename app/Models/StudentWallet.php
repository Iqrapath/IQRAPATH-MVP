<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\PaymentMethod;

class StudentWallet extends Model
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
        'total_spent',
        'total_refunded',
        'default_payment_method_id',
        'auto_renew_enabled',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'total_refunded' => 'decimal:2',
        'auto_renew_enabled' => 'boolean',
    ];

    /**
     * Get the user that owns the wallet.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the default payment method for this wallet.
     */
    public function defaultPaymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'default_payment_method_id');
    }

    /**
     * Get the transactions for this wallet.
     */
    public function transactions()
    {
        return $this->hasMany(WalletTransaction::class, 'wallet_id');
    }

    /**
     * Get the subscriptions for the user.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'user_id', 'user_id');
    }

    /**
     * Add funds to the wallet.
     *
     * @param float $amount
     * @param string $description
     * @return bool
     */
    public function addFunds(float $amount, string $description = 'Wallet funding'): bool
    {
        $this->balance += $amount;
        
        // Create a transaction record
        $this->transactions()->create([
            'transaction_type' => 'credit',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
        ]);
        
        return $this->save();
    }

    /**
     * Deduct funds from the wallet.
     *
     * @param float $amount
     * @param string $description
     * @return bool
     * @throws \Exception
     */
    public function deductFunds(float $amount, string $description = 'Wallet deduction'): bool
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient wallet balance');
        }
        
        $this->balance -= $amount;
        
        // Create a transaction record
        $this->transactions()->create([
            'transaction_type' => 'debit',
            'amount' => $amount,
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
        ]);
        
        return $this->save();
    }

    /**
     * Add a refund to the wallet.
     *
     * @param float $amount
     * @return bool
     */
    public function addRefund(float $amount): bool
    {
        if ($amount <= 0) {
            return false;
        }

        $this->balance += $amount;
        $this->total_refunded += $amount;
        return $this->save();
    }

    /**
     * Set default payment method.
     *
     * @param int $paymentMethodId
     * @return bool
     */
    public function setDefaultPaymentMethod(int $paymentMethodId): bool
    {
        // Verify the payment method belongs to the wallet's user and is active
        $paymentMethod = PaymentMethod::where('id', $paymentMethodId)
            ->where('user_id', $this->user_id)
            ->where('is_active', true)
            ->first();

        if (!$paymentMethod) {
            return false;
        }
        
        $this->default_payment_method_id = $paymentMethodId;
        return $this->save();
    }

    /**
     * Check if the wallet has sufficient balance for an amount.
     *
     * @param float $amount
     * @return bool
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }


} 