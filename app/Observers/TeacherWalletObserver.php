<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SyncTeacherEarningJob;
use App\Models\TeacherWallet;

class TeacherWalletObserver
{
    /**
     * Handle the TeacherWallet "created" event.
     */
    public function created(TeacherWallet $teacherWallet): void
    {
        // Sync with teacher earning when wallet is created
        SyncTeacherEarningJob::dispatch($teacherWallet);
    }

    /**
     * Handle the TeacherWallet "updated" event.
     */
    public function updated(TeacherWallet $teacherWallet): void
    {
        // Only sync if financial fields changed and it's not a sync operation
        if (!$teacherWallet->wasChanged('last_sync_at') && 
            $teacherWallet->wasChanged([
                'balance',
                'total_earned',
                'total_withdrawn',
                'pending_payouts'
            ])) {
            SyncTeacherEarningJob::dispatch($teacherWallet);
        }
    }
}
