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

class SyncTeacherEarningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private TeacherWallet $teacherWallet
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Find or create teacher earning record
            $earning = TeacherEarning::firstOrCreate(
                ['teacher_id' => $this->teacherWallet->user_id],
                [
                    'wallet_balance' => $this->teacherWallet->balance,
                    'total_earned' => $this->teacherWallet->total_earned,
                    'total_withdrawn' => $this->teacherWallet->total_withdrawn,
                    'pending_payouts' => $this->teacherWallet->pending_payouts,
                ]
            );

            // Update earning record with current wallet data
            $earning->update([
                'wallet_balance' => $this->teacherWallet->balance,
                'total_earned' => $this->teacherWallet->total_earned,
                'total_withdrawn' => $this->teacherWallet->total_withdrawn,
                'pending_payouts' => $this->teacherWallet->pending_payouts,
            ]);

            Log::info('Teacher earning synced successfully', [
                'teacher_id' => $this->teacherWallet->user_id,
                'earning_id' => $earning->id,
                'balance' => $earning->wallet_balance,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync teacher earning', [
                'teacher_id' => $this->teacherWallet->user_id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
