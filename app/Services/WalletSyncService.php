<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\TeacherWallet;
use App\Models\UnifiedTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * WalletSyncService - Centralized wallet synchronization
 * 
 * This service ensures wallet balances stay consistent across:
 * - Teacher header display
 * - Earnings page
 * - Transaction processing
 * - Payout requests
 */
class WalletSyncService
{
    /**
     * Sync teacher wallet balance from transactions.
     * 
     * @param User $teacher
     * @return array Updated wallet data
     */
    public function syncTeacherWallet(User $teacher): array
    {
        $teacherWallet = $teacher->teacherWallet;

        if (!$teacherWallet) {
            Log::warning('Teacher wallet not found', ['teacher_id' => $teacher->id]);
            return [
                'balance' => 0.0,
                'total_earned' => 0.0,
                'total_withdrawn' => 0.0,
                'pending_payouts' => 0.0
            ];
        }

        return DB::transaction(function () use ($teacher, $teacherWallet) {
            // Calculate totals from unified transactions
            $unifiedTransactions = UnifiedTransaction::where('wallet_type', 'App\\Models\\TeacherWallet')
                ->where('wallet_id', $teacherWallet->id)
                ->get();

            $totalEarned = $unifiedTransactions
                ->whereIn('transaction_type', ['session_payment', 'credit', 'bonus', 'refund'])
                ->where('status', 'completed')
                ->sum('amount');

            $totalWithdrawn = $unifiedTransactions
                ->whereIn('transaction_type', ['withdrawal', 'debit'])
                ->where('status', 'completed')
                ->sum('amount');

            $pendingPayouts = $unifiedTransactions
                ->whereIn('transaction_type', ['withdrawal'])
                ->where('status', 'pending')
                ->sum('amount');

            $currentBalance = $totalEarned - $totalWithdrawn;

            // Update wallet with calculated values
            $teacherWallet->update([
                'total_earned' => $totalEarned,
                'total_withdrawn' => $totalWithdrawn,
                'pending_payouts' => $pendingPayouts,
                'balance' => $currentBalance,
                'last_sync_at' => now(),
            ]);

            // Also sync with teacher_earnings table for backward compatibility
            \App\Models\TeacherEarning::updateOrCreate(
                ['teacher_id' => $teacher->id],
                [
                    'wallet_balance' => $currentBalance,
                    'total_earned' => $totalEarned,
                    'total_withdrawn' => $totalWithdrawn,
                    'pending_payouts' => $pendingPayouts,
                ]
            );

            Log::info('Teacher wallet synced', [
                'teacher_id' => $teacher->id,
                'balance' => $currentBalance,
                'total_earned' => $totalEarned
            ]);

            return [
                'balance' => (float) $currentBalance,
                'total_earned' => (float) $totalEarned,
                'total_withdrawn' => (float) $totalWithdrawn,
                'pending_payouts' => (float) $pendingPayouts
            ];
        });
    }

    /**
     * Get teacher wallet data without syncing.
     * 
     * @param User $teacher
     * @return array Current wallet data
     */
    public function getTeacherWalletData(User $teacher): array
    {
        $teacherWallet = $teacher->teacherWallet;

        if (!$teacherWallet) {
            return [
                'balance' => 0.0,
                'total_earned' => 0.0,
                'total_withdrawn' => 0.0,
                'pending_payouts' => 0.0
            ];
        }

        return [
            'balance' => (float) $teacherWallet->balance,
            'total_earned' => (float) $teacherWallet->total_earned,
            'total_withdrawn' => (float) $teacherWallet->total_withdrawn,
            'pending_payouts' => (float) $teacherWallet->pending_payouts
        ];
    }

    /**
     * Check if wallet needs syncing.
     * 
     * @param User $teacher
     * @param int $hoursThreshold Hours since last sync
     * @return bool
     */
    public function needsSync(User $teacher, int $hoursThreshold = 24): bool
    {
        $teacherWallet = $teacher->teacherWallet;

        if (!$teacherWallet) {
            return false;
        }

        if (!$teacherWallet->last_sync_at) {
            return true;
        }

        return $teacherWallet->last_sync_at->lt(now()->subHours($hoursThreshold));
    }

    /**
     * Sync all teacher wallets (for scheduled jobs).
     * 
     * @return array Summary of sync results
     */
    public function syncAllTeacherWallets(): array
    {
        $teachers = User::where('role', 'teacher')
            ->whereHas('teacherWallet')
            ->get();

        $synced = 0;
        $failed = 0;

        foreach ($teachers as $teacher) {
            try {
                $this->syncTeacherWallet($teacher);
                $synced++;
            } catch (\Exception $e) {
                Log::error('Failed to sync teacher wallet', [
                    'teacher_id' => $teacher->id,
                    'error' => $e->getMessage()
                ]);
                $failed++;
            }
        }

        return [
            'total' => $teachers->count(),
            'synced' => $synced,
            'failed' => $failed
        ];
    }
}
