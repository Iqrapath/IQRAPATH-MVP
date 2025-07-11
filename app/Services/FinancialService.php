<?php

namespace App\Services;

use App\Models\TeacherEarning;
use App\Models\TeachingSession;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class FinancialService
{
    /**
     * Process payment for a completed session.
     */
    public function processSessionPayment(TeachingSession $session, User $admin = null): Transaction
    {
        // Validate that the session is completed
        if ($session->status !== 'completed') {
            throw new Exception('Cannot process payment for a session that is not completed');
        }

        // Check if payment has already been processed
        $existingTransaction = Transaction::where('session_id', $session->id)
            ->where('transaction_type', 'session_payment')
            ->first();
            
        if ($existingTransaction) {
            throw new Exception('Payment has already been processed for this session');
        }

        // Calculate payment amount (this could be based on session duration, fixed rate, etc.)
        $amount = $this->calculateSessionPayment($session);

        // Create transaction record
        $transaction = DB::transaction(function () use ($session, $amount, $admin) {
            $transaction = Transaction::create([
                'teacher_id' => $session->teacher_id,
                'session_id' => $session->id,
                'transaction_type' => 'session_payment',
                'description' => 'Payment for session #' . $session->session_uuid,
                'amount' => $amount,
                'status' => 'completed',
                'transaction_date' => now()->format('Y-m-d'),
                'created_by_id' => $admin ? $admin->id : null,
            ]);

            return $transaction;
        });

        return $transaction;
    }

    /**
     * Calculate payment amount for a session.
     * This could be based on various factors like session duration, teacher rate, etc.
     */
    protected function calculateSessionPayment(TeachingSession $session): float
    {
        // Get actual duration or use scheduled duration if not available
        $durationMinutes = $session->actual_duration_minutes ?? 
            (strtotime($session->end_time) - strtotime($session->start_time)) / 60;

        // For now, use a simple calculation (e.g., ₦1000 per hour)
        $hourlyRate = 1000;
        $amount = ($durationMinutes / 60) * $hourlyRate;

        // Round to 2 decimal places
        return round($amount, 2);
    }

    /**
     * Process a referral bonus for a teacher.
     */
    public function processReferralBonus(User $teacher, float $amount, string $description, User $admin = null): Transaction
    {
        // Create transaction record
        $transaction = DB::transaction(function () use ($teacher, $amount, $description, $admin) {
            $transaction = Transaction::create([
                'teacher_id' => $teacher->id,
                'transaction_type' => 'referral_bonus',
                'description' => $description,
                'amount' => $amount,
                'status' => 'completed',
                'transaction_date' => now()->format('Y-m-d'),
                'created_by_id' => $admin ? $admin->id : null,
            ]);

            return $transaction;
        });

        return $transaction;
    }

    /**
     * Get a teacher's financial summary.
     */
    public function getTeacherFinancialSummary(User $teacher): array
    {
        // Get or create teacher earnings record
        $earnings = TeacherEarning::firstOrCreate(
            ['teacher_id' => $teacher->id],
            [
                'wallet_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_payouts' => 0,
            ]
        );

        // Get recent transactions
        $recentTransactions = Transaction::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        // Get pending payout requests
        $pendingPayouts = $teacher->payoutRequests()
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'wallet_balance' => $earnings->wallet_balance,
            'total_earned' => $earnings->total_earned,
            'total_withdrawn' => $earnings->total_withdrawn,
            'pending_payouts' => $earnings->pending_payouts,
            'recent_transactions' => $recentTransactions,
            'pending_payout_requests' => $pendingPayouts,
        ];
    }

    /**
     * Create a system adjustment transaction.
     */
    public function createSystemAdjustment(User $teacher, float $amount, string $reason, User $admin): Transaction
    {
        if (!$admin->isSuperAdmin()) {
            throw new Exception('Only super admins can create system adjustments');
        }

        // Create transaction record
        $transaction = DB::transaction(function () use ($teacher, $amount, $reason, $admin) {
            $transaction = Transaction::create([
                'teacher_id' => $teacher->id,
                'transaction_type' => 'system_adjustment',
                'description' => 'System adjustment: ' . $reason,
                'amount' => $amount,
                'status' => 'completed',
                'transaction_date' => now()->format('Y-m-d'),
                'created_by_id' => $admin->id,
            ]);

            return $transaction;
        });

        return $transaction;
    }

    /**
     * Create a refund transaction.
     */
    public function createRefund(Transaction $originalTransaction, float $amount, string $reason, User $admin): Transaction
    {
        if (!$admin->isSuperAdmin()) {
            throw new Exception('Only super admins can issue refunds');
        }

        // Validate that the original transaction exists and is completed
        if ($originalTransaction->status !== 'completed') {
            throw new Exception('Cannot refund a transaction that is not completed');
        }

        // Validate that the refund amount is not greater than the original amount
        if ($amount > $originalTransaction->amount) {
            throw new Exception('Refund amount cannot be greater than the original transaction amount');
        }

        // Create transaction record
        $transaction = DB::transaction(function () use ($originalTransaction, $amount, $reason, $admin) {
            $transaction = Transaction::create([
                'teacher_id' => $originalTransaction->teacher_id,
                'session_id' => $originalTransaction->session_id,
                'transaction_type' => 'refund',
                'description' => 'Refund for transaction #' . $originalTransaction->transaction_uuid . ': ' . $reason,
                'amount' => $amount,
                'status' => 'completed',
                'transaction_date' => now()->format('Y-m-d'),
                'created_by_id' => $admin->id,
            ]);

            return $transaction;
        });

        return $transaction;
    }
} 