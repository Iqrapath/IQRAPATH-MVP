<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherStatsService;
use App\Services\BookingNotificationService;
use App\Services\TeachingSessionMeetingService;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionsController extends Controller
{
    public function __construct(
        private TeacherStatsService $teacherStatsService,
        private BookingNotificationService $bookingNotificationService,
        private TeachingSessionMeetingService $meetingService
    ) {}

    public function index(Request $request): Response
    {
        $teacherId = $request->user()->id;

        // Get data for all tabs
        $activeStudents = $this->teacherStatsService->getActiveStudents($teacherId);
        $upcomingSessions = $this->teacherStatsService->getUpcomingSessionsForSessionsPage($teacherId);
        $pendingRequests = $this->teacherStatsService->getPendingRequests($teacherId);

        return Inertia::render('teacher/sessions/index', [
            'activeStudents' => $activeStudents,
            'upcomingSessions' => $upcomingSessions,
            'pendingRequests' => $pendingRequests,
        ]);
    }

    /**
     * Accept a pending booking request.
     */
    public function acceptRequest(Request $request, int $bookingId): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $booking = Booking::where('id', $bookingId)
                ->where('teacher_id', $teacherId)
                ->where('status', 'pending')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking request not found or already processed.'
                ], 404);
            }

            // Update booking status to approved
            $booking->update(['status' => 'approved']);

            // Create teaching session
            $teachingSession = $booking->teachingSession()->create([
                'booking_id' => $booking->id,
                'teacher_id' => $teacherId,
                'student_id' => $booking->student_id,
                'subject_id' => $booking->subject_id,
                'session_date' => $booking->booking_date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'status' => 'scheduled',
                'teacher_notes' => $booking->notes,
            ]);

            // Create meeting links (Zoom + Google Meet)
            $meetingData = $this->meetingService->createMeetingLinks($teachingSession, $request->user());
            $this->meetingService->updateSessionWithMeetingData($teachingSession, $meetingData);

            // Send notifications for booking approval
            $this->bookingNotificationService->sendBookingApprovedNotifications($booking);

            return response()->json([
                'success' => true,
                'message' => 'Booking request accepted successfully.',
                'session_id' => $teachingSession->id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to accept booking request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Decline a pending booking request.
     */
    public function declineRequest(Request $request, int $bookingId): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $booking = Booking::where('id', $bookingId)
                ->where('teacher_id', $teacherId)
                ->where('status', 'pending')
                ->first();

            if (!$booking) {
                return response()->json([
                    'success' => false,
                    'message' => 'Booking request not found or already processed.'
                ], 404);
            }

            // Update booking status to declined
            $booking->update(['status' => 'declined']);

            // Send notifications for booking decline
            $this->bookingNotificationService->sendBookingRejectedNotifications($booking, 'Teacher declined the booking request');

            return response()->json([
                'success' => true,
                'message' => 'Booking request declined successfully.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to decline booking request: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get detailed student profile data for modal display.
     */
    public function getStudentProfile(Request $request, int $studentId): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $studentProfile = $this->teacherStatsService->getDetailedStudentProfile($teacherId, $studentId);
            
            if (empty($studentProfile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or not associated with this teacher.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $studentProfile
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch student profile: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get upcoming sessions for the teacher
     */
    public function getUpcomingSessions(Request $request): JsonResponse
    {
        try {
            \Log::info('getUpcomingSessions called', ['user_id' => $request->user()?->id]);
            
            $teacherId = $request->user()->id;
            
            if (!$teacherId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated'
                ], 401);
            }
            
            $sessions = $this->teacherStatsService->getUpcomingSessionsForSessionsPage($teacherId);
            
            \Log::info('Upcoming sessions fetched', ['count' => count($sessions)]);
            
            return response()->json([
                'success' => true,
                'sessions' => $sessions
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getUpcomingSessions', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch upcoming sessions: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get past sessions for the teacher
     */
    public function getPastSessions(Request $request): JsonResponse
    {
        try {
            $teacherId = $request->user()->id;
            
            $sessions = $this->teacherStatsService->getPastSessionsForSessionsPage($teacherId);
            
            return response()->json([
                'success' => true,
                'sessions' => $sessions
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getPastSessions', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch past sessions: ' . $e->getMessage()
            ], 500);
        }
    }
}
