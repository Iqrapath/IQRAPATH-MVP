<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentMethod extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'type',
        'name',
        'gateway',
        'gateway_token',
        'gateway_customer_id',
        'last_four',
        'card_brand',
        'card_number_prefix',
        'card_number_middle',
        'exp_month',
        'exp_year',
        'stripe_payment_method_id',
        'expiry_month',
        'expiry_year',
        'bank_name',
        'bank_code',
        'account_name',
        'account_number',
        'phone_number',
        'provider',
        'currency',
        'daily_limit',
        'transaction_limit',
        'is_default',
        'is_active',
        'is_verified',
        'verification_status',
        'verified_at',
        'verification_notes',
        'expires_at',
        'last_used_at',
        'usage_count',
        'details',
        'metadata',
    ];

    protected $casts = [
        'details' => 'array',
        'metadata' => 'array',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'expires_at' => 'date',
        'last_used_at' => 'datetime',
        'exp_month' => 'integer',
        'exp_year' => 'integer',
        'expiry_month' => 'integer',
        'expiry_year' => 'integer',
        'usage_count' => 'integer',
        'daily_limit' => 'decimal:2',
        'transaction_limit' => 'decimal:2',
    ];

    /**
     * Get the user that owns the payment method.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the payment intents for this payment method.
     */
    public function paymentIntents(): HasMany
    {
        return $this->hasMany(PaymentIntent::class);
    }

    /**
     * Get the wallet transactions for this payment method.
     */
    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
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

    /**
     * Check if payment method is verified.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'verified';
    }

    /**
     * Check if payment method is expired.
     */
    public function isExpired(): bool
    {
        if ($this->type !== 'card' || !$this->expiry_year || !$this->expiry_month) {
            return false;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiryDate < now();
    }

    /**
     * Check if payment method is expiring soon.
     */
    public function isExpiringSoon(int $days = 30): bool
    {
        if ($this->type !== 'card' || !$this->expiry_year || !$this->expiry_month) {
            return false;
        }
        
        $expiryDate = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiryDate->diffInDays(now()) <= $days && !$this->isExpired();
    }

    /**
     * Get masked details for display.
     */
    public function maskDetails(): string
    {
        switch ($this->type) {
            case 'card':
                $brand = $this->card_brand ? ucfirst($this->card_brand) : 'Card';
                return "{$brand} ending in {$this->last_four}";
            
            case 'bank_transfer':
                return "{$this->bank_name} - {$this->account_name} (...{$this->last_four})";
            
            case 'mobile_money':
                return "{$this->provider} - {$this->phone_number}";
            
            default:
                return $this->name;
        }
    }

    /**
     * Check if payment method can be used for a transaction type.
     */
    public function canBeUsedFor(string $transactionType): bool
    {
        if (!$this->is_active || !$this->is_verified) {
            return false;
        }
        
        if ($this->isExpired()) {
            return false;
        }
        
        // Add transaction type specific logic here
        return true;
    }

    /**
     * Mark payment method as used.
     */
    public function markAsUsed(): void
    {
        $this->increment('usage_count');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Verify payment method.
     */
    public function verify(string $notes = null): bool
    {
        return $this->update([
            'verification_status' => 'verified',
            'verified_at' => now(),
            'verification_notes' => $notes,
        ]);
    }

    /**
     * Fail verification.
     */
    public function failVerification(string $reason): bool
    {
        return $this->update([
            'verification_status' => 'failed',
            'verification_notes' => $reason,
        ]);
    }
}
