<?php

namespace App\Jobs;

use App\Models\FinancialSetting;
use App\Models\PayoutRequest;
use App\Models\TeacherEarning;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessAutoPayouts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get auto-payout threshold from settings
        $threshold = (float) FinancialSetting::get('auto_payout_threshold', 50000);
        
        // Check if auto-payouts are enabled (threshold > 0)
        if ($threshold <= 0) {
            Log::info('[Auto Payout] Auto-payouts disabled (threshold is 0)');
            return;
        }

        Log::info('[Auto Payout] Processing auto-payouts', [
            'threshold' => $threshold,
            'started_at' => now()->toDateTimeString(),
        ]);

        // Find teachers with balance >= threshold and no pending payouts
        $eligibleTeachers = User::where('role', 'teacher')
            ->whereHas('earnings', function ($query) use ($threshold) {
                $query->where('wallet_balance', '>=', $threshold);
            })
            ->whereDoesntHave('payoutRequests', function ($query) {
                $query->whereIn('status', ['pending', 'processing', 'approved']);
            })
            ->get();

        $processedCount = 0;
        $failedCount = 0;

        foreach ($eligibleTeachers as $teacher) {
            try {
                $this->createAutoPayoutRequest($teacher);
                $processedCount++;
            } catch (\Exception $e) {
                $failedCount++;
                Log::error('[Auto Payout] Failed to create auto-payout for teacher', [
                    'teacher_id' => $teacher->id,
                    'teacher_name' => $teacher->name,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('[Auto Payout] Auto-payout processing completed', [
            'eligible_teachers' => $eligibleTeachers->count(),
            'processed' => $processedCount,
            'failed' => $failedCount,
            'completed_at' => now()->toDateTimeString(),
        ]);
    }

    /**
     * Create an automatic payout request for a teacher.
     */
    protected function createAutoPayoutRequest(User $teacher): void
    {
        DB::transaction(function () use ($teacher) {
            $earnings = $teacher->earnings;
            $amount = $earnings->wallet_balance;

            // Get teacher's default payment method
            $defaultPaymentMethod = \App\Models\PaymentMethod::where('user_id', $teacher->id)
                ->where('is_default', true)
                ->where('is_active', true)
                ->first();

            if (!$defaultPaymentMethod) {
                // Use first available payment method
                $defaultPaymentMethod = \App\Models\PaymentMethod::where('user_id', $teacher->id)
                    ->where('is_active', true)
                    ->first();
            }

            if (!$defaultPaymentMethod) {
                throw new \Exception('No payment method available for auto-payout');
            }

            // Create payout request
            $payoutRequest = PayoutRequest::create([
                'request_uuid' => 'PR-' . strtoupper(uniqid()),
                'user_id' => $teacher->id,
                'user_type' => 'teacher',
                'amount' => $amount,
                'currency' => 'NGN',
                'payment_method' => $defaultPaymentMethod->type,
                'payment_details' => [
                    'bank_name' => $defaultPaymentMethod->bank_name ?? null,
                    'account_number' => $defaultPaymentMethod->account_number ?? null,
                    'account_name' => $defaultPaymentMethod->account_name ?? null,
                ],
                'status' => 'pending',
                'request_date' => now(),
                'notes' => 'Automatic payout request (threshold reached)',
            ]);

            Log::info('[Auto Payout] Created auto-payout request', [
                'payout_id' => $payoutRequest->id,
                'teacher_id' => $teacher->id,
                'teacher_name' => $teacher->name,
                'amount' => $amount,
                'payment_method' => $defaultPaymentMethod->type,
            ]);

            // Send notification to teacher
            try {
                $notificationService = app(\App\Services\NotificationService::class);
                $notificationService->createNotification([
                    'title' => 'Auto-Payout Request Created',
                    'body' => "An automatic payout request of ₦" . number_format($amount, 2) . " has been created for you.",
                    'type' => 'payout',
                    'sender_type' => 'system',
                    'sender_id' => null,
                ]);
                
                $notificationService->addRecipients($notificationService->getLastCreatedNotification(), [
                    'user_ids' => [$teacher->id],
                    'channels' => ['in-app', 'email'],
                ]);
            } catch (\Exception $e) {
                Log::error('[Auto Payout] Failed to send notification', [
                    'teacher_id' => $teacher->id,
                    'error' => $e->getMessage(),
                ]);
            }
        });
    }
}
