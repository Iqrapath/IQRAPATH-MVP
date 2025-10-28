<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\TeachingSession;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service to control access to teaching sessions based on time and user roles
 */
class SessionAccessControlService
{
    /**
     * Check if a user can access a session's meeting link right now
     */
    public function canAccessSession(TeachingSession $session, User $user): array
    {
        // Admins can always access any session for monitoring purposes
        if (in_array($user->role, ['admin', 'super-admin'])) {
            return [
                'can_access' => true,
                'reason' => 'admin_access',
                'message' => 'Admin monitoring access granted',
                'is_admin_monitoring' => true,
            ];
        }

        // Check if user is the teacher or student for this session
        $isTeacher = $session->teacher_id === $user->id;
        $isStudent = $session->student_id === $user->id;

        if (!$isTeacher && !$isStudent) {
            return [
                'can_access' => false,
                'reason' => 'not_authorized',
                'message' => 'You are not authorized to access this session',
                'is_admin_monitoring' => false,
            ];
        }

        // Check session status
        if ($session->status === 'cancelled') {
            return [
                'can_access' => false,
                'reason' => 'session_cancelled',
                'message' => 'This session has been cancelled',
                'is_admin_monitoring' => false,
            ];
        }

        if ($session->status === 'completed') {
            return [
                'can_access' => false,
                'reason' => 'session_completed',
                'message' => 'This session has already been completed',
                'is_admin_monitoring' => false,
            ];
        }

        // Get session date and time
        $sessionDateTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'));
        $sessionEndTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->end_time->format('H:i:s'));
        $now = Carbon::now();

        // Teachers can join 30 minutes early to prepare
        if ($isTeacher) {
            $teacherEarlyAccessTime = $sessionDateTime->copy()->subMinutes(30);
            
            if ($now->lt($teacherEarlyAccessTime)) {
                $minutesUntilAccess = $now->diffInMinutes($teacherEarlyAccessTime, false);
                return [
                    'can_access' => false,
                    'reason' => 'too_early_teacher',
                    'message' => "You can join {$minutesUntilAccess} minutes before the session starts (" . $teacherEarlyAccessTime->format('g:i A') . ")",
                    'early_access_time' => $teacherEarlyAccessTime->toIso8601String(),
                    'session_start_time' => $sessionDateTime->toIso8601String(),
                    'is_admin_monitoring' => false,
                ];
            }
        }

        // Students can join 15 minutes early
        if ($isStudent) {
            $studentEarlyAccessTime = $sessionDateTime->copy()->subMinutes(15);
            
            if ($now->lt($studentEarlyAccessTime)) {
                $minutesUntilAccess = $now->diffInMinutes($studentEarlyAccessTime, false);
                return [
                    'can_access' => false,
                    'reason' => 'too_early_student',
                    'message' => "The class will be available in {$minutesUntilAccess} minutes (" . $studentEarlyAccessTime->format('g:i A') . ")",
                    'early_access_time' => $studentEarlyAccessTime->toIso8601String(),
                    'session_start_time' => $sessionDateTime->toIso8601String(),
                    'is_admin_monitoring' => false,
                ];
            }
        }

        // Check if session has ended (30 minutes grace period after end time)
        $sessionGraceEndTime = $sessionEndTime->copy()->addMinutes(30);
        
        if ($now->gt($sessionGraceEndTime)) {
            return [
                'can_access' => false,
                'reason' => 'session_ended',
                'message' => 'This session has ended. The meeting link is no longer available.',
                'session_end_time' => $sessionEndTime->toIso8601String(),
                'is_admin_monitoring' => false,
            ];
        }

