<?php

namespace App\Observers;

use App\Models\VerificationRequest;
use Illuminate\Support\Facades\Cache;

class VerificationRequestObserver
{
    /**
     * Handle the VerificationRequest "created" event.
     */
    public function created(VerificationRequest $verificationRequest): void
    {
        if ($verificationRequest->status === 'pending') {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the VerificationRequest "updated" event.
     */
    public function updated(VerificationRequest $verificationRequest): void
    {
        // Only clear cache if status changed to or from 'pending'
        if ($verificationRequest->isDirty('status') && 
            ($verificationRequest->status === 'pending' || $verificationRequest->getOriginal('status') === 'pending')) {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the VerificationRequest "deleted" event.
     */
    public function deleted(VerificationRequest $verificationRequest): void
    {
        if ($verificationRequest->status === 'pending') {
            Cache::forget('admin_urgent_actions');
        }
    }
} 