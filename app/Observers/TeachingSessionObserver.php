<?php

namespace App\Observers;

use App\Models\TeachingSession;
use App\Services\FinancialService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class TeachingSessionObserver
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Handle the TeachingSession "created" event.
     */
    public function created(TeachingSession $teachingSession): void
    {
        if ($teachingSession->teacher_id === null || $teachingSession->status === 'pending_teacher') {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the TeachingSession "updated" event.
     */
    public function updated(TeachingSession $teachingSession): void
    {
        // Clear cache if teacher_id was assigned or unassigned
        if ($teachingSession->isDirty('teacher_id') && 
            ($teachingSession->teacher_id === null || $teachingSession->getOriginal('teacher_id') === null)) {
            Cache::forget('admin_urgent_actions');
        }

        // Clear cache if status changed to or from 'pending_teacher'
        if ($teachingSession->isDirty('status') && 
            ($teachingSession->status === 'pending_teacher' || $teachingSession->getOriginal('status') === 'pending_teacher')) {
            Cache::forget('admin_urgent_actions');
        }

        // Handle session completion - process payment
        if ($teachingSession->isDirty('status') && $teachingSession->status === 'completed') {
            $this->handleSessionCompletion($teachingSession);
        }
    }

    /**
     * Handle session completion and process payment.
     */
    protected function handleSessionCompletion(TeachingSession $session): void
    {
        try {
            // Only process payment if session has a teacher and student
            if (!$session->teacher_id || !$session->student_id) {
                Log::warning('Cannot process payment for session without teacher or student', [
                    'session_id' => $session->id,
                    'teacher_id' => $session->teacher_id,
                    'student_id' => $session->student_id
                ]);
                return;
            }

            // Process the session payment
            $transaction = $this->financialService->processSessionPayment($session);
            
            Log::info('Session payment processed successfully', [
                'session_id' => $session->id,
                'transaction_id' => $transaction->id,
                'amount' => $transaction->amount
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to process session payment', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Handle the TeachingSession "deleted" event.
     */
    public function deleted(TeachingSession $teachingSession): void
    {
        if ($teachingSession->teacher_id === null || $teachingSession->status === 'pending_teacher') {
            Cache::forget('admin_urgent_actions');
        }
    }
} 