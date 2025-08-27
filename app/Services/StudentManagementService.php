<?php

namespace App\Services;

use App\Models\User;
use App\Models\StudentProfile;
use App\Events\StudentStatusChanged;
use App\Jobs\UpdateStudentStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentManagementService
{
    /**
     * Approve a student account.
     */
    public function approveStudent(User $student): bool
    {
        try {
            DB::beginTransaction();

            $oldStatus = $student->studentProfile->status ?? 'inactive';
            
            // Update student profile status
            $student->studentProfile()->update([
                'status' => 'active'
            ]);

            // Update user email verification if not verified
            if (!$student->email_verified_at) {
                $student->update([
                    'email_verified_at' => now()
                ]);
            }

            // Dispatch events and jobs
            event(new StudentStatusChanged($student, $oldStatus, 'active'));
            UpdateStudentStats::dispatch($student);

            DB::commit();

            Log::info('Student approved', [
                'student_id' => $student->id,
                'student_email' => $student->email,
                'approved_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to approve student', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Suspend a student account.
     */
    public function suspendStudent(User $student): bool
    {
        try {
            DB::beginTransaction();

            $oldStatus = $student->studentProfile->status ?? 'active';
            
            // Update student profile status
            $student->studentProfile()->update([
                'status' => 'suspended'
            ]);

            // Cancel upcoming sessions
            $this->cancelUpcomingSessions($student);

            // Dispatch events and jobs
            event(new StudentStatusChanged($student, $oldStatus, 'suspended'));
            UpdateStudentStats::dispatch($student);

            DB::commit();

            Log::info('Student suspended', [
                'student_id' => $student->id,
                'student_email' => $student->email,
                'suspended_by' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to suspend student', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update student contact information.
     */
    public function updateContactInfo(User $student, array $data): bool
    {
        try {
            DB::beginTransaction();

            // Update user table fields
            $updateData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'location' => $data['location'] ?? null,
                'role' => $data['role'],
                'account_status' => $data['account_status'], // Update the user account_status field
            ];
            
            // Debug: Log what we're trying to update
            Log::info('Attempting to update user with data:', [
                'user_id' => $student->id,
                'update_data' => $updateData,
                'current_location' => $student->location,
                'new_location' => $data['location'],
            ]);
            
            // Check current values before update
            Log::info('Before update - Current user values:', [
                'user_id' => $student->id,
                'current_location' => $student->location,
                'current_name' => $student->name,
                'current_email' => $student->email,
                'current_phone' => $student->phone,
                'current_role' => $student->role,
            ]);
            
            $result = $student->update($updateData);
            
            // Check if update returned true
            Log::info('Update method result:', [
                'update_returned' => $result,
                'update_data_count' => count($updateData),
            ]);
            
            // Check for any database errors
            if (DB::getQueryLog()) {
                Log::info('Database query log:', DB::getQueryLog());
            }
            
            // Debug: Log the update result
            Log::info('User update result:', [
                'user_id' => $student->id,
                'update_success' => $result,
                'raw_update_data' => $updateData,
            ]);
            
            // Check if the update actually worked by querying the database directly
            $updatedUser = \App\Models\User::where('id', $student->id)->first();
            Log::info('Direct database query after update:', [
                'user_id' => $updatedUser->id,
                'location' => $updatedUser->location,
                'name' => $updatedUser->name,
                'email' => $updatedUser->email,
                'phone' => $updatedUser->phone,
                'role' => $updatedUser->role,
            ]);
            
            // Additional debugging: Check if the model was actually updated
            $student->refresh();
            Log::info('User model after refresh:', [
                'user_id' => $student->id,
                'location' => $student->location,
                'name' => $student->name,
                'email' => $student->email,
                'phone' => $student->phone,
                'role' => $student->role,
            ]);
            
            // Direct database query to verify the update
            $directUser = \App\Models\User::find($student->id);
            Log::info('Direct database query result:', [
                'user_id' => $directUser->id,
                'location' => $directUser->location,
                'name' => $directUser->name,
                'email' => $directUser->email,
                'phone' => $directUser->phone,
                'role' => $directUser->role,
            ]);

            // Update profile status based on role
            if ($data['role'] === 'student' && $student->studentProfile) {
                $student->studentProfile()->update([
                    'status' => $data['account_status']
                ]);
            } elseif ($data['role'] === 'guardian' && $student->guardianProfile) {
                $student->guardianProfile()->update([
                    'status' => $data['account_status']
                ]);
            }

            // Log the change
            Log::info('Student contact info updated', [
                'student_id' => $student->id,
                'updated_by' => auth()->id(),
                'changes' => $data,
            ]);

            DB::commit();
            
            // Verify the commit worked by checking the database again
            $finalUser = \App\Models\User::where('id', $student->id)->first();
            Log::info('Final database state after commit:', [
                'user_id' => $finalUser->id,
                'location' => $finalUser->location,
                'name' => $finalUser->name,
                'email' => $finalUser->email,
                'phone' => $finalUser->phone,
                'role' => $finalUser->role,
            ]);
            
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update student contact info', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Update student learning preferences.
     */
    public function updatePreferences(User $student, array $data): bool
    {
        try {
            DB::beginTransaction();

            $student->studentProfile()->update([
                'subjects_of_interest' => $data['subjects_of_interest'] ?? null,
                'preferred_learning_times' => $data['preferred_learning_times'] ?? null,
                'learning_goals' => $data['learning_goals'] ?? null,
                'teaching_mode' => $data['teaching_mode'] ?? null,
                'additional_notes' => $data['additional_notes'] ?? null,
            ]);

            // Log the change
            Log::info('Student preferences updated', [
                'student_id' => $student->id,
                'updated_by' => auth()->id(),
                'changes' => $data,
            ]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update student preferences', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get student statistics for dashboard.
     */
    public function getStudentStats(): array
    {
        $totalStudents = User::where('role', 'student')->count();
        $activeStudents = User::where('role', 'student')
            ->whereHas('studentProfile', function ($q) {
                $q->where('status', 'active');
            })->count();
        $suspendedStudents = User::where('role', 'student')
            ->whereHas('studentProfile', function ($q) {
                $q->where('status', 'suspended');
            })->count();
        $newStudentsThisMonth = User::where('role', 'student')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            'total' => $totalStudents,
            'active' => $activeStudents,
            'suspended' => $suspendedStudents,
            'new_this_month' => $newStudentsThisMonth,
            'activity_rate' => $totalStudents > 0 ? round(($activeStudents / $totalStudents) * 100, 1) : 0,
        ];
    }

    /**
     * Get recent students for dashboard.
     */
    public function getRecentStudents(int $limit = 10): array
    {
        return User::where('role', 'student')
            ->with(['studentProfile'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'status' => $user->studentProfile->status ?? 'inactive',
                    'registration_date' => $user->created_at->format('M j, Y'),
                ];
            })
            ->toArray();
    }

    /**
     * Cancel upcoming sessions for a student.
     */
    private function cancelUpcomingSessions(User $student): void
    {
        $student->studentProfile->teachingSessions()
            ->where('status', 'scheduled')
            ->where('session_date', '>=', now()->format('Y-m-d'))
            ->update([
                'status' => 'cancelled',
                'teacher_notes' => 'Session cancelled due to student account suspension',
            ]);
    }

    /**
     * Calculate student engagement metrics.
     */
    public function calculateEngagementMetrics(User $student): array
    {
        $studentProfile = $student->studentProfile;
        
        if (!$studentProfile) {
            return [
                'attendance_rate' => 0,
                'completion_rate' => 0,
                'average_rating' => 0,
                'total_sessions' => 0,
            ];
        }

        $totalSessions = $studentProfile->total_sessions_count;
        $completedSessions = $studentProfile->completed_sessions_count;
        $attendanceRate = $studentProfile->attendance_percentage;
        $averageRating = $studentProfile->average_engagement;
        
        $completionRate = $totalSessions > 0 ? 
            round(($completedSessions / $totalSessions) * 100, 1) : 0;

        return [
            'attendance_rate' => $attendanceRate,
            'completion_rate' => $completionRate,
            'average_rating' => $averageRating,
            'total_sessions' => $totalSessions,
        ];
    }

    /**
     * Bulk update student statuses.
     */
    public function bulkUpdateStatus(array $studentIds, string $status): int
    {
        $updated = 0;
        
        foreach ($studentIds as $studentId) {
            $student = User::find($studentId);
            if ($student && $student->role === 'student') {
                if ($status === 'active') {
                    $this->approveStudent($student);
                } elseif ($status === 'suspended') {
                    $this->suspendStudent($student);
                }
                $updated++;
            }
        }

        return $updated;
    }
}

