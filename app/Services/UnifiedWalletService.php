<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\GuardianWallet;
use App\Models\StudentWallet;
use App\Models\TeacherWallet;
use App\Models\UnifiedTransaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UnifiedWalletService
{
    /**
     * Get or create wallet for any user type.
     *
     * @param User $user
     * @return StudentWallet|TeacherWallet|GuardianWallet
     */
    public function getWalletForUser(User $user)
    {
        return match ($user->role) {
            'student' => $this->getStudentWallet($user),
            'teacher' => $this->getTeacherWallet($user),
            'guardian' => $this->getGuardianWallet($user),
            default => throw new Exception("Unsupported user role: {$user->role}")
        };
    }

    /**
     * Get or create student wallet.
     *
     * @param User $user
     * @return StudentWallet
     */
    public function getStudentWallet(User $user): StudentWallet
    {
        return StudentWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_spent' => 0,
                'total_refunded' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
                'auto_renew_enabled' => false,
            ]
        );
    }

    /**
     * Get or create teacher wallet.
     *
     * @param User $user
     * @return TeacherWallet
     */
    public function getTeacherWallet(User $user): TeacherWallet
    {
        $wallet = TeacherWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_payouts' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
                'auto_withdrawal_enabled' => false,
            ]
        );

        // Import from existing TeacherEarning if wallet is new
        if ($wallet->wasRecentlyCreated) {
            $wallet->importFromTeacherEarning();
        }

        return $wallet;
    }

    /**
     * Get or create guardian wallet.
     *
     * @param User $user
     * @return GuardianWallet
     */
    public function getGuardianWallet(User $user): GuardianWallet
    {
        return GuardianWallet::firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'total_spent_on_children' => 0,
                'total_refunded' => 0,
                'payment_methods' => [],
                'default_payment_method' => null,
                'auto_fund_children' => false,
            ]
        );
    }

    /**
     * Transfer funds between wallets (currently only guardian to child).
     *
     * @param GuardianWallet $fromWallet
     * @param StudentWallet $toWallet
     * @param float $amount
     * @param string $description
     * @return UnifiedTransaction
     */
    public function transferFunds(GuardianWallet $fromWallet, StudentWallet $toWallet, float $amount, string $description = 'Family transfer'): UnifiedTransaction
    {
        if ($amount <= 0) {
            throw new Exception('Transfer amount must be greater than zero');
        }

        return DB::transaction(function () use ($fromWallet, $toWallet, $amount, $description) {
            return $fromWallet->fundChildWallet($toWallet, $amount, $description);
        });
    }

    /**
     * Get unified transaction history for a user across all their wallets.
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserTransactionHistory(User $user, int $limit = 20)
    {
        $walletConditions = [];

        // Add conditions for each wallet type the user might have
        if ($user->role === 'student' || $user->role === 'guardian') {
            $walletConditions[] = [
                'wallet_type' => StudentWallet::class,
                'wallet_id' => $user->studentWallet?->id ?? 0
            ];
        }

        if ($user->role === 'teacher') {
            $walletConditions[] = [
                'wallet_type' => TeacherWallet::class,
                'wallet_id' => $user->teacherWallet?->id ?? 0
            ];
        }

        if ($user->role === 'guardian') {
            $walletConditions[] = [
                'wallet_type' => GuardianWallet::class,
                'wallet_id' => $user->guardianWallet?->id ?? 0
            ];
        }

        $query = UnifiedTransaction::query();

        foreach ($walletConditions as $i => $condition) {
            if ($i === 0) {
                $query->where($condition);
            } else {
                $query->orWhere($condition);
            }
        }

        return $query->with(['session', 'subscription', 'createdBy'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get family financial summary for guardians.
     *
     * @param User $guardian
     * @return array
     */
    public function getFamilyFinancialSummary(User $guardian): array
    {
        if ($guardian->role !== 'guardian') {
            throw new Exception('User must be a guardian');
        }

        $guardianWallet = $this->getGuardianWallet($guardian);
        return $guardianWallet->getFamilySpendingSummary();
    }

    /**
     * Process session payment for teacher.
     *
     * @param User $teacher
     * @param float $amount
     * @param int $sessionId
     * @param string $description
     * @return UnifiedTransaction
     */
    public function processTeacherSessionPayment(User $teacher, float $amount, int $sessionId, string $description = 'Session payment'): UnifiedTransaction
    {
        if ($teacher->role !== 'teacher') {
            throw new Exception('User must be a teacher');
        }

        $wallet = $this->getTeacherWallet($teacher);
        
        return $wallet->unifiedTransactions()->create([
            'transaction_uuid' => 'TSP-' . date('ymd') . '-' . strtoupper(uniqid()),
            'transaction_type' => 'session_payment',
            'amount' => $amount,
            'currency' => 'NGN',
            'description' => $description,
            'status' => 'completed',
            'session_id' => $sessionId,
            'transaction_date' => now(),
        ]);
    }

    /**
     * Get wallet balance for any user.
     *
     * @param User $user
     * @return float
     */
    public function getUserWalletBalance(User $user): float
    {
        $wallet = $this->getWalletForUser($user);
        return (float) $wallet->balance;
    }

    /**
     * Check if user has sufficient balance for a transaction.
     *
     * @param User $user
     * @param float $amount
     * @return bool
     */
    public function userHasSufficientBalance(User $user, float $amount): bool
    {
        return $this->getUserWalletBalance($user) >= $amount;
    }

    /**
     * Get financial analytics for admin dashboard.
     *
     * @return array
     */
    public function getFinancialAnalytics(): array
    {
        return [
            'total_student_balances' => StudentWallet::sum('balance'),
            'total_teacher_balances' => TeacherWallet::sum('balance'),
            'total_guardian_balances' => GuardianWallet::sum('balance'),
            'total_teacher_earnings' => TeacherWallet::sum('total_earned'),
            'total_family_spending' => GuardianWallet::sum('total_spent_on_children'),
            'total_transactions_today' => UnifiedTransaction::whereDate('transaction_date', today())->count(),
            'total_transaction_volume_today' => UnifiedTransaction::whereDate('transaction_date', today())->sum('amount'),
        ];
    }

    /**
     * Migrate existing teacher earning to new wallet system.
     *
     * @param User $teacher
     * @return TeacherWallet
     */
    public function migrateTeacherEarningToWallet(User $teacher): TeacherWallet
    {
        if ($teacher->role !== 'teacher') {
            throw new Exception('User must be a teacher');
        }

        $wallet = $this->getTeacherWallet($teacher);
        
        Log::info('Teacher wallet migration completed', [
            'teacher_id' => $teacher->id,
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
        ]);

        return $wallet;
    }
}
