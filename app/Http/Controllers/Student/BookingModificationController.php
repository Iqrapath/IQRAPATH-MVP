<?php

declare(strict_types=1);

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\CreateRescheduleRequest;
use App\Http\Requests\Student\CreateRebookRequest;
use App\Http\Requests\Student\ApproveModificationRequest;
use App\Http\Requests\Student\RejectModificationRequest;
use App\Models\Booking;
use App\Models\BookingModification;
use App\Models\TeacherAvailability;
use App\Models\User;
use App\Services\BookingModificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookingModificationController extends Controller
{
    public function __construct(
        private BookingModificationService $modificationService
    ) {}

    /**
     * Display student's booking modifications
     */
    public function index(Request $request): Response
    {
        $student = $request->user();
        
        $modifications = BookingModification::where('student_id', $student->id)
            ->with(['booking.teacher.teacherProfile', 'booking.subject', 'teacher.teacherProfile'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('student/modifications/index', [
            'modifications' => $modifications,
        ]);
    }

    /**
     * Show reschedule class page (calendar and time selection)
     */
    public function rescheduleClass(Request $request): Response
    {
        // Clear any existing reschedule session when starting fresh
        if ($request->isMethod('get') && $request->has('booking_id')) {
            $request->session()->forget('reschedule_session');
        }

        return $this->renderRescheduleClass($request);
    }

    /**
     * Show reschedule session details page
     */
    public function rescheduleSessionDetails(Request $request): Response
    {
        return $this->renderRescheduleSessionDetails($request);
    }

    /**
     * Show reschedule pricing payment page
     */
    public function reschedulePricingPayment(Request $request): Response
    {
        return $this->renderReschedulePricingPayment($request);
    }

    /**
     * Submit reschedule request
     */
    public function submitReschedule(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'teacher_id' => 'required|exists:users,id',
            'new_booking_date' => 'required|date|after:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'new_duration_minutes' => 'required|integer|min:30|max:480',
            'reschedule_reason' => 'required|string|max:1000',
        ]);

        try {
            $student = $request->user();
            
            $modification = $this->modificationService->createRescheduleRequest(
                $request->booking_id,
                $student->id,
                [
                    'new_booking_date' => $request->new_booking_date,
                    'new_start_time' => $request->new_start_time,
                    'new_end_time' => $request->new_end_time,
                    'new_duration_minutes' => $request->new_duration_minutes,
                    'meeting_platform' => 'zoom',
                ],
                $request->reschedule_reason
            );

            // Clear reschedule session data
            $this->clearRescheduleSession($request);

            return response()->json([
                'success' => true,
                'message' => 'Reschedule request submitted successfully!',
                'modification' => $modification->load(['booking', 'teacher']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Create rebook request
     */
    public function rebook(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'teacher_id' => 'required|exists:users,id',
            'new_booking_date' => 'required|date|after:today',
            'new_start_time' => 'required|date_format:H:i',
            'new_end_time' => 'required|date_format:H:i|after:new_start_time',
            'new_duration_minutes' => 'required|integer|min:30|max:480',
            'rebook_reason' => 'required|string|max:1000',
        ]);

        try {
            $student = $request->user();
            
            $modification = $this->modificationService->createRebookRequest(
                $request->booking_id,
                $student->id,
                [
                    'new_booking_date' => $request->new_booking_date,
                    'new_start_time' => $request->new_start_time,
                    'new_end_time' => $request->new_end_time,
                    'new_duration_minutes' => $request->new_duration_minutes,
                    'meeting_platform' => 'zoom',
                ],
                $request->rebook_reason
            );

            return response()->json([
                'success' => true,
                'message' => 'Rebook request submitted successfully!',
                'modification' => $modification->load(['booking', 'teacher']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Approve modification request
     */
    public function approveModification(ApproveModificationRequest $request): JsonResponse
    {
        try {
            $student = $request->user();
            $modification = BookingModification::findOrFail($request->modification_id);

            if ($modification->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to approve this modification.',
                ], 403);
            }

            $this->modificationService->approveModification($modification, $student->id);

            return response()->json([
                'success' => true,
                'message' => 'Modification request approved successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject modification request
     */
    public function rejectModification(RejectModificationRequest $request): JsonResponse
    {
        try {
            $student = $request->user();
            $modification = BookingModification::findOrFail($request->modification_id);

            if ($modification->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reject this modification.',
                ], 403);
            }

            $this->modificationService->rejectModification($modification, $student->id, $request->rejection_reason);

            return response()->json([
                'success' => true,
                'message' => 'Modification request rejected successfully!',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Cancel modification request
     */
    public function cancel(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $modification = BookingModification::findOrFail($request->modification_id);

            if ($modification->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to cancel this modification.',
                ], 403);
            }

            if (!$modification->canBeCancelled()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This modification request cannot be cancelled.',
                ], 400);
            }

            $modification->cancel($student->id);

            return response()->json([
                'success' => true,
                'message' => 'Modification request cancelled successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get teacher availability for reschedule
     */
    public function getTeacherAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_id' => 'required|exists:users,id',
            'date' => 'required|date|after_or_equal:today',
        ]);

        $teacherId = $request->teacher_id;
        $date = $request->date;
        $dayOfWeek = \Carbon\Carbon::parse($date)->dayOfWeek;

        $availabilities = TeacherAvailability::where('teacher_id', $teacherId)
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'formatted_time' => $this->formatTimeSlot($availability->start_time, $availability->end_time),
                    'time_zone' => $availability->time_zone ?? 'UTC',
                ];
            });

        return response()->json([
            'success' => true,
            'availabilities' => $availabilities,
        ]);
    }

    /**
     * Show the reschedule class page (GET for direct navigation)
     */
    public function rescheduleClassGet(Request $request): Response
    {
        return $this->renderRescheduleClass($request);
    }

    /**
     * Show the reschedule session details page (GET for direct navigation)
     */
    public function rescheduleSessionDetailsGet(Request $request): Response
    {
        return $this->renderRescheduleSessionDetails($request);
    }

    /**
     * Show the reschedule pricing payment page (GET for direct navigation)
     */
    public function reschedulePricingPaymentGet(Request $request): Response
    {
        return $this->renderReschedulePricingPayment($request);
    }

    /**
     * Render reschedule class page
     */
    private function renderRescheduleClass(Request $request): Response
    {
        // Get data from session first, then from request
        $sessionData = $request->hasSession() ? $request->session()->get('reschedule_session', []) : [];
        
        $bookingId = $request->input('booking_id') ?? $sessionData['booking_id'] ?? null;
        $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;

        if (!$bookingId || !$teacherId) {
            return redirect()->route('student.my-bookings')
                ->with('error', 'Please start the reschedule process from the beginning.');
        }

        $student = $request->user();
        $booking = Booking::with(['teacher', 'subject'])
            ->where('student_id', $student->id)
            ->findOrFail($bookingId);

        $teacher = User::with(['teacherProfile', 'availabilities'])
            ->findOrFail($teacherId);

        // Store reschedule data in session
        $request->session()->put('reschedule_session', [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $sessionData['dates'] ?? [],
            'availability_ids' => $sessionData['availability_ids'] ?? [],
            'subjects' => $sessionData['subjects'] ?? [],
            'note_to_teacher' => $sessionData['note_to_teacher'] ?? '',
            'reschedule_reason' => $sessionData['reschedule_reason'] ?? '',
        ]);

        // Get teacher availabilities
        $availabilities = TeacherAvailability::where('teacher_id', $teacher->id)
            ->where('is_active', true)
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'is_active' => $availability->is_active,
                    'time_zone' => $availability->time_zone ?? 'UTC',
                    'formatted_time' => $this->formatTimeSlot($availability->start_time, $availability->end_time),
                    'availability_type' => $availability->availability_type ?? 'regular'
                ];
            });

        return Inertia::render('student/reschedule/reschedule-class', [
            'booking' => $booking,
            'teacher' => $teacher,
            'availabilities' => $availabilities,
        ]);
    }

    /**
     * Render reschedule session details page
     */
    private function renderRescheduleSessionDetails(Request $request): Response
    {
        // Get data from session first, then from request
        $sessionData = $request->hasSession() ? $request->session()->get('reschedule_session', []) : [];
        
        $bookingId = $request->input('booking_id') ?? $sessionData['booking_id'] ?? null;
        $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
        $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
        $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];

        if (!$bookingId || !$teacherId || empty($dates) || empty($availabilityIds)) {
            return redirect()->route('student.reschedule.class')
                ->with('error', 'Please start the reschedule process from the beginning.');
        }

        $student = $request->user();
        $booking = Booking::with(['teacher', 'subject'])
            ->where('student_id', $student->id)
            ->findOrFail($bookingId);

        $teacher = User::with(['teacherProfile', 'subjects.template'])
            ->findOrFail($teacherId);

        // Update session with new data
        $request->session()->put('reschedule_session', array_merge($sessionData, [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
        ]));

        // Get time slots for selected availabilities
        $timeSlots = TeacherAvailability::whereIn('id', $availabilityIds)
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $this->getDayName($availability->day_of_week),
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'formatted_time' => $this->formatTimeSlot($availability->start_time, $availability->end_time),
                    'time_zone' => $availability->time_zone ?? 'UTC',
                ];
            });

        return Inertia::render('student/reschedule/reschedule-session-details', [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'time_slots' => $timeSlots,
            'booking' => $booking,
            'teacher' => $teacher,
        ]);
    }

    /**
     * Render reschedule pricing payment page
     */
    private function renderReschedulePricingPayment(Request $request): Response
    {
        // Get data from session first, then from request
        $sessionData = $request->hasSession() ? $request->session()->get('reschedule_session', []) : [];
        
        $bookingId = $request->input('booking_id') ?? $sessionData['booking_id'] ?? null;
        $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
        $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
        $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
        $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
        $rescheduleReason = $request->input('reschedule_reason') ?? $sessionData['reschedule_reason'] ?? '';

        if (!$bookingId || !$teacherId || empty($dates) || empty($availabilityIds) || empty($subjects) || empty($rescheduleReason)) {
            return redirect()->route('student.reschedule.class')
                ->with('error', 'Please start the reschedule process from the beginning.');
        }

        $student = $request->user();
        $booking = Booking::with(['teacher', 'subject'])
            ->where('student_id', $student->id)
            ->findOrFail($bookingId);

        $teacher = User::with(['teacherProfile'])
            ->findOrFail($teacherId);

        // Update session with new data
        $request->session()->put('reschedule_session', array_merge($sessionData, [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'subjects' => $subjects,
            'reschedule_reason' => $rescheduleReason,
        ]));

        // Get teacher profile with pricing
        $teacherProfile = $teacher->teacherProfile;
        if (!$teacherProfile) {
            return Inertia::render('student/reschedule/reschedule-pricing-payment', [
                'booking_id' => $bookingId,
                'teacher_id' => $teacherId,
                'dates' => $dates,
                'availability_ids' => $availabilityIds,
                'time_slots' => [],
                'subjects' => $subjects,
                'note_to_teacher' => $sessionData['note_to_teacher'] ?? '',
                'reschedule_reason' => $rescheduleReason,
                'booking' => $booking,
                'teacher' => null, // No teacher profile found
                'wallet_balance_usd' => 0,
                'wallet_balance_ngn' => 0,
                'user' => $student,
            ]);
        }

        // Add pricing data to teacher object with fallback values
        $teacher->hourly_rate_usd = $teacherProfile->hourly_rate_usd ? (float)$teacherProfile->hourly_rate_usd : 25.0;
        $teacher->hourly_rate_ngn = $teacherProfile->hourly_rate_ngn ? (float)$teacherProfile->hourly_rate_ngn : 37500.0;

        // Get time slots for selected availabilities
        $timeSlots = TeacherAvailability::whereIn('id', $availabilityIds)
            ->get()
            ->map(function ($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $this->getDayName($availability->day_of_week),
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'formatted_time' => $this->formatTimeSlot($availability->start_time, $availability->end_time),
                    'time_zone' => $availability->time_zone ?? 'UTC',
                ];
            });

        // Get wallet balances
        $wallet = $student->studentWallet;
        $walletBalanceNGN = $wallet ? $wallet->balance : 0;
        $walletBalanceUSD = $walletBalanceNGN / 1500; // Convert NGN to USD

        return Inertia::render('student/reschedule/reschedule-pricing-payment', [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'time_slots' => $timeSlots,
            'subjects' => $subjects,
            'note_to_teacher' => $sessionData['note_to_teacher'] ?? '',
            'reschedule_reason' => $rescheduleReason,
            'booking' => $booking,
            'teacher' => $teacher,
            'wallet_balance_usd' => $walletBalanceUSD,
            'wallet_balance_ngn' => $walletBalanceNGN,
            'user' => $student,
        ]);
    }

    /**
     * Clear reschedule session data
     */
    private function clearRescheduleSession(Request $request): void
    {
        $request->session()->forget('reschedule_session');
    }

    /**
     * Helper method to format time slot
     */
    private function formatTimeSlot(string $startTime, string $endTime): string
    {
        $start = \Carbon\Carbon::parse($startTime);
        $end = \Carbon\Carbon::parse($endTime);
        
        return $start->format('g:i A') . ' - ' . $end->format('g:i A');
    }

    /**
     * Helper method to get day name
     */
    private function getDayName(int $dayOfWeek): string
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayOfWeek] ?? 'Unknown';
    }

    /**
     * Check if there's already a pending modification for a booking
     */
    public function checkExistingModification(Request $request): JsonResponse
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
        ]);

        $existingModification = BookingModification::where('booking_id', $request->booking_id)
            ->whereIn('status', ['pending', 'approved'])
            ->with(['booking', 'teacher'])
            ->first();

        return response()->json([
            'has_existing' => $existingModification !== null,
            'modification' => $existingModification,
        ]);
    }
}