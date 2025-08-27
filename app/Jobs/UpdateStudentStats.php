<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\TeachingSession;
use App\Models\StudentLearningProgress;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateStudentStats implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $student;

    /**
     * Create a new job instance.
     */
    public function __construct(User $student)
    {
        $this->student = $student;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $studentProfile = $this->student->studentProfile;
            
            if (!$studentProfile) {
                Log::warning('Student profile not found', ['student_id' => $this->student->id]);
                return;
            }

            // Update session statistics
            $this->updateSessionStats($studentProfile);
            
            // Update learning progress statistics
            $this->updateLearningProgressStats($studentProfile);
            
            // Update guardian children count if applicable
            if ($studentProfile->guardian_id) {
                $this->updateGuardianChildrenCount($studentProfile->guardian_id);
            }

            Log::info('Student stats updated successfully', [
                'student_id' => $this->student->id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update student stats', [
                'student_id' => $this->student->id,
                'error' => $e->getMessage(),
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Update session-related statistics.
     */
    private function updateSessionStats(StudentProfile $studentProfile): void
    {
        $sessions = TeachingSession::where('student_id', $studentProfile->user_id)->get();
        
        $totalSessions = $sessions->count();
        $completedSessions = $sessions->where('status', 'completed')->count();
        $attendedSessions = $sessions->where('student_marked_present', true)->count();
        $missedSessions = $sessions->whereIn('status', ['no_show', 'missed'])->count();
        
        // Calculate attendance percentage
        $attendancePercentage = $totalSessions > 0 ? 
            round(($attendedSessions / $totalSessions) * 100, 1) : 0;

        // Calculate average rating
        $averageRating = $sessions->whereNotNull('student_rating')->avg('student_rating');
        $averageRating = $averageRating ? round($averageRating, 1) : 0;

        // We don't need to update the model directly since we use accessors
        // But we could cache these values if needed for performance
        Log::debug('Session stats calculated', [
            'student_id' => $studentProfile->user_id,
            'total_sessions' => $totalSessions,
            'completed_sessions' => $completedSessions,
            'attendance_percentage' => $attendancePercentage,
            'average_rating' => $averageRating,
        ]);
    }

    /**
     * Update learning progress statistics.
     */
    private function updateLearningProgressStats(StudentProfile $studentProfile): void
    {
        $progressRecords = StudentLearningProgress::where('user_id', $studentProfile->user_id)->get();
        
        foreach ($progressRecords as $progress) {
            // Update progress percentage based on completed sessions
            $progress->updateProgressPercentage();
        }

        Log::debug('Learning progress stats updated', [
            'student_id' => $studentProfile->user_id,
            'progress_records_updated' => $progressRecords->count(),
        ]);
    }

    /**
     * Update guardian's children count.
     */
    private function updateGuardianChildrenCount(int $guardianId): void
    {
        $childrenCount = StudentProfile::where('guardian_id', $guardianId)
            ->where('status', 'active')
            ->count();
        
        $guardianProfile = \App\Models\GuardianProfile::where('user_id', $guardianId)->first();
        
        if ($guardianProfile) {
            $guardianProfile->update(['children_count' => $childrenCount]);
            
            Log::debug('Guardian children count updated', [
                'guardian_id' => $guardianId,
                'children_count' => $childrenCount,
            ]);
        }
    }

    /**
     * The job failed to process.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('UpdateStudentStats job failed', [
            'student_id' => $this->student->id,
            'exception' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);
    }
}

