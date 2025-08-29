<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\TeacherEarning;
use App\Models\TeacherWallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncTeacherWalletJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private TeacherEarning $teacherEarning
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find or create teacher wallet
            $wallet = TeacherWallet::firstOrCreate(
                ['user_id' => $this->teacherEarning->teacher_id],
                [
                    'balance' => $this->teacherEarning->wallet_balance,
                    'total_earned' => $this->teacherEarning->total_earned,
                    'total_withdrawn' => $this->teacherEarning->total_withdrawn,
                    'pending_payouts' => $this->teacherEarning->pending_payouts,
                ]
            );

            // Update wallet with current earning data
            $wallet->update([
                'balance' => $this->teacherEarning->wallet_balance,
                'total_earned' => $this->teacherEarning->total_earned,
                'total_withdrawn' => $this->teacherEarning->total_withdrawn,
                'pending_payouts' => $this->teacherEarning->pending_payouts,
                'last_sync_at' => now(),
            ]);

            Log::info('Teacher wallet synced successfully', [
                'teacher_id' => $this->teacherEarning->teacher_id,
                'wallet_id' => $wallet->id,
                'balance' => $wallet->balance,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync teacher wallet', [
                'teacher_id' => $this->teacherEarning->teacher_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
