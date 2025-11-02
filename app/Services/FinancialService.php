<?php

namespace App\Services;

use App\Models\TeacherEarning;
use App\Models\TeachingSession;
use App\Models\Transaction;
use App\Models\PayoutRequest;
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

        // Create transaction record with currency and exchange rate info
        $transaction = DB::transaction(function () use ($session, $amount, $admin) {
            $booking = $session->booking;
            $currency = $booking && $booking->rate_currency ? $booking->rate_currency : 'NGN';
            $exchangeRate = $booking && $booking->exchange_rate_used ? $booking->exchange_rate_used : 1.0;
            $exchangeRateDate = $booking && $booking->rate_locked_at ? $booking->rate_locked_at : now();
            
            $transaction = Transaction::create([
                'teacher_id' => $session->teacher_id,
                'session_id' => $session->id,
                'transaction_type' => 'session_payment',
                'description' => 'Payment for session #' . $session->session_uuid,
                'amount' => $amount,
                'currency' => $currency,
                'exchange_rate_used' => $exchangeRate,
                'exchange_rate_date' => $exchangeRateDate,
                'status' => 'completed',
                'transaction_date' => now()->format('Y-m-d'),
                'created_by_id' => $admin ? $admin->id : null,
            ]);

            return $transaction;
        });

        return $transaction;
    }

    /**
     * Calculate payment amount for a session using locked rates from booking.
     */
    protected function calculateSessionPayment(TeachingSession $session): float
    {
        // Get actual duration or use scheduled duration if not available
        $durationMinutes = $session->actual_duration_minutes ?? 
            (strtotime($session->end_time) - strtotime($session->start_time)) / 60;

        $durationHours = $durationMinutes / 60;

        // Use locked rates from booking
        $booking = $session->booking;
        if ($booking && $booking->rate_locked_at) {
            // Use the locked rate from booking time
            $hourlyRate = $booking->rate_currency === 'NGN' 
                ? $booking->hourly_rate_ngn 
                : $booking->hourly_rate_usd;
            
            $amount = $durationHours * $hourlyRate;
        } else {
            // Fallback to current teacher rates if no locked rate
            $teacher = $session->teacher;
            $teacherProfile = $teacher->teacherProfile;
            
            $hourlyRate = $teacherProfile->preferred_currency === 'NGN' 
                ? $teacherProfile->hourly_rate_ngn 
                : $teacherProfile->hourly_rate_usd;
            
            $amount = $durationHours * $hourlyRate;
        }

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

        // Get recent transactions with currency info
        $recentTransactions = Transaction::where('teacher_id', $teacher->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function ($transaction) {
                $currency = $transaction->currency ?? 'NGN';
                $currencyService = app(\App\Services\CurrencyService::class);
                
                // Calculate amount in both currencies
                $amountNGN = $currency === 'NGN' ? $transaction->amount : 
                    $currencyService->convertAmount($transaction->amount, $currency, 'NGN');
                $amountUSD = $currency === 'USD' ? $transaction->amount : 
                    $currencyService->convertAmount($transaction->amount, $currency, 'USD');
                
                return [
                    'id' => $transaction->id,
                    'uuid' => $transaction->transaction_uuid,
                    'type' => $transaction->transaction_type,
                    'amount' => $transaction->amount,
                    'currency' => $currency,
                    'amount_ngn' => round($amountNGN, 2),
                    'amount_usd' => round($amountUSD, 2),
                    'exchange_rate_used' => $transaction->exchange_rate_used,
                    'exchange_rate_date' => $transaction->exchange_rate_date,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'date' => $transaction->transaction_date,
                    'session_info' => $transaction->session ? [
                        'uuid' => $transaction->session->session_uuid,
                        'subject' => $transaction->session->subject->name ?? 'Unknown',
                    ] : null,
                ];
            });

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
     * Get real-time teacher earnings data from database tables.
     * This calculates earnings based on actual transactions and payout requests.
     */
    public function getTeacherEarningsRealTime(User $teacher): array
    {
        // Calculate total earned from completed transactions (in NGN)
        $totalEarnedNGN = Transaction::where('teacher_id', $teacher->id)
            ->whereIn('transaction_type', ['session_payment', 'referral_bonus'])
            ->where('status', 'completed')
            ->get()
            ->sum(function ($transaction) {
                $currency = $transaction->currency ?? 'NGN';
                if ($currency === 'NGN') {
                    return $transaction->amount;
                }
                $currencyService = app(\App\Services\CurrencyService::class);
                return $currencyService->convertAmount($transaction->amount, $currency, 'NGN');
            });

        // Calculate total withdrawn from withdrawal transactions (in NGN)
        $totalWithdrawnNGN = Transaction::where('teacher_id', $teacher->id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->get()
            ->sum(function ($transaction) {
                $currency = $transaction->currency ?? 'NGN';
                if ($currency === 'NGN') {
                    return $transaction->amount;
                }
                $currencyService = app(\App\Services\CurrencyService::class);
                return $currencyService->convertAmount($transaction->amount, $currency, 'NGN');
            });

        $totalEarned = $totalEarnedNGN;
        $totalWithdrawn = $totalWithdrawnNGN;

        // Calculate pending payouts from pending payout requests
        $pendingPayouts = PayoutRequest::where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'processing', 'approved'])
            ->sum('amount');

        // Calculate wallet balance (total earned - total withdrawn - pending payouts)
        $walletBalance = $totalEarned - $totalWithdrawn - $pendingPayouts;

        // Get recent transactions for display
        $recentTransactions = Transaction::where('teacher_id', $teacher->id)
            ->with(['session', 'createdBy'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get()
            ->map(function ($transaction) {
                return [
                    'id' => $transaction->id,
                    'uuid' => $transaction->transaction_uuid,
                    'type' => $transaction->transaction_type,
                    'type_display' => $transaction->type_display,
                    'amount' => $transaction->amount,
                    'formatted_amount' => $transaction->formatted_amount,
                    'status' => $transaction->status,
                    'description' => $transaction->description,
                    'date' => $transaction->transaction_date,
                    'session_info' => $transaction->session ? [
                        'uuid' => $transaction->session->session_uuid,
                        'subject' => $transaction->session->subject->name ?? 'Unknown',
                    ] : null,
                ];
            });

        // Get pending payout requests
        $pendingPayoutRequests = PayoutRequest::where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'processing'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($payout) {
                return [
                    'id' => $payout->id,
                    'uuid' => $payout->request_uuid,
                    'amount' => $payout->amount,
                    'formatted_amount' => $payout->formatted_amount,
                    'payment_method' => $payout->payment_method,
                    'payment_method_display' => $payout->payment_method_display,
                    'status' => $payout->status,
                    'status_display' => $payout->status_display,
                    'request_date' => $payout->request_date,
                    'notes' => $payout->notes,
                ];
            });

        // Calculate amounts in both currencies
        $currencyService = app(\App\Services\CurrencyService::class);
        $totalEarnedUSD = $currencyService->convertAmount($totalEarned, 'NGN', 'USD');
        $totalWithdrawnUSD = $currencyService->convertAmount($totalWithdrawn, 'NGN', 'USD');
        $walletBalanceUSD = $currencyService->convertAmount($walletBalance, 'NGN', 'USD');
        $pendingPayoutsUSD = $currencyService->convertAmount($pendingPayouts, 'NGN', 'USD');

        return [
            'wallet_balance' => $walletBalance,
            'wallet_balance_usd' => round($walletBalanceUSD, 2),
            'total_earned' => $totalEarned,
            'total_earned_usd' => round($totalEarnedUSD, 2),
            'total_withdrawn' => $totalWithdrawn,
            'total_withdrawn_usd' => round($totalWithdrawnUSD, 2),
            'pending_payouts' => $pendingPayouts,
            'pending_payouts_usd' => round($pendingPayoutsUSD, 2),
            'recent_transactions' => $recentTransactions,
            'pending_payout_requests' => $pendingPayoutRequests,
            'calculated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get teacher earnings statistics for dashboard.
     */
    public function getTeacherEarningsStats(User $teacher, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        // Earnings in the last N days
        $recentEarnings = Transaction::where('teacher_id', $teacher->id)
            ->whereIn('transaction_type', ['session_payment', 'referral_bonus'])
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Withdrawals in the last N days
        $recentWithdrawals = Transaction::where('teacher_id', $teacher->id)
            ->where('transaction_type', 'withdrawal')
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->sum('amount');

        // Pending payouts count
        $pendingPayoutsCount = PayoutRequest::where('teacher_id', $teacher->id)
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        // Total sessions completed (for earnings calculation)
        $completedSessions = Transaction::where('teacher_id', $teacher->id)
            ->where('transaction_type', 'session_payment')
            ->where('status', 'completed')
            ->count();

        return [
            'recent_earnings' => $recentEarnings,
            'recent_withdrawals' => $recentWithdrawals,
            'pending_payouts_count' => $pendingPayoutsCount,
            'completed_sessions' => $completedSessions,
            'period_days' => $days,
            'period_start' => $startDate->toDateString(),
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

    /**
     * Process subscription payment from student wallet.
     */
    public function processSubscriptionPayment(User $user, \App\Models\Subscription $subscription, string $currency): void
    {
        if ($user->role !== 'student') {
            throw new Exception('Only students can make subscription payments');
        }

        $wallet = $user->studentWallet;
        if (!$wallet) {
            throw new Exception('Student wallet not found');
        }

        $amount = $subscription->amount_paid;
        
        // Convert amount to NGN if needed (wallet balance is stored in NGN)
        $currencyService = app(\App\Services\CurrencyService::class);
        $amountNGN = $currency === 'NGN' ? $amount : 
            $currencyService->convertAmount($amount, $currency, 'NGN');

        // Check wallet balance (stored in NGN)
        if ($wallet->balance < $amountNGN) {
            throw new Exception('Insufficient wallet balance');
        }

        DB::transaction(function () use ($wallet, $amount, $amountNGN, $currency, $subscription) {
            // Debit wallet (in NGN)
            $wallet->decrement('balance', $amountNGN);
            $wallet->increment('total_spent', $amountNGN);

            // Create wallet transaction record
            \App\Models\WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'transaction_type' => 'debit',
                'amount' => $amountNGN,
                'currency' => 'NGN', // Wallet transactions are stored in NGN
                'original_amount' => $amount,
                'original_currency' => $currency,
                'description' => "Subscription payment for {$subscription->plan->name}",
                'reference_type' => 'subscription',
                'reference_id' => $subscription->id,
                'status' => 'completed',
                'transaction_date' => now(),
                'balance_after' => $wallet->balance - $amountNGN,
            ]);
        });
    }

    /**
     * Add funds to user wallet (for bank transfer, virtual account credits, etc.)
     */
    public function addFunds(User $user, float $amount, string $description = 'Wallet funding', ?string $reference = null): Transaction
    {
        return DB::transaction(function () use ($user, $amount, $description, $reference) {
            // Get user's wallet based on role
            $wallet = match($user->role) {
                'student' => $user->studentWallet,
                'teacher' => $user->teacherWallet,
                'guardian' => $user->guardianWallet,
                default => throw new Exception('Invalid user role for wallet funding')
            };

            if (!$wallet) {
                throw new Exception('User wallet not found');
            }

            // Credit wallet
            $wallet->increment('balance', $amount);

            // Create unified transaction record
            $transaction = Transaction::create([
                'user_id' => $user->id,
                'transaction_type' => 'wallet_funding',
                'amount' => $amount,
                'currency' => 'NGN',
                'description' => $description,
                'reference' => $reference,
                'status' => 'completed',
                'transaction_date' => now(),
                'balance_after' => $wallet->fresh()->balance,
            ]);

            Log::info('[Financial Service] Funds added to wallet', [
                'user_id' => $user->id,
                'amount' => $amount,
                'reference' => $reference,
                'new_balance' => $wallet->balance
            ]);

            return $transaction;
        });
    }
} 