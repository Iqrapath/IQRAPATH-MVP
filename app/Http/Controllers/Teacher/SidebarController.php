<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\BookingNotificationService;
use App\Services\TeachingSessionMeetingService;
use App\Models\Booking;
use App\Models\BookingNotification;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SidebarController extends Controller
{
    public function __construct(
        private BookingNotificationService $bookingNotificationService,
        private TeachingSessionMeetingService $meetingService
    ) {}
    /**
     * Get sidebar data for teacher dashboard.
     */
    public function getSidebarData(Request $request): JsonResponse
    {
        $teacher = $request->user();
        
        try {
            // Get pending session requests (bookings with status 'pending')
            $sessionRequests = $this->getSessionRequests($teacher->id);
            
            // Get recent messages/notifications
            $messages = $this->getRecentMessages($teacher->id);
            
            // Get online students (students who have been active recently)
            $onlineStudents = $this->getOnlineStudents($teacher->id);
            
            // Get counts
            $pendingRequestCount = Booking::where('teacher_id', $teacher->id)
                ->where('status', 'pending')
                ->count();
                
            $unreadMessageCount = BookingNotification::where('user_id', $teacher->id)
                ->where('is_read', false)
                ->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'sessionRequests' => $sessionRequests,
                    'messages' => $messages,
                    'onlineStudents' => $onlineStudents,
                    'pendingRequestCount' => $pendingRequestCount,
                    'unreadMessageCount' => $unreadMessageCount,
                ]
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Failed to fetch teacher sidebar data', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch sidebar data',
                'data' => [
                    'sessionRequests' => [],
                    'messages' => [],
                    'onlineStudents' => [],
                    'pendingRequestCount' => 0,
                    'unreadMessageCount' => 0,
                ]
            ], 500);
        }
    }

    /**
     * Get pending session requests for the teacher.
     */
    private function getSessionRequests(int $teacherId): array
    {
        return Booking::with(['student', 'subject'])
            ->where('teacher_id', $teacherId)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($booking) {
                return [
                    'id' => $booking->id,
                    'student' => [
                        'id' => $booking->student->id,
                        'name' => $booking->student->name,
                        'avatar' => $booking->student->avatar,
                        'is_online' => $this->isUserOnline($booking->student->id),
                    ],
                    'subject' => $booking->subject->template->name ?? $booking->subject->name ?? 'Unknown Subject',
                    'scheduled_at' => $booking->booking_date->format('M d, Y') . ' at ' . $booking->start_time->format('g:i A'),
                    'start_time' => $booking->start_time->format('g:i A'),
                    'end_time' => $booking->end_time->format('g:i A'),
                    'time_ago' => $booking->created_at->diffForHumans(),
                ];
            })
            ->toArray();
    }

    /**
     * Get recent messages/notifications for the teacher.
     */
    private function getRecentMessages(int $teacherId): array
    {
        return BookingNotification::with(['booking.student'])
            ->where('user_id', $teacherId)
            ->where('channel', 'in_app')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                $student = $notification->booking?->student;
                
                return [
                    'id' => $notification->id,
                    'sender' => [
                        'id' => $student?->id ?? 0,
                        'name' => $student?->name ?? 'System',
                        'avatar' => $student?->avatar,
                        'is_online' => $student ? $this->isUserOnline($student->id) : false,
                    ],
                    'message' => $notification->message,
                    'time_ago' => $notification->created_at->diffForHumans(),
                    'is_read' => $notification->is_read,
                ];
            })
            ->toArray();
    }

    /**
     * Get online students who have interacted with this teacher.
     */
    private function getOnlineStudents(int $teacherId): array
    {
        // Get students who have bookings with this teacher and have been active recently
        $recentlyActiveStudents = User::where('role', 'student')
            ->whereHas('studentBookings', function ($query) use ($teacherId) {
                $query->where('teacher_id', $teacherId);
            })
            ->where('last_active_at', '>=', now()->subMinutes(30)) // Active in last 30 minutes
            ->with(['studentProfile'])
            ->limit(10)
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'name' => $student->name,
                    'avatar' => $student->avatar,
                    'is_online' => true,
                ];
            })
            ->toArray();

        return $recentlyActiveStudents;
    }

    /**
     * Check if a user is currently online.
     */
    private function isUserOnline(int $userId): bool
    {
        $user = User::find($userId);
        
        if (!$user || !$user->last_active_at) {
            return false;
        }
        
        // Consider user online if they were active in the last 15 minutes
        return $user->last_active_at->isAfter(now()->subMinutes(15));
    }

    /**
     * Accept a session request.
     */
    public function acceptRequest(Request $request, int $bookingId): JsonResponse
    {
        $teacher = $request->user();
        
        try {
            $booking = Booking::where('id', $bookingId)
                ->where('teacher_id', $teacher->id)
                ->where('status', 'pending')
                ->firstOrFail();

            DB::transaction(function () use ($booking, $teacher) {
                // Update booking status
                $booking->update([
                    'status' => 'approved',
                    'approved_by_id' => $teacher->id,
                    'approved_at' => now(),
                ]);

                // Create teaching session
                $teachingSession = $booking->teachingSession()->create([
                    'teacher_id' => $booking->teacher_id,
                    'student_id' => $booking->student_id,
                    'subject_id' => $booking->subject_id,
                    'session_date' => $booking->booking_date,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => 'scheduled',
                ]);

                // Create meeting links (Zoom + Google Meet)
                $meetingData = $this->meetingService->createMeetingLinks($teachingSession, $teacher);
                $this->meetingService->updateSessionWithMeetingData($teachingSession, $meetingData);
            });

            // Send notifications for booking approval
            $this->bookingNotificationService->sendBookingApprovedNotifications($booking);

            return response()->json([
                'success' => true,
                'message' => 'Session request accepted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to accept session request', [
                'booking_id' => $bookingId,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to accept session request'
            ], 500);
        }
    }

    /**
     * Decline a session request.
     */
    public function declineRequest(Request $request, int $bookingId): JsonResponse
    {
        $teacher = $request->user();
        
        try {
            $booking = Booking::where('id', $bookingId)
                ->where('teacher_id', $teacher->id)
                ->where('status', 'pending')
                ->firstOrFail();

            DB::transaction(function () use ($booking, $teacher) {
                // Update booking status
                $booking->update([
                    'status' => 'rejected',
                    'cancelled_by_id' => $teacher->id,
                    'cancelled_at' => now(),
                ]);
            });

            // Send notifications for booking decline
            $this->bookingNotificationService->sendBookingRejectedNotifications($booking, 'Teacher declined the booking request');

            return response()->json([
                'success' => true,
                'message' => 'Session request declined successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to decline session request', [
                'booking_id' => $bookingId,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to decline session request'
            ], 500);
        }
    }
}