        // Access granted
        return [
            'can_access' => true,
            'reason' => $isTeacher ? 'teacher_access' : 'student_access',
            'message' => 'Access granted to session',
            'is_admin_monitoring' => false,
            'session_status' => $session->status,
            'session_start_time' => $sessionDateTime->toIso8601String(),
            'session_end_time' => $sessionEndTime->toIso8601String(),
        ];
    }

    /**
     * Get the meeting link for a session if user has access
     */
    public function getMeetingLink(TeachingSession $session, User $user): array
    {
        $accessCheck = $this->canAccessSession($session, $user);

        if (!$accessCheck['can_access']) {
            return $accessCheck;
        }

        // Determine which meeting link to return
        $meetingLink = $session->zoom_join_url ?? $session->google_meet_link ?? $session->meeting_link;
        $platform = $session->meeting_platform ?? 'zoom';
        $password = $session->zoom_password ?? $session->meeting_password;

        if (empty($meetingLink)) {
            return [
                'can_access' => false,
                'reason' => 'no_meeting_link',
                'message' => 'Meeting link has not been set up for this session yet. Please contact support.',
                'is_admin_monitoring' => $accessCheck['is_admin_monitoring'],
            ];
        }

        // Log access attempt
        Log::info('Session meeting link accessed', [
            'session_id' => $session->id,
            'user_id' => $user->id,
            'user_role' => $user->role,
            'is_admin_monitoring' => $accessCheck['is_admin_monitoring'],
            'access_time' => now()->toIso8601String(),
        ]);

        // Update session attendance tracking
        $this->updateAttendanceTracking($session, $user);

        return array_merge($accessCheck, [
            'meeting_link' => $meetingLink,
            'meeting_platform' => $platform,
            'meeting_password' => $password,
            'meeting_id' => $session->zoom_meeting_id ?? $session->google_meet_id,
        ]);
    }

    /**
     * Update attendance tracking when user joins
     */
    private function updateAttendanceTracking(TeachingSession $session, User $user): void
    {
        $isTeacher = $session->teacher_id === $user->id;
        $isStudent = $session->student_id === $user->id;

        if ($isTeacher && !$session->teacher_joined_at) {
            $session->update([
                'teacher_joined_at' => now(),
                'teacher_marked_present' => true,
            ]);
            
            Log::info('Teacher joined session', [
                'session_id' => $session->id,
                'teacher_id' => $user->id,
                'joined_at' => now()->toIso8601String(),
            ]);
        }

        if ($isStudent && !$session->student_joined_at) {
            $session->update([
                'student_joined_at' => now(),
                'student_marked_present' => true,
            ]);
            
            Log::info('Student joined session', [
                'session_id' => $session->id,
                'student_id' => $user->id,
                'joined_at' => now()->toIso8601String(),
            ]);
        }

        // Auto-update session status to 'in_progress' when first participant joins
        if ($session->status === 'scheduled') {
            $session->update(['status' => 'in_progress']);
            
            Log::info('Session status updated to in_progress', [
                'session_id' => $session->id,
                'updated_by' => $user->id,
            ]);
        }
    }

    /**
     * Get all active sessions that admins can monitor
     */
    public function getActiveSessionsForMonitoring(): array
    {
        $now = Carbon::now();
        
        // Get sessions that are currently in progress or starting soon (within 30 minutes)
        $sessions = TeachingSession::with(['teacher', 'student', 'subject'])
            ->whereIn('status', ['scheduled', 'in_progress'])
            ->whereDate('session_date', '=', $now->toDateString())
            ->where(function ($query) use ($now) {
                // Sessions that have started or will start within 30 minutes
                $query->whereTime('start_time', '<=', $now->copy()->addMinutes(30)->format('H:i:s'))
                      ->whereTime('end_time', '>=', $now->copy()->subMinutes(30)->format('H:i:s'));
            })
            ->orderBy('start_time')
            ->get();

        return $sessions->map(function ($session) use ($now) {
            $sessionDateTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'));
            $sessionEndTime = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->end_time->format('H:i:s'));
            
            $status = 'upcoming';
            if ($now->gte($sessionDateTime) && $now->lte($sessionEndTime)) {
                $status = 'ongoing';
            } elseif ($now->gt($sessionEndTime)) {
                $status = 'recently_ended';
            }

            return [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'teacher' => [
                    'id' => $session->teacher->id,
                    'name' => $session->teacher->name,
                    'email' => $session->teacher->email,
                ],
                'student' => [
                    'id' => $session->student->id,
                    'name' => $session->student->name,
                    'email' => $session->student->email,
                ],
                'subject' => [
                    'id' => $session->subject->id,
                    'name' => $session->subject->name,
                ],
                'session_date' => $session->session_date->format('Y-m-d'),
                'start_time' => $session->start_time->format('H:i:s'),
                'end_time' => $session->end_time->format('H:i:s'),
                'status' => $session->status,
                'monitoring_status' => $status,
                'meeting_link' => $session->zoom_join_url ?? $session->google_meet_link ?? $session->meeting_link,
                'meeting_platform' => $session->meeting_platform,
                'teacher_joined' => $session->teacher_joined_at ? $session->teacher_joined_at->toIso8601String() : null,
                'student_joined' => $session->student_joined_at ? $session->student_joined_at->toIso8601String() : null,
            ];
        })->toArray();
    }
}

