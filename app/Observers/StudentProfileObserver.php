<?php

namespace App\Observers;

use App\Models\StudentProfile;
use App\Models\User;
use App\Events\StudentStatusChanged;
use App\Jobs\UpdateStudentStats;
use Illuminate\Support\Facades\Log;

class StudentProfileObserver
{
    /**
     * Handle the StudentProfile "created" event.
     */
    public function created(StudentProfile $studentProfile): void
    {
        Log::info('Student profile created', [
            'student_id' => $studentProfile->user_id,
            'guardian_id' => $studentProfile->guardian_id,
        ]);

        // Update guardian children count if applicable
        if ($studentProfile->guardian_id) {
            $this->updateGuardianChildrenCount($studentProfile->guardian_id);
        }

        // Dispatch job to calculate initial stats
        $student = User::find($studentProfile->user_id);
        if ($student) {
            UpdateStudentStats::dispatch($student);
        }
    }

    /**
     * Handle the StudentProfile "updated" event.
     */
    public function updated(StudentProfile $studentProfile): void
    {
        // Check if status changed
        if ($studentProfile->isDirty('status')) {
            $oldStatus = $studentProfile->getOriginal('status');
            $newStatus = $studentProfile->status;
            
            $student = User::find($studentProfile->user_id);
            if ($student) {
                event(new StudentStatusChanged($student, $oldStatus, $newStatus));
                
                Log::info('Student status changed', [
                    'student_id' => $studentProfile->user_id,
                    'old_status' => $oldStatus,
                    'new_status' => $newStatus,
                    'changed_by' => auth()->id(),
                ]);
            }
        }

        // Check if guardian changed
        if ($studentProfile->isDirty('guardian_id')) {
            $oldGuardianId = $studentProfile->getOriginal('guardian_id');
            $newGuardianId = $studentProfile->guardian_id;
            
            // Update old guardian's children count
            if ($oldGuardianId) {
                $this->updateGuardianChildrenCount($oldGuardianId);
            }
            
            // Update new guardian's children count
            if ($newGuardianId) {
                $this->updateGuardianChildrenCount($newGuardianId);
            }
            
            Log::info('Student guardian changed', [
                'student_id' => $studentProfile->user_id,
                'old_guardian_id' => $oldGuardianId,
                'new_guardian_id' => $newGuardianId,
            ]);
        }

        // Update stats if any significant changes occurred
        if ($studentProfile->isDirty(['status', 'guardian_id', 'subjects_of_interest', 'preferred_learning_times'])) {
            $student = User::find($studentProfile->user_id);
            if ($student) {
                UpdateStudentStats::dispatch($student);
            }
        }
    }

    /**
     * Handle the StudentProfile "deleted" event.
     */
    public function deleted(StudentProfile $studentProfile): void
    {
        Log::info('Student profile deleted', [
            'student_id' => $studentProfile->user_id,
            'guardian_id' => $studentProfile->guardian_id,
        ]);

        // Update guardian children count if applicable
        if ($studentProfile->guardian_id) {
            $this->updateGuardianChildrenCount($studentProfile->guardian_id);
        }
    }

    /**
     * Handle the StudentProfile "restored" event.
     */
    public function restored(StudentProfile $studentProfile): void
    {
        Log::info('Student profile restored', [
            'student_id' => $studentProfile->user_id,
        ]);

        // Update guardian children count if applicable
        if ($studentProfile->guardian_id) {
            $this->updateGuardianChildrenCount($studentProfile->guardian_id);
        }

        // Dispatch job to recalculate stats
        $student = User::find($studentProfile->user_id);
        if ($student) {
            UpdateStudentStats::dispatch($student);
        }
    }

    /**
     * Update guardian's children count.
     */
    private function updateGuardianChildrenCount(int $guardianId): void
    {
        try {
            $childrenCount = StudentProfile::where('guardian_id', $guardianId)
                ->where('status', 'active')
                ->count();
            
            $guardianProfile = \App\Models\GuardianProfile::where('user_id', $guardianId)->first();
            
            if ($guardianProfile) {
                $guardianProfile->update(['children_count' => $childrenCount]);
                
                Log::debug('Guardian children count updated via observer', [
                    'guardian_id' => $guardianId,
                    'children_count' => $childrenCount,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to update guardian children count', [
                'guardian_id' => $guardianId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

