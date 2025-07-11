<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'payment_methods',
        'default_payment_method',
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
        'payment_methods' => 'array',
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
     * Add a payment method to the wallet.
     *
     * @param array $paymentMethod
     * @param bool $setAsDefault
     * @return bool
     */
    public function addPaymentMethod(array $paymentMethod, bool $setAsDefault = false): bool
    {
        $methods = $this->payment_methods ?? [];
        
        // Generate a unique ID for this payment method
        $paymentMethod['id'] = uniqid('pm_');
        $paymentMethod['created_at'] = now()->toDateTimeString();
        
        $methods[] = $paymentMethod;
        $this->payment_methods = $methods;
        
        if ($setAsDefault || count($methods) === 1) {
            $this->default_payment_method = $paymentMethod['id'];
        }
        
        return $this->save();
    }

    /**
     * Remove a payment method from the wallet.
     *
     * @param string $paymentMethodId
     * @return bool
     */
    public function removePaymentMethod(string $paymentMethodId): bool
    {
        $methods = $this->payment_methods ?? [];
        
        $filteredMethods = array_filter($methods, function ($method) use ($paymentMethodId) {
            return $method['id'] !== $paymentMethodId;
        });
        
        // If we removed the default payment method, set a new default
        if ($this->default_payment_method === $paymentMethodId && count($filteredMethods) > 0) {
            $this->default_payment_method = $filteredMethods[array_key_first($filteredMethods)]['id'];
        } elseif (count($filteredMethods) === 0) {
            $this->default_payment_method = null;
        }
        
        $this->payment_methods = array_values($filteredMethods);
        return $this->save();
    }

    /**
     * Set default payment method.
     *
     * @param string $paymentMethodId
     * @return bool
     */
    public function setDefaultPaymentMethod(string $paymentMethodId): bool
    {
        if (!$this->hasPaymentMethod($paymentMethodId)) {
            return false;
        }
        
        $this->default_payment_method = $paymentMethodId;
        return $this->save();
    }

    /**
     * Get the default payment method.
     *
     * @return array|null
     */
    public function getDefaultPaymentMethod(): ?array
    {
        if (!$this->default_payment_method) {
            return null;
        }
        
        $methods = $this->payment_methods ?? [];
        
        foreach ($methods as $method) {
            if ($method['id'] === $this->default_payment_method) {
                return $method;
            }
        }
        
        return null;
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

    /**
     * Check if the wallet has a specific payment method.
     *
     * @param string $paymentMethodId
     * @return bool
     */
    public function hasPaymentMethod(string $paymentMethodId): bool
    {
        $methods = $this->payment_methods ?? [];
        
        foreach ($methods as $method) {
            if ($method['id'] === $paymentMethodId) {
                return true;
            }
        }
        
        return false;
    }
} 