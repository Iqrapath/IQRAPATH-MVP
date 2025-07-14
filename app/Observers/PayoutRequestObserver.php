<?php

namespace App\Observers;

use App\Models\PayoutRequest;
use Illuminate\Support\Facades\Cache;

class PayoutRequestObserver
{
    /**
     * Handle the PayoutRequest "created" event.
     */
    public function created(PayoutRequest $payoutRequest): void
    {
        if ($payoutRequest->status === 'pending') {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the PayoutRequest "updated" event.
     */
    public function updated(PayoutRequest $payoutRequest): void
    {
        // Only clear cache if status changed to or from 'pending'
        if ($payoutRequest->isDirty('status') && 
            ($payoutRequest->status === 'pending' || $payoutRequest->getOriginal('status') === 'pending')) {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the PayoutRequest "deleted" event.
     */
    public function deleted(PayoutRequest $payoutRequest): void
    {
        if ($payoutRequest->status === 'pending') {
            Cache::forget('admin_urgent_actions');
        }
    }
} 