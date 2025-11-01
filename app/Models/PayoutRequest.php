<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PayoutRequest extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'request_uuid',
        'teacher_id',
        'amount',
        'payment_method',
        'payment_details',
        'status',
        'request_date',
        'processed_date',
        'processed_by_id',
        'notes',
        'transaction_id',
        'currency',
        'exchange_rate_used',
        'fee_amount',
        'fee_currency',
        'external_reference',
        'external_transfer_code',
        'processed_at',
        'completed_at',
        'failed_at',
        'cancelled_at',
        'returned_at',
        'failure_reason',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'payment_details' => 'array',
        'request_date' => 'date',
        'processed_date' => 'date',
        'exchange_rate_used' => 'decimal:6',
        'fee_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'returned_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payoutRequest) {
            // Generate a unique request ID if not provided
            if (!$payoutRequest->request_uuid) {
                // Use timestamp + random to ensure uniqueness
                $payoutRequest->request_uuid = 'POUT-' . date('ymd') . '-' . time() . '-' . str_pad(random_int(1, 9999), 4, '0', STR_PAD_LEFT);
            }

            // Set request date to today if not provided
            if (!$payoutRequest->request_date) {
                $payoutRequest->request_date = now()->format('Y-m-d');
            }

            // Update teacher earnings when a payout request is created
            $teacherEarning = TeacherEarning::firstOrCreate(
                ['teacher_id' => $payoutRequest->teacher_id],
                [
                    'wallet_balance' => 0,
                    'total_earned' => 0,
                    'total_withdrawn' => 0,
                    'pending_payouts' => 0,
                ]
            );

            $teacherEarning->addPendingPayout($payoutRequest->amount);
        });

        static::updated(function ($payoutRequest) {
            // If payout request status changed to declined, update teacher earnings
            if ($payoutRequest->isDirty('status') && $payoutRequest->status === 'declined') {
                $teacherEarning = TeacherEarning::where('teacher_id', $payoutRequest->teacher_id)->first();
                if ($teacherEarning) {
                    $teacherEarning->removePendingPayout($payoutRequest->amount);
                }
            }

            // If payout request status changed to approved, create a withdrawal transaction
            if ($payoutRequest->isDirty('status') && $payoutRequest->status === 'approved' && !$payoutRequest->transaction_id) {
                $transaction = Transaction::create([
                    'teacher_id' => $payoutRequest->teacher_id,
                    'transaction_type' => 'withdrawal',
                    'description' => 'Payout request #' . $payoutRequest->request_uuid,
                    'amount' => $payoutRequest->amount,
                    'currency' => $payoutRequest->currency ?? 'NGN',
                    'exchange_rate_used' => $payoutRequest->exchange_rate_used,
                    'exchange_rate_date' => now(),
                    'status' => 'completed',
                    'transaction_date' => now()->format('Y-m-d'),
                    'created_by_id' => $payoutRequest->processed_by_id,
                ]);

                $payoutRequest->transaction_id = $transaction->id;
                $payoutRequest->save();
            }
        });
    }

    /**
     * Get the teacher associated with the payout request.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the admin who processed the payout request.
     */
    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by_id');
    }

    /**
     * Get the transaction associated with the payout request.
     */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    /**
     * Format amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get payment method display name.
     */
    public function getPaymentMethodDisplayAttribute(): string
    {
        return match($this->payment_method) {
            'bank_transfer' => 'Bank Transfer',
            'paypal' => 'PayPal',
            'mobile_money' => 'Mobile Money',
            'other' => 'Other',
            default => ucfirst($this->payment_method),
        };
    }

    /**
     * Get status display name.
     */
    public function getStatusDisplayAttribute(): string
    {
        return match($this->status) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'approved' => 'Approved',
            'declined' => 'Declined',
            'paid' => 'Paid',
            default => ucfirst($this->status),
        };
    }

    /**
     * Scope a query to only include pending payout requests.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved payout requests.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include declined payout requests.
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Scope a query to only include paid payout requests.
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'paid');
    }

    /**
     * Approve the payout request and initiate payment gateway transfer.
     */
    public function approve(User $admin, bool $autoProcessPayout = true): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($admin, $autoProcessPayout) {
            // Lock the payout request to prevent double approval
            $lockedPayout = self::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            // Double-check status after lock
            if ($lockedPayout->status !== 'pending') {
                throw new \Exception('Payout request is not pending. Current status: ' . $lockedPayout->status);
            }

            // Update payout request status
            $lockedPayout->status = 'approved';
            $lockedPayout->processed_by_id = $admin->id;
            $lockedPayout->processed_date = now();
            $lockedPayout->save();

            // Get teacher's wallet with lock
            $teacherWallet = TeacherWallet::where('user_id', $lockedPayout->teacher_id)
                ->lockForUpdate()
                ->first();
            
            if (!$teacherWallet) {
                throw new \Exception('Teacher wallet not found');
            }

            // Safety check: ensure pending_payouts has sufficient amount
            if ($teacherWallet->pending_payouts < $lockedPayout->amount) {
                throw new \Exception('Insufficient pending payouts. Expected: ' . $lockedPayout->amount . ', Available: ' . $teacherWallet->pending_payouts);
            }

            // Safety check: prevent negative pending_payouts
            if (($teacherWallet->pending_payouts - $lockedPayout->amount) < 0) {
                throw new \Exception('Operation would result in negative pending payouts');
            }

            // Process the payout in the wallet
            // 1. Decrement pending_payouts
            // 2. Increment total_withdrawn
            $teacherWallet->pending_payouts -= $lockedPayout->amount;
            $teacherWallet->total_withdrawn += $lockedPayout->amount;
            $teacherWallet->save();

            // Create transaction record (UUID auto-generated by model)
            $transaction = $teacherWallet->unifiedTransactions()->create([
                'transaction_type' => 'withdrawal',
                'amount' => $lockedPayout->amount,
                'currency' => 'NGN',
                'description' => 'Payout approved - ' . $lockedPayout->payment_method,
                'status' => 'completed',
                'transaction_date' => now(),
                'metadata' => [
                    'payout_request_id' => $lockedPayout->id,
                    'payment_method' => $lockedPayout->payment_method,
                    'payment_details' => $lockedPayout->payment_details,
                    'approved_by' => $admin->id,
                    'approved_at' => now()->toDateTimeString(),
                ],
            ]);

            // Link transaction to payout request
            $lockedPayout->transaction_id = $transaction->id;
            $lockedPayout->save();

            // Sync wallet with earnings table
            $teacherWallet->syncWithTeacherEarning();

            // Update the current instance
            $this->refresh();

            \Illuminate\Support\Facades\Log::info('Payout approved and processed', [
                'payout_request_id' => $lockedPayout->id,
                'teacher_id' => $lockedPayout->teacher_id,
                'amount' => $lockedPayout->amount,
                'admin_id' => $admin->id,
                'transaction_id' => $transaction->id,
                'wallet_balance' => $teacherWallet->balance,
                'wallet_pending' => $teacherWallet->pending_payouts,
                'wallet_withdrawn' => $teacherWallet->total_withdrawn,
            ]);
        });

        // After transaction commits, initiate payment gateway transfer
        if ($autoProcessPayout) {
            // Dispatch payout processing as a queued job to avoid blocking
            // For now, process synchronously but catch all errors
            try {
                $payoutService = app(\App\Services\PayoutService::class);
                $result = $payoutService->processPayout($this->fresh());
                
                if ($result['success']) {
                    // Success notification to admin
                    $admin->notify(new \App\Notifications\PayoutProcessedNotification(
                        'success',
                        $this->fresh()
                    ));
                } else {
                    \Illuminate\Support\Facades\Log::warning('Automatic payout processing failed', [
                        'payout_request_id' => $this->id,
                        'payment_method' => $this->payment_method,
                        'error' => $result['message'] ?? 'Unknown error',
                    ]);
                    
                    // Failure notification to admin
                    $admin->notify(new \App\Notifications\PayoutProcessedNotification(
                        'failed',
                        $this->fresh(),
                        $result['message'] ?? 'Unknown error'
                    ));
                    
                    // Don't change status - keep as 'approved' for manual processing
                    // Just add a note about the automatic processing failure
                    $this->update([
                        'notes' => ($this->notes ? $this->notes . "\n\n" : '') . 
                                  '[' . now()->format('Y-m-d H:i:s') . '] ' .
                                  'Automatic transfer failed: ' . ($result['message'] ?? 'Unknown error') . 
                                  '. Please process manually or retry.',
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Payout processing exception', [
                    'payout_request_id' => $this->id,
                    'payment_method' => $this->payment_method,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Exception notification to admin
                $admin->notify(new \App\Notifications\PayoutProcessedNotification(
                    'error',
                    $this->fresh(),
                    $e->getMessage()
                ));
                
                // Don't change status - keep as 'approved' for manual processing
                $this->update([
                    'notes' => ($this->notes ? $this->notes . "\n\n" : '') . 
                              '[' . now()->format('Y-m-d H:i:s') . '] ' .
                              'Automatic transfer exception: ' . $e->getMessage() . 
                              '. Please process manually.',
                ]);
            }
        }
    }

    /**
     * Reject the payout request.
     */
    public function reject(User $admin, ?string $reason = null): void
    {
        \Illuminate\Support\Facades\DB::transaction(function () use ($admin, $reason) {
            // Lock the payout request to prevent double rejection
            $lockedPayout = self::where('id', $this->id)
                ->lockForUpdate()
                ->first();

            // Double-check status after lock
            if ($lockedPayout->status !== 'pending') {
                throw new \Exception('Payout request is not pending. Current status: ' . $lockedPayout->status);
            }

            // Update payout request status
            $lockedPayout->status = 'rejected';
            $lockedPayout->processed_by_id = $admin->id;
            $lockedPayout->processed_date = now();
            
            if ($reason) {
                $lockedPayout->notes = $reason;
            }
            
            $lockedPayout->save();

            // Get teacher's wallet with lock
            $teacherWallet = TeacherWallet::where('user_id', $lockedPayout->teacher_id)
                ->lockForUpdate()
                ->first();
            
            if (!$teacherWallet) {
                throw new \Exception('Teacher wallet not found');
            }

            // Safety check: ensure pending_payouts has sufficient amount
            if ($teacherWallet->pending_payouts < $lockedPayout->amount) {
                throw new \Exception('Insufficient pending payouts to restore. Expected: ' . $lockedPayout->amount . ', Available: ' . $teacherWallet->pending_payouts);
            }

            // Restore the balance
            // 1. Increment balance (restore available funds)
            // 2. Decrement pending_payouts
            $teacherWallet->balance += $lockedPayout->amount;
            $teacherWallet->pending_payouts -= $lockedPayout->amount;
            $teacherWallet->save();

            // Sync wallet with earnings table
            $teacherWallet->syncWithTeacherEarning();

            // Update the current instance
            $this->refresh();

            \Illuminate\Support\Facades\Log::info('Payout rejected and balance restored', [
                'payout_request_id' => $lockedPayout->id,
                'teacher_id' => $lockedPayout->teacher_id,
                'amount' => $lockedPayout->amount,
                'admin_id' => $admin->id,
                'reason' => $reason,
                'wallet_balance' => $teacherWallet->balance,
                'wallet_pending' => $teacherWallet->pending_payouts,
            ]);
        });
    }

    /**
     * Decline the payout request (alias for reject).
     */
    public function decline(User $admin, ?string $reason = null): void
    {
        $this->reject($admin, $reason);
    }

    /**
     * Mark the payout request as paid.
     */
    public function markAsPaid(User $admin): void
    {
        $this->status = 'paid';
        $this->processed_by_id = $admin->id;
        $this->processed_date = now();
        $this->save();
    }
} 
