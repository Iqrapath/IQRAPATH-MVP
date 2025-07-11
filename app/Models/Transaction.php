<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'transaction_uuid',
        'teacher_id',
        'session_id',
        'transaction_type',
        'description',
        'amount',
        'status',
        'transaction_date',
        'created_by_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Generate a unique transaction ID if not provided
            if (!$transaction->transaction_uuid) {
                $transaction->transaction_uuid = 'TRX-' . date('ymd') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);
            }

            // Set transaction date to today if not provided
            if (!$transaction->transaction_date) {
                $transaction->transaction_date = now()->format('Y-m-d');
            }
        });

        static::created(function ($transaction) {
            // Update teacher earnings when a transaction is created
            $teacherEarning = TeacherEarning::firstOrCreate(
                ['teacher_id' => $transaction->teacher_id],
                [
                    'wallet_balance' => 0,
                    'total_earned' => 0,
                    'total_withdrawn' => 0,
                    'pending_payouts' => 0,
                ]
            );

            if ($transaction->status === 'completed') {
                $teacherEarning->updateBalance($transaction);
            }
        });

        static::updated(function ($transaction) {
            // If transaction status changed to completed, update teacher earnings
            if ($transaction->isDirty('status') && $transaction->status === 'completed') {
                $teacherEarning = TeacherEarning::where('teacher_id', $transaction->teacher_id)->first();
                if ($teacherEarning) {
                    $teacherEarning->updateBalance($transaction);
                }
            }
        });
    }

    /**
     * Get the teacher associated with the transaction.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the session associated with the transaction.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(TeachingSession::class, 'session_id');
    }

    /**
     * Get the user who created the transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    /**
     * Get the payout request associated with this transaction.
     */
    public function payoutRequest(): BelongsTo
    {
        return $this->belongsTo(PayoutRequest::class);
    }

    /**
     * Scope a query to only include transactions of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope a query to only include transactions with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include completed transactions.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Format amount with currency symbol.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '₦' . number_format($this->amount, 2);
    }

    /**
     * Get transaction type display name.
     */
    public function getTypeDisplayAttribute(): string
    {
        return match($this->transaction_type) {
            'session_payment' => 'Session Payment',
            'referral_bonus' => 'Referral Bonus',
            'withdrawal' => 'Withdrawal',
            'system_adjustment' => 'System Adjustment',
            'refund' => 'Refund',
            default => ucfirst($this->transaction_type),
        };
    }
} 