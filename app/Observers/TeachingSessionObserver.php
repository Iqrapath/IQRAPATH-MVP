<?php

namespace App\Observers;

use App\Models\TeachingSession;
use Illuminate\Support\Facades\Cache;

class TeachingSessionObserver
{
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