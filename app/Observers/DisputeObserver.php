<?php

namespace App\Observers;

use App\Models\Dispute;
use Illuminate\Support\Facades\Cache;

class DisputeObserver
{
    /**
     * Handle the Dispute "created" event.
     */
    public function created(Dispute $dispute): void
    {
        if ($dispute->status === 'reported') {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the Dispute "updated" event.
     */
    public function updated(Dispute $dispute): void
    {
        // Only clear cache if status changed to or from 'reported'
        if ($dispute->isDirty('status') && 
            ($dispute->status === 'reported' || $dispute->getOriginal('status') === 'reported')) {
            Cache::forget('admin_urgent_actions');
        }
    }

    /**
     * Handle the Dispute "deleted" event.
     */
    public function deleted(Dispute $dispute): void
    {
        if ($dispute->status === 'reported') {
            Cache::forget('admin_urgent_actions');
        }
    }
} 