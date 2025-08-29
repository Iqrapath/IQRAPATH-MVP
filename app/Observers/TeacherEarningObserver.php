<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\SyncTeacherWalletJob;
use App\Models\TeacherEarning;

class TeacherEarningObserver
{
    /**
     * Handle the TeacherEarning "created" event.
     */
    public function created(TeacherEarning $teacherEarning): void
    {
        // Sync with teacher wallet when earning record is created
        SyncTeacherWalletJob::dispatch($teacherEarning);
    }

    /**
     * Handle the TeacherEarning "updated" event.
     */
    public function updated(TeacherEarning $teacherEarning): void
    {
        // Only sync if financial fields changed
        if ($teacherEarning->wasChanged([
            'wallet_balance',
            'total_earned',
            'total_withdrawn',
            'pending_payouts'
        ])) {
            SyncTeacherWalletJob::dispatch($teacherEarning);
        }
    }
}
