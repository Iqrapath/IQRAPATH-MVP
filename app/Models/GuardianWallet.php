<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Exception;

class GuardianWallet extends Model
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
        'total_spent_on_children',
        'total_refunded',
        'payment_methods',
        'default_payment_method',
        'auto_fund_children',
        'auto_fund_threshold',
        'family_spending_limits',
        'child_allowances',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'balance' => 'decimal:2',
        'total_spent_on_children' => 'decimal:2',
        'total_refunded' => 'decimal:2',
        'auto_fund_threshold' => 'decimal:2',
        'payment_methods' => 'array',
        'family_spending_limits' => 'array',
        'child_allowances' => 'array',
        'auto_fund_children' => 'boolean',
    ];

    /**
     * Get the guardian that owns the wallet.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the guardian that owns the wallet (alias).
     */
    public function guardian(): BelongsTo
    {
        return $this->user();
    }

    /**
     * Get the guardian profile.
     */
    public function guardianProfile(): BelongsTo
    {
        return $this->belongsTo(GuardianProfile::class, 'user_id', 'user_id');
    }

    /**
     * Get the unified transactions for this wallet.
     */
    public function unifiedTransactions(): MorphMany
    {
        return $this->morphMany(UnifiedTransaction::class, 'wallet');
    }

    /**
     * Get the children associated with this guardian.
     */
    public function children()
    {
        return $this->guardianProfile->students();
    }

    /**
     * Get the children's wallets.
     */
    public function childrenWallets()
    {
        return StudentWallet::whereIn('user_id', 
            $this->children()->pluck('user_id')
        );
    }

    /**
     * Add funds to the guardian wallet.
     *
     * @param float $amount
     * @param string $description
     * @param array $metadata
     * @return UnifiedTransaction
     */
    public function addFunds(float $amount, string $description = 'Wallet funding', array $metadata = []): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        $this->balance += $amount;
        $this->save();

        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'credit',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Fund a child's wallet from guardian wallet.
     *
     * @param StudentWallet $childWallet
     * @param float $amount
     * @param string $description
     * @return UnifiedTransaction
     */
    public function fundChildWallet(StudentWallet $childWallet, float $amount, string $description = 'Family transfer'): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        if ($this->balance < $amount) {
            throw new Exception('Insufficient guardian wallet balance');
        }

        // Check if child belongs to this guardian
        if (!$this->isMyChild($childWallet->user_id)) {
            throw new Exception('Child does not belong to this guardian');
        }

        // Check child allowance if set
        $this->checkChildAllowance($childWallet->user_id, $amount);

        // Perform the transfer
        $this->balance -= $amount;
        $this->total_spent_on_children += $amount;
        $this->save();

        // Add funds to child wallet using existing method
        $childWallet->addFunds($amount, $description);

        // Create unified transaction for the transfer
        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'family_transfer',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
            'from_wallet_type' => GuardianWallet::class,
            'from_wallet_id' => $this->id,
            'to_wallet_type' => StudentWallet::class,
            'to_wallet_id' => $childWallet->id,
            'metadata' => [
                'child_user_id' => $childWallet->user_id,
                'guardian_user_id' => $this->user_id,
            ],
        ]);
    }

    /**
     * Pay for child's subscription from guardian wallet.
     *
     * @param StudentWallet $childWallet
     * @param float $amount
     * @param int $subscriptionId
     * @param string $description
     * @return UnifiedTransaction
     */
    public function payChildSubscription(StudentWallet $childWallet, float $amount, int $subscriptionId, string $description = 'Subscription payment'): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        if ($this->balance < $amount) {
            throw new Exception('Insufficient guardian wallet balance');
        }

        if (!$this->isMyChild($childWallet->user_id)) {
            throw new Exception('Child does not belong to this guardian');
        }

        $this->balance -= $amount;
        $this->total_spent_on_children += $amount;
        $this->save();

        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'subscription_payment',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
            'subscription_id' => $subscriptionId,
            'metadata' => [
                'paid_for_child' => $childWallet->user_id,
                'guardian_user_id' => $this->user_id,
            ],
        ]);
    }

    /**
     * Add a refund to the guardian wallet.
     *
     * @param float $amount
     * @param string $description
     * @return UnifiedTransaction
     */
    public function addRefund(float $amount, string $description = 'Refund'): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Amount must be greater than zero');
        }

        $this->balance += $amount;
        $this->total_refunded += $amount;
        $this->save();

        return $this->unifiedTransactions()->create([
            'transaction_uuid' => $this->generateTransactionUuid(),
            'transaction_type' => 'refund',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'transaction_date' => now(),
        ]);
    }

    /**
     * Set spending allowance for a child.
     *
     * @param int $childUserId
     * @param float $amount
     * @param string $period (daily, weekly, monthly)
     * @return void
     */
    public function setChildAllowance(int $childUserId, float $amount, string $period = 'monthly'): void
    {
        if (!$this->isMyChild($childUserId)) {
            throw new Exception('Child does not belong to this guardian');
        }

        $allowances = $this->child_allowances ?? [];
        $allowances[$childUserId] = [
            'amount' => $amount,
            'period' => $period,
            'spent_this_period' => 0,
            'period_start' => now()->toDateString(),
        ];

        $this->child_allowances = $allowances;
        $this->save();
    }

    /**
     * Check if spending is within child's allowance.
     *
     * @param int $childUserId
     * @param float $amount
     * @return void
     * @throws Exception
     */
    private function checkChildAllowance(int $childUserId, float $amount): void
    {
        $allowances = $this->child_allowances ?? [];
        
        if (!isset($allowances[$childUserId])) {
            return; // No allowance set, no restriction
        }

        $allowance = $allowances[$childUserId];
        $newSpent = $allowance['spent_this_period'] + $amount;

        if ($newSpent > $allowance['amount']) {
            throw new Exception("Amount exceeds child's allowance limit");
        }

        // Update spent amount
        $allowances[$childUserId]['spent_this_period'] = $newSpent;
        $this->child_allowances = $allowances;
        $this->save();
    }

    /**
     * Check if a user is a child of this guardian.
     *
     * @param int $childUserId
     * @return bool
     */
    private function isMyChild(int $childUserId): bool
    {
        return $this->children()->where('user_id', $childUserId)->exists();
    }

    /**
     * Get total family spending summary.
     *
     * @return array
     */
    public function getFamilySpendingSummary(): array
    {
        $children = $this->children()->with(['user', 'wallet'])->get();
        
        $summary = [
            'guardian_balance' => $this->balance,
            'total_spent_on_children' => $this->total_spent_on_children,
            'children' => [],
            'total_family_balance' => $this->balance,
        ];

        foreach ($children as $child) {
            $childWallet = $child->wallet;
            $childBalance = $childWallet ? $childWallet->balance : 0;
            
            $summary['children'][] = [
                'name' => $child->user->name,
                'user_id' => $child->user_id,
                'wallet_balance' => $childBalance,
                'allowance' => $this->child_allowances[$child->user_id] ?? null,
            ];
            
            $summary['total_family_balance'] += $childBalance;
        }

        return $summary;
    }

    /**
     * Generate a unique transaction UUID.
     *
     * @return string
     */
    private function generateTransactionUuid(): string
    {
        return 'GWL-' . date('ymd') . '-' . strtoupper(uniqid());
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
     * Get formatted total spent with currency.
     *
     * @return string
     */
    public function getFormattedTotalSpentAttribute(): string
    {
        return '₦' . number_format($this->total_spent_on_children, 2);
    }
}
