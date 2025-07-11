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
                $payoutRequest->request_uuid = 'POUT-' . date('ymd') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
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
     * Approve the payout request.
     */
    public function approve(User $admin): void
    {
        $this->status = 'approved';
        $this->processed_by_id = $admin->id;
        $this->processed_date = now();
        $this->save();
    }

    /**
     * Decline the payout request.
     */
    public function decline(User $admin, string $reason = null): void
    {
        $this->status = 'declined';
        $this->processed_by_id = $admin->id;
        $this->processed_date = now();
        
        if ($reason) {
            $this->notes = $reason;
        }
        
        $this->save();
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