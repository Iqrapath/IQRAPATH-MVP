<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TeacherProfile;
use App\Models\SubjectTemplates;
use App\Models\Booking;
use App\Models\TeachingSession;
use App\Models\TeacherAvailability;
use App\Models\Subject;
use App\Models\TeacherReview;
use App\Models\BookingNote;
use App\Services\StudentSessionService;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use App\Models\User;
use App\Services\BookingNotificationService;
use App\Services\GuardianBookingService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct(
        private BookingNotificationService $bookingNotificationService,
        private StudentSessionService $sessionService,
        private GuardianBookingService $guardianBookingService
    ) {}

    /**
     * Display guardian's children bookings
     */
    public function index(Request $request)
    {
        $guardian = auth()->user();
        
        $bookings = $this->guardianBookingService->getFormattedBookings($guardian);
        
        return Inertia::render('guardian/my-bookings', [
            'bookings' => [
                'upcoming' => $bookings['upcoming'],
                'ongoing' => $bookings['ongoing'],
                'completed' => $bookings['completed'],
            ],
            'stats' => $bookings['stats'],
        ]);
    }

    /**
     * Display specific booking details
     */
    public function show(Request $request, Booking $booking)
    {
        $guardian = auth()->user();
        
        // Get all student IDs this guardian can access (children + guardian if they have student role)
        $studentIds = $this->guardianBookingService->getStudentIds($guardian);

        // Ensure the booking belongs to one of the guardian's children or the guardian themselves
        if (!in_array($booking->student_id, $studentIds)) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'You can only view bookings for your children or yourself.');
        }

        $booking->load([
            'student',
            'teacher.teacherProfile',
            'subject.template',
            'teachingSession',
            'bookingNotes' => function($query) {
                $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
            },
            'history' => function($query) {
                $query->latest()->take(1);
            }
        ]);

        // Format booking data for frontend
        $bookingData = [
            'id' => $booking->id,
            'booking_uuid' => $booking->booking_uuid,
            'title' => $booking->subject->template->name ?? 'Unknown Subject',
            'teacher' => $booking->teacher->name ?? 'Unknown Teacher',
            'student_name' => $booking->student->name ?? 'Unknown Student',
            'subject' => $booking->subject->template->name ?? 'Unknown Subject',
            'date' => $booking->booking_date->format('d M Y'),
            'time' => $booking->start_time->format('H:i') . ' - ' . $booking->end_time->format('H:i'),
            'status' => ucfirst($booking->status),
            'meetingUrl' => $booking->teachingSession?->zoom_join_url,
            'teacherNotes' => $booking->bookingNotes->where('note_type', 'teacher_note')->first()?->note,
            'studentNotes' => $booking->bookingNotes->where('note_type', 'student_note')->first()?->note,
            'studentReviewNote' => $booking->bookingNotes->where('note_type', 'student_review')->first()?->note,
            'history' => $booking->history->first() ? [
                'status' => $booking->history->first()->status,
                'updated_at' => $booking->history->first()->updated_at->format('d M Y H:i'),
            ] : null,
        ];

        // Get teacher data
        $teacher = $booking->teacher;
        $teacherProfile = $teacher->teacherProfile;
        
        // Get teacher subjects
        $teacherSubjects = $teacherProfile?->subjects ?? collect();
        $subjectNames = $teacherSubjects->map(function($subject) {
            return $subject->template?->name ?? $subject->name ?? 'Unknown Subject';
        })->toArray();

        // Get reviews count
        $reviewsCount = $teacher->teacherReviews?->count() ?? 0;

        // Format teacher data
        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => null, // Use initials instead of images
            'specialization' => $teacherProfile?->qualification ?? 'Islamic Studies',
            'location' => $teacherProfile?->location ?? 'Not specified',
            'rating' => $teacherProfile?->rating ?? 4.5,
            'availability' => $teacherProfile?->teaching_mode ?? 'Available on request',
            'bio' => $teacherProfile?->bio ?? '',
            'experience_years' => $teacherProfile?->experience_years ?? 0,
            'subjects' => $subjectNames,
            'reviews_count' => $reviewsCount,
            'hourly_rate_ngn' => $teacherProfile?->hourly_rate_ngn ?? 0,
            'hourly_rate_usd' => $teacherProfile?->hourly_rate_usd ?? 0,
            'is_verified' => $teacherProfile?->verified ?? false,
        ];

        return Inertia::render('guardian/class-details', [
            'booking' => $bookingData,
            'teacher' => $teacherData,
        ]);
    }

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, Booking $booking)
    {
        $guardian = auth()->user();
        
        // Get all student IDs this guardian can access (children + guardian if they have student role)
        $studentIds = $this->guardianBookingService->getStudentIds($guardian);

        // Ensure the booking belongs to one of the guardian's children or the guardian themselves
        if (!in_array($booking->student_id, $studentIds)) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'You can only cancel bookings for your children or yourself.');
        }

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            DB::beginTransaction();

            // Update booking status
            $booking->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->reason,
                'cancelled_at' => now(),
                'cancelled_by' => $guardian->id,
            ]);

            // Cancel associated teaching session if exists
            if ($booking->teachingSession) {
                $booking->teachingSession->update([
                    'status' => 'cancelled',
                    'cancellation_reason' => $request->reason,
                ]);
            }

            // Send notifications
            $this->bookingNotificationService->sendBookingCancelledNotification($booking, $guardian);

            DB::commit();

            return redirect()->route('guardian.my-bookings')
                ->with('success', 'Booking cancelled successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Failed to cancel booking. Please try again.');
        }
    }

    /**
     * Create a new booking
     */
    public function create(Request $request)
    {
        $teacherId = $request->input('teacherId');
        
        if (!$teacherId) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Please select a teacher to book a class.');
        }

        $teacher = User::with([
            'teacherProfile.subjects.template',
            'teacherAvailabilities' => function($query) {
                $query->where('is_active', true);
            }
        ])->findOrFail($teacherId);

        if (!$teacher || $teacher->role !== 'teacher') {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Teacher not found.');
        }

        $profile = $teacher->teacherProfile;
        if (!$profile || !$profile->verified) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'This teacher is not available for booking.');
        }

        // Format subjects
        $subjects = [];
        if ($profile && $profile->subjects) {
            $subjects = $profile->subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->template?->name ?? 'Unknown Subject',
                    'template' => $subject->template
                ];
            })->toArray();
        }

        // Format teacher data
        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar,
            'rating' => $profile->rating ? (float) $profile->rating : 4.5,
            'hourly_rate_usd' => $profile->hourly_rate_usd,
            'hourly_rate_ngn' => $profile->hourly_rate_ngn,
            'subjects' => $subjects,
            'location' => $teacher->location ?? 'Nigeria',
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'reviews_count' => $profile->reviews_count,
            'availability' => $profile->availability,
            'verified' => $profile->verified,
            'availabilities' => $teacher->teacherAvailabilities->map(function($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'is_active' => $availability->is_active,
                    'time_zone' => $availability->time_zone,
                    'formatted_time' => date('g:i A', strtotime($availability->start_time)) . ' - ' . date('g:i A', strtotime($availability->end_time)),
                    'availability_type' => $availability->availability_type,
                ];
            })->toArray(),
            'recommended_teachers' => [], // TODO: Implement recommendations
        ];

        return Inertia::render('guardian/book-class', [
            'teacher' => $teacherData,
            'teacherId' => $teacherId,
        ]);
    }

    /**
     * Show the session details page (POST from booking flow)
     */
    public function sessionDetails(Request $request)
    {
        // Store booking session data in session for page refresh support
        $request->session()->put('booking_session', [
            'teacher_id' => $request->input('teacher_id'),
            'dates' => $request->input('dates', []),
            'availability_ids' => $request->input('availability_ids', []),
            'subjects' => $request->input('subjects', []),
            'note_to_teacher' => $request->input('note_to_teacher', ''),
        ]);
        
        return $this->renderSessionDetails($request);
    }

    /**
     * Show the session details page (GET for direct navigation)
     */
    public function sessionDetailsGet(Request $request)
    {
        return $this->renderSessionDetails($request);
    }

    /**
     * Render session details page
     */
    private function renderSessionDetails(Request $request)
    {
        // Get data from session first, then from request
        $sessionData = $request->hasSession() ? $request->session()->get('booking_session', []) : [];
        
        $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
        $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
        $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
        $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
        $noteToTeacher = $request->input('note_to_teacher') ?? $sessionData['note_to_teacher'] ?? '';

        if (!$teacherId || empty($dates) || empty($availabilityIds)) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        $teacher = User::where('role', 'teacher')->with(['teacherProfile.subjects.template'])->find($teacherId);
        
        if (!$teacher) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Teacher not found.');
        }
                $subject = $booking->subject;
                $session = $booking->teachingSession;
                $student = $booking->student;
                
                $subjectTemplate = $subject?->template;
                $teacherProfile = $teacher?->teacherProfile;
                
                // Get teacher and student notes from BookingNote
                $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
                $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
                $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();
                
                // Get student review for this session
                $studentReview = $session?->teacherReviews->whereIn('student_id', $studentIds)->first();
                
                // Determine if this booking is for the guardian or a child
                $isGuardianBooking = $booking->student_id === $guardian->id;
                $studentName = $isGuardianBooking ? $guardian->name . ' (You)' : $student->name;
                
                return [
                    'id' => $booking->id,
                    'booking_uuid' => $booking->booking_uuid,
                    'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                    'teacher' => $teacher?->name ?? 'Unknown Teacher',
                    'student_name' => $studentName,
                    'student_id' => $booking->student_id,
                    'is_guardian_booking' => $isGuardianBooking,
                    'teacher_id' => $teacher?->id,
                    'teacher_avatar' => null, // Use initials instead of images
                    'subject' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                    'date' => $booking->booking_date->format('d M Y'),
                    'time' => $booking->start_time->format('H:i') . ' - ' . $booking->end_time->format('H:i'),
                    'status' => ucfirst($booking->status),
                    'imageUrl' => null, // Use initials instead of images
                    'meetingUrl' => $session?->zoom_join_url,
                    'session_uuid' => $session?->session_uuid,
                    'can_join' => $session && in_array($booking->status, ['approved', 'confirmed']) && 
                                  $booking->booking_date->isToday() && 
                                  $booking->start_time->subMinutes(15)->isPast(),
                    'can_reschedule' => in_array($booking->status, ['pending', 'approved']) && 
                                        $booking->booking_date->isFuture(),
                    'can_cancel' => in_array($booking->status, ['pending', 'approved']) && 
                                    $booking->booking_date->isFuture(),
                    'booking_date_raw' => $booking->booking_date,
                    'start_time_raw' => $booking->start_time,
                    // Database fields for modal
                    'booking_date' => $booking->booking_date->format('Y-m-d'),
                    'start_time' => $booking->start_time->format('H:i:s'),
                    'end_time' => $booking->end_time->format('H:i:s'),
                    'duration_minutes' => $booking->duration_minutes,
                    'notes' => $booking->notes,
                    'teacher_notes' => $teacherNotes?->content,
                    'student_notes' => $studentNotes?->content,
                    'user_feedback' => $studentReviewNote?->content,
                    'teachingSession' => $session ? [
                        'id' => $session->id,
                        'teacher_notes' => $session->teacher_notes,
                        'student_notes' => $session->student_notes,
                        'student_rating' => $session->student_rating,
                        'teacher_rating' => $session->teacher_rating,
                        'meeting_platform' => $session->meeting_platform,
                        'recording_url' => $session->recording_url,
                        'completion_date' => $session->completion_date?->format('Y-m-d H:i:s'),
                        'zoom_join_url' => $session->zoom_join_url,
                        'google_meet_link' => $session->google_meet_link,
                        'student_review' => $studentReview?->review,
                        'booking_notes' => $booking->bookingNotes->map(function($note) {
                            return [
                                'id' => $note->id,
                                'content' => $note->content,
                                'note_type' => $note->note_type,
                                'created_at' => $note->created_at->format('Y-m-d H:i:s'),
                            ];
                        })->toArray(),
                    ] : null,
                ];
            // });

        // Categorize bookings
        $upcoming = $bookings->filter(function ($booking) {
            return in_array(strtolower($booking['status']), ['pending', 'approved']) && 
                   Carbon::parse($booking['booking_date_raw'])->isFuture();
        })->values();

        $ongoing = $bookings->filter(function ($booking) {
            return strtolower($booking['status']) === 'confirmed' && 
                   Carbon::parse($booking['booking_date_raw'])->isToday();
        })->values();

        $completed = $bookings->filter(function ($booking) {
            return strtolower($booking['status']) === 'completed';
        })->values();

        // Calculate stats
        $stats = [
            'total' => $bookings->count(),
            'upcoming' => $upcoming->count(),
            'ongoing' => $ongoing->count(),
            'completed' => $completed->count(),
        ];

        return Inertia::render('guardian/my-bookings', [
            'bookings' => [
                'upcoming' => $upcoming->toArray(),
                'ongoing' => $ongoing->toArray(),
                'completed' => $completed->toArray(),
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Display specific booking details
     */
    // public function show(Request $request, Booking $booking)
    // {
    //     $guardian = auth()->user();
    //     $children = $guardian->guardianProfile->children ?? collect();
    //     $childIds = $children->pluck('id')->toArray();

    //     // Ensure the booking belongs to one of the guardian's children
    //     if (!in_array($booking->student_id, $childIds)) {
    //         abort(403, 'You can only view bookings for your children.');
    //     }

    //     $booking->load([
    //         'student',
    //         'teacher.teacherProfile',
    //         'subject.template',
    //         'teachingSession.teacherReviews' => function($query) use ($childIds) {
    //             $query->whereIn('student_id', $childIds);
    //         },
    //         'bookingNotes' => function($query) {
    //             $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
    //         },
    //     ]);

    //     $teacher = $booking->teacher;
    //     $subject = $booking->subject;
    //     $session = $booking->teachingSession;
    //     $student = $booking->student;

    //     $subjectTemplate = $subject?->template;
    //     $teacherProfile = $teacher?->teacherProfile;

    //     // Get teacher and student notes from BookingNote
    //     $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
    //     $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
    //     $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();

    //     // Get student review for this session
    //     $studentReview = $session?->teacherReviews->whereIn('student_id', $childIds)->first();

    //     $bookingData = [
    //         'id' => $booking->id,
    //         'booking_uuid' => $booking->booking_uuid,
    //         'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
    //         'teacher' => $teacher?->name ?? 'Unknown Teacher',
    //         'teacher_id' => $teacher?->id,
    //         'teacher_avatar' => null,
    //         'subject' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
    //         'student_name' => $student?->name ?? 'Unknown Student',
    //         'student_id' => $student?->id,
    //         'date' => $booking->booking_date->format('d M Y'),
    //         'time' => $booking->start_time->format('H:i') . ' - ' . $booking->end_time->format('H:i'),
    //         'status' => ucfirst($booking->status),
    //         'imageUrl' => null,
    //         'meetingUrl' => $session?->zoom_join_url,
    //         'session_uuid' => $session?->session_uuid,
    //         'can_join' => $session && in_array($booking->status, ['approved', 'confirmed']) && 
    //                       $booking->booking_date->isToday() && 
    //                       $booking->start_time->subMinutes(15)->isPast(),
    //         'can_reschedule' => in_array($booking->status, ['pending', 'approved']) && 
    //                             $booking->booking_date->isFuture(),
    //         'can_cancel' => in_array($booking->status, ['pending', 'approved']) && 
    //                         $booking->booking_date->isFuture(),
    //         'booking_date_raw' => $booking->booking_date,
    //         'start_time_raw' => $booking->start_time,
    //         'booking_date' => $booking->booking_date->format('Y-m-d'),
    //         'start_time' => $booking->start_time->format('H:i:s'),
    //         'end_time' => $booking->end_time->format('H:i:s'),
    //         'duration_minutes' => $booking->duration_minutes,
    //         'notes' => $booking->notes,
    //         'teacher_notes' => $teacherNotes?->content,
    //         'student_notes' => $studentNotes?->content,
    //         'user_feedback' => $studentReviewNote?->content,
    //         'teachingSession' => $session ? [
    //             'id' => $session->id,
    //             'teacher_notes' => $session->teacher_notes,
    //             'student_notes' => $session->student_notes,
    //             'student_rating' => $session->student_rating,
    //             'teacher_rating' => $session->teacher_rating,
    //             'meeting_platform' => $session->meeting_platform,
    //             'recording_url' => $session->recording_url,
    //             'completion_date' => $session->completion_date?->format('Y-m-d H:i:s'),
    //             'zoom_join_url' => $session->zoom_join_url,
    //             'google_meet_link' => $session->google_meet_link,
    //             'student_review' => $studentReview?->review,
    //             'booking_notes' => $booking->bookingNotes->map(function($note) {
    //                 return [
    //                     'id' => $note->id,
    //                     'content' => $note->content,
    //                     'note_type' => $note->note_type,
    //                     'created_at' => $note->created_at->format('Y-m-d H:i:s'),
    //                 ];
    //             })->toArray(),
    //         ] : null,
    //     ];

    //     return Inertia::render('guardian/booking-details', [
    //         'booking' => $bookingData,
    //     ]);
    // }

    /**
     * Cancel a booking
     */
    // public function cancel(Request $request, Booking $booking)
    // {
    //     $guardian = auth()->user();
    //     $children = $guardian->guardianProfile->children ?? collect();
    //     $childIds = $children->pluck('id')->toArray();

    //     // Ensure the booking belongs to one of the guardian's children
    //     if (!in_array($booking->student_id, $childIds)) {
    //         abort(403, 'You can only cancel bookings for your children.');
    //     }

    //     $request->validate([
    //         'reason' => 'required|string|max:500',
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // Update booking status
    //         $booking->update([
    //             'status' => 'cancelled',
    //             'cancellation_reason' => $request->reason,
    //             'cancelled_at' => now(),
    //             'cancelled_by' => $guardian->id,
    //         ]);

    //         // Cancel associated teaching session if exists
    //         if ($booking->teachingSession) {
    //             $booking->teachingSession->update([
    //                 'status' => 'cancelled',
    //                 'cancellation_reason' => $request->reason,
    //             ]);
    //         }

    //         // Send notifications
    //         $this->bookingNotificationService->sendBookingCancelledNotification($booking, $guardian);

    //         DB::commit();

    //         return redirect()->route('guardian.my-bookings')
    //             ->with('success', 'Booking cancelled successfully.');

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return redirect()->back()
    //             ->with('error', 'Failed to cancel booking. Please try again.');
    //     }
    // }

    /**
     * Show book class page for a specific teacher
     */
    // public function create(Request $request)
    // {
    //     $teacherId = $request->input('teacherId');
        
    //     if (!$teacherId) {
    //         return redirect()->route('guardian.browse-teachers')
    //             ->with('error', 'Please select a teacher to book a class.');
    //     }

    //     $teacher = User::with([
    //         'teacherProfile.subjects.template',
    //         'teacherAvailabilities' => function($query) {
    //             $query->where('is_active', true)
    //                   ->where('holiday_mode', false);
    //         }
    //     ])->find($teacherId);

    //     if (!$teacher || $teacher->role !== 'teacher') {
    //         return redirect()->route('guardian.browse-teachers')
    //             ->with('error', 'Teacher not found.');
    //     }

    //     $profile = $teacher->teacherProfile;
    //     if (!$profile || !$profile->verified) {
    //         return redirect()->route('guardian.browse-teachers')
    //             ->with('error', 'This teacher is not available for booking.');
    //     }

    //     // Format teacher data
    //     $teacherData = [
    //         'id' => $teacher->id,
    //         'name' => $teacher->name,
    //         'avatar' => $teacher->avatar,
    //         'rating' => $profile->rating ? (float) $profile->rating : 4.5,
    //         'hourly_rate_usd' => $profile->hourly_rate_usd,
    //         'hourly_rate_ngn' => $profile->hourly_rate_ngn,
    //         'subjects' => $profile->subjects->map(fn($s) => $s->template->name ?? $s->name)->filter()->toArray(),
    //         'location' => $teacher->location ?? 'Nigeria',
    //         'bio' => $profile->bio,
    //         'experience_years' => $profile->experience_years,
    //         'reviews_count' => $profile->reviews_count,
    //         'availability' => $profile->availability,
    //         'verified' => $profile->verified,
    //         'availabilities' => $teacher->teacherAvailabilities->map(function($availability) {
    //             return [
    //                 'id' => $availability->id,
    //                 'day_of_week' => $availability->day_of_week,
    //                 'start_time' => $availability->start_time,
    //                 'end_time' => $availability->end_time,
    //                 'is_active' => $availability->is_active,
    //                 'time_zone' => $availability->time_zone,
    //                 'formatted_time' => date('g:i A', strtotime($availability->start_time)) . ' - ' . date('g:i A', strtotime($availability->end_time)),
    //                 'availability_type' => $availability->availability_type,
    //             ];
    //         })->toArray(),
    //         'recommended_teachers' => [], // TODO: Implement recommendations
    //     ];

    //     return Inertia::render('guardian/book-class', [
    //         'teacher' => $teacherData,
    //         'teacherId' => $teacherId,
    //     ]);
    // }

    /**
     * Show the session details page (POST from booking flow)
     */
    // public function sessionDetails(Request $request)
    // {
    //     // Store booking session data in session for page refresh support
    //     $request->session()->put('booking_session', [
    //         'teacher_id' => $request->input('teacher_id'),
    //         'dates' => $request->input('dates', []),
    //         'availability_ids' => $request->input('availability_ids', []),
    //         'subjects' => $request->input('subjects', []),
    //         'note_to_teacher' => $request->input('note_to_teacher', ''),
    //     ]);
        
    //     return $this->renderSessionDetails($request);
    // }

    /**
     * Show the session details page (GET for direct navigation)
     */
    // public function sessionDetailsGet(Request $request)
    // {
    //     return $this->renderSessionDetails($request);
    // }

    /**
     * Render session details page
     */
    // private function renderSessionDetails(Request $request)
    // {
    //     // Get data from session first, then from request
    //     $sessionData = $request->hasSession() ? $request->session()->get('booking_session', []) : [];
        
    //     $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
    //     $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
    //     $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
    //     $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
    //     $noteToTeacher = $request->input('note_to_teacher') ?? $sessionData['note_to_teacher'] ?? '';

    //     if (!$teacherId || empty($dates) || empty($availabilityIds)) {
    //         return redirect()->route('guardian.browse-teachers')
    //             ->with('error', 'Please start the booking process from the beginning.');
    //     }

    //     $teacher = User::where('role', 'teacher')->with(['teacherProfile.subjects.template'])->find($teacherId);
        
    //     if (!$teacher) {
    //         return redirect()->route('guardian.browse-teachers')
    //             ->with('error', 'Teacher not found.');
    //     }

    //     // Get time slots for selected availability IDs
    //     $timeSlots = TeacherAvailability::whereIn('id', $availabilityIds)
    //         ->get()
    //         ->map(function($availability) {
    //             return [
    //                 'id' => $availability->id,
    //                 'day_of_week' => $availability->day_of_week,
    //                 'start_time' => $availability->start_time,
    //                 'end_time' => $availability->end_time,
    //                 'formatted_time' => date('g:i A', strtotime($availability->start_time)) . ' - ' . date('g:i A', strtotime($availability->end_time)),
    //                 'time_zone' => $availability->time_zone,
    //             ];
    //         });

    //     $teacherData = [
    //         'id' => $teacher->id,
    //         'name' => $teacher->name,
    //         'subjects' => $teacher->teacherProfile->subjects->map(function($subject) {
    //             return [
    //                 'id' => $subject->id,
    //                 'name' => $subject->template?->name ?? $subject->name,
    //                 'template' => $subject->template,
    //             ];
    //         })->toArray(),
    //         'recommended_teachers' => [], // TODO: Implement recommendations
    //     ];

    //     return Inertia::render('guardian/session-details', [
    //         'teacher_id' => $teacherId,
    //         'dates' => $dates,
    //         'availability_ids' => $availabilityIds,
    //         'time_slots' => $timeSlots,
    //         'teacher' => $teacherData,
    //         'previous_page' => $request->input('previous_page'),
    //     ]);
    // }


    /**
     * Show reschedule class page
     */
    public function rescheduleClass(Request $request)
    {
        $bookingId = $request->input('booking_id');
        
        if (!$bookingId) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Please select a booking to reschedule.');
        }

        $guardian = auth()->user();
        
        // Get all student IDs this guardian can access (children + guardian if they have student role)
        $studentIds = $this->guardianBookingService->getStudentIds($guardian);

        $booking = Booking::with([
            'teacher.teacherProfile.subjects.template',
            'teacher.teacherAvailabilities' => function($query) {
                $query->where('is_active', true)
                      ->where('holiday_mode', false);
            },
            'subject.template'
        ])->find($bookingId);

        if (!$booking || !in_array($booking->student_id, $studentIds)) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Booking not found or you do not have permission to reschedule it.');
        }

        // Format booking data
        $bookingData = [
            'id' => $booking->id,
            'booking_uuid' => $booking->booking_uuid,
            'title' => $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Unknown Subject',
            'teacher' => $booking->teacher?->name ?? 'Unknown Teacher',
            'teacher_id' => $booking->teacher?->id,
            'subject' => $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Unknown Subject',
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'start_time' => $booking->start_time->format('H:i:s'),
            'end_time' => $booking->end_time->format('H:i:s'),
            'status' => ucfirst($booking->status),
        ];

        // Format teacher data
        $teacher = $booking->teacher;
        $profile = $teacher->teacherProfile;
        
        // Format subjects
        $subjects = [];
        if ($profile && $profile->subjects) {
            $subjects = $profile->subjects->map(function ($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->template?->name ?? 'Unknown Subject',
                    'template' => $subject->template
                ];
            })->toArray();
        }
        
        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar,
            'rating' => $profile->rating ? (float) $profile->rating : 4.5,
            'hourly_rate_usd' => $profile->hourly_rate_usd,
            'hourly_rate_ngn' => $profile->hourly_rate_ngn,
            'subjects' => $subjects,
            'location' => $teacher->location ?? 'Nigeria',
            'bio' => $profile->bio,
            'experience_years' => $profile->experience_years,
            'reviews_count' => $profile->reviews_count,
            'availability' => $profile->availability,
            'verified' => $profile->verified,
            'availabilities' => $teacher->teacherAvailabilities->map(function($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'is_active' => $availability->is_active,
                    'time_zone' => $availability->time_zone,
                    'formatted_time' => date('g:i A', strtotime($availability->start_time)) . ' - ' . date('g:i A', strtotime($availability->end_time)),
                    'availability_type' => $availability->availability_type,
                ];
            })->toArray(),
            'recommended_teachers' => [], // TODO: Implement recommendations
        ];

        return Inertia::render('guardian/reschedule/reschedule-class', [
            'booking' => $bookingData,
            'teacher' => $teacherData,
            'availabilities' => $teacherData['availabilities'],
        ]);
    }

    /**
     * Show reschedule session details page
     */
    public function rescheduleSessionDetailsGet(Request $request)
    {
        $bookingId = $request->input('booking_id');
        $teacherId = $request->input('teacher_id');
        $dates = $request->input('dates', []);
        $availabilityIds = $request->input('availability_ids', []);

        if (!$bookingId || !$teacherId || empty($dates) || empty($availabilityIds)) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Missing reschedule information. Please try again.');
        }

        $guardian = auth()->user();
        $children = $guardian->guardianProfile->children ?? collect();
        $childIds = $children->pluck('id')->toArray();

        $booking = Booking::with(['student'])->find($bookingId);
        
        if (!$booking || !in_array($booking->student_id, $childIds)) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Booking not found or you do not have permission to reschedule it.');
        }

        $teacher = User::with(['teacherProfile.subjects.template'])->find($teacherId);
        
        if (!$teacher || $teacher->role !== 'teacher') {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Teacher not found.');
        }

        // Get time slots for selected availability IDs
        $timeSlots = TeacherAvailability::whereIn('id', $availabilityIds)
            ->get()
            ->map(function($availability) {
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'formatted_time' => date('g:i A', strtotime($availability->start_time)) . ' - ' . date('g:i A', strtotime($availability->end_time)),
                    'time_zone' => $availability->time_zone,
                ];
            });

        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'subjects' => $teacher->teacherProfile->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->template?->name ?? $subject->name,
                    'template' => $subject->template,
                ];
            })->toArray(),
            'recommended_teachers' => [], // TODO: Implement recommendations
        ];

        return Inertia::render('guardian/reschedule/reschedule-session-details', [
            'booking_id' => $bookingId,
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'time_slots' => $timeSlots,
            'teacher' => $teacherData,
            'student_name' => $booking->student->name,
        ]);
    }

    /**
     * Process reschedule session details form submission
     */
    public function rescheduleSessionDetails(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'teacher_id' => 'required|exists:users,id',
            'dates' => 'required|array|min:1',
            'availability_ids' => 'required|array|min:1',
            'subjects' => 'required|array|min:1',
            'note_to_teacher' => 'nullable|string|max:1000',
        ]);

        // Store reschedule data in session for next step
        session([
            'guardian_reschedule_data' => [
                'booking_id' => $request->booking_id,
                'teacher_id' => $request->teacher_id,
                'dates' => $request->dates,
                'availability_ids' => $request->availability_ids,
                'subjects' => $request->subjects,
                'note_to_teacher' => $request->note_to_teacher,
            ]
        ]);

        return redirect()->route('guardian.reschedule.pricing-payment');
    }

    /**
     * Show reschedule pricing payment page
     */
    public function reschedulePricingPaymentGet(Request $request)
    {
        $rescheduleData = session('guardian_reschedule_data');
        
        if (!$rescheduleData) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Reschedule session expired. Please try again.');
        }

        // Get booking details
        $booking = Booking::with(['teacher.teacherProfile', 'subject.template'])->find($rescheduleData['booking_id']);
        
        if (!$booking) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Booking not found.');
        }

        // Calculate pricing (same as original booking for now)
        $teacher = $booking->teacher;
        $profile = $teacher->teacherProfile;
        
        $pricingData = [
            'teacher_name' => $teacher->name,
            'subject' => $booking->subject?->template?->name ?? $booking->subject?->name ?? 'Unknown Subject',
            'original_date' => $booking->booking_date->format('M j, Y'),
            'original_time' => $booking->start_time->format('g:i A') . ' - ' . $booking->end_time->format('g:i A'),
            'new_dates' => array_map(function($date) {
                return date('M j, Y', strtotime($date));
            }, $rescheduleData['dates']),
            'hourly_rate_ngn' => $profile->hourly_rate_ngn,
            'hourly_rate_usd' => $profile->hourly_rate_usd,
            'duration_hours' => $booking->duration_minutes / 60,
            'total_amount_ngn' => ($profile->hourly_rate_ngn * $booking->duration_minutes / 60) * count($rescheduleData['dates']),
            'total_amount_usd' => ($profile->hourly_rate_usd * $booking->duration_minutes / 60) * count($rescheduleData['dates']),
            'sessions_count' => count($rescheduleData['dates']),
        ];

        return Inertia::render('guardian/reschedule/reschedule-pricing-payment', [
            'pricing' => $pricingData,
            'reschedule_data' => $rescheduleData,
        ]);
    }

    /**
     * Process reschedule pricing payment form submission
     */
    public function reschedulePricingPayment(Request $request)
    {
        $request->validate([
            'payment_method' => 'required|in:wallet,card',
            'agree_terms' => 'required|accepted',
        ]);

        $rescheduleData = session('guardian_reschedule_data');
        
        if (!$rescheduleData) {
            return redirect()->route('guardian.my-bookings')
                ->with('error', 'Reschedule session expired. Please try again.');
        }

        // TODO: Implement actual reschedule logic
        // For now, just redirect back to my-bookings with success message
        
        session()->forget('guardian_reschedule_data');
        
        return redirect()->route('guardian.my-bookings')
            ->with('success', 'Reschedule request submitted successfully. The teacher will be notified.');
    }

    /**
     * Show the pricing and payment page (GET for direct navigation)
     */
    public function pricingPaymentGet(Request $request)
    {
        return $this->renderPricingPayment($request);
    }

    /**
     * Process session details form submission and redirect to pricing (POST from session-details page)
     */
    public function pricingPayment(Request $request)
    {
        // Get teacher data to store in session
        $teacherId = $request->input('teacher_id');
        $teacher = null;
        $teacherData = null;
        
        if ($teacherId) {
            $teacher = User::where('role', 'teacher')
                ->with(['teacherProfile'])
                ->find($teacherId);
                
            if ($teacher) {
                $profile = $teacher->teacherProfile;
                $teacherData = [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'hourly_rate_usd' => $profile?->hourly_rate_usd ? (float)$profile->hourly_rate_usd : 25.0,
                    'hourly_rate_ngn' => $profile?->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : 37500.0,
                ];
            }
        }

        // Store booking session data in session for page refresh support
        $request->session()->put('booking_session', [
            'teacher_id' => $request->input('teacher_id'),
            'teacher_data' => $teacherData,
            'dates' => $request->input('dates', []),
            'availability_ids' => $request->input('availability_ids', []),
            'subjects' => $request->input('subjects', []),
            'note_to_teacher' => $request->input('note_to_teacher', ''),
        ]);

        return $this->renderPricingPayment($request);
    }

    /**
     * Render pricing payment page
     */
    private function renderPricingPayment(Request $request)
    {
        // Get data from session first, then from request
        $sessionData = $request->hasSession() ? $request->session()->get('booking_session', []) : [];
        
        $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
        $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
        $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
        $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
        $noteToTeacher = $request->input('note_to_teacher') ?? $sessionData['note_to_teacher'] ?? '';

        if (!$teacherId || empty($dates) || empty($subjects) || empty($availabilityIds)) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        $teacher = User::where('role', 'teacher')->with(['teacherProfile'])->find($teacherId);
        
        if (!$teacher) {
            return redirect()->route('guardian.browse-teachers')
                ->with('error', 'Teacher not found.');
        }

        $profile = $teacher->teacherProfile;
        
        // Get recommended teachers (similar teachers with good ratings)
        $recommendedTeachers = User::where('role', 'teacher')
            ->where('id', '!=', $teacher->id)
            ->with(['teacherProfile.subjects.template'])
            ->whereHas('teacherProfile', function ($query) {
                $query->where('rating', '>=', 4.0)
                      ->where('verified', true);
            })
            ->take(6)
            ->get()
            ->map(function ($recommendedTeacher) {
                $recommendedProfile = $recommendedTeacher->teacherProfile;
                $recommendedSubjects = $recommendedProfile && $recommendedProfile->subjects 
                    ? $recommendedProfile->subjects->pluck('template.name')->filter()->implode(', ')
                    : 'General Tutoring';
                
                return [
                    'id' => $recommendedTeacher->id,
                    'name' => $recommendedTeacher->name,
                    'subjects' => $recommendedSubjects,
                    'location' => $recommendedProfile->location ?? 'Location not set',
                    'rating' => $recommendedProfile->rating ? (float) $recommendedProfile->rating : 4.0,
                    'price' => $recommendedProfile->hourly_rate_ngn ? '₦' . number_format($recommendedProfile->hourly_rate_ngn) . ' / session' : '₦5,000 / session',
                    'avatarUrl' => '',
                ];
            })->toArray();

        $formattedTeacher = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'recommended_teachers' => $recommendedTeachers,
            'hourly_rate_usd' => $profile?->hourly_rate_usd ? (float)$profile->hourly_rate_usd : 25.0,
            'hourly_rate_ngn' => $profile?->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : 37500.0,
        ];

        // Get time slot information for the selected availability IDs
        $timeSlots = [];
        if (!empty($availabilityIds)) {
            $availabilities = TeacherAvailability::whereIn('id', $availabilityIds)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();
                
            foreach ($availabilities as $availability) {
                $startTime = \Carbon\Carbon::parse($availability->start_time);
                $endTime = \Carbon\Carbon::parse($availability->end_time);
                
                $timeSlots[] = [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'formatted_time' => $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A'),
                    'time_zone' => $availability->time_zone,
                ];
            }
        }

        // Get guardian wallet balance
        $guardianWallet = auth()->user()->guardianWallet;
        $walletBalanceUSD = 0;
        $walletBalanceNGN = 0;
        
        if ($guardianWallet) {
            $walletBalanceNGN = (float)$guardianWallet->balance;
            $walletBalanceUSD = $walletBalanceNGN / 1500; // Approximate conversion rate
        }

        $guardian = auth()->user();
        $guardianProfile = $guardian->guardianProfile;
        $children = $guardianProfile ? $guardianProfile->students : collect();
        
        return Inertia::render('guardian/pricing-payment', [
            'teacher_id' => (int) $teacherId,
            'dates' => $dates,
            'availability_ids' => array_map('intval', $availabilityIds),
            'time_slots' => $timeSlots,
            'subjects' => $subjects,
            'note_to_teacher' => $noteToTeacher,
            'teacher' => $formattedTeacher,
            'wallet_balance_usd' => $walletBalanceUSD,
            'wallet_balance_ngn' => $walletBalanceNGN,
            'user' => [
                'id' => $guardian->id,
                'name' => $guardian->name,
                'email' => $guardian->email,
                'country' => $guardian->country ?? 'NG',
                'additional_roles' => $guardian->additional_roles ?? [],
            ],
            'children' => $children->map(function ($child) {
                return [
                    'id' => $child->user_id,
                    'name' => $child->user->name,
                    'email' => $child->user->email,
                ];
            })->toArray(),
        ]);
    }

    /**
     * Process payment for new booking
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'dates' => 'required|array',
            'dates.*' => 'date',
            'availability_ids' => 'array',
            'availability_ids.*' => 'integer|exists:teacher_availabilities,id',
            'subjects' => 'required|array',
            'subjects.*' => 'string',
            'note_to_teacher' => 'nullable|string',
            'currency' => 'required|in:USD,NGN',
            'payment_methods' => 'required|array',
            'payment_methods.*' => 'string|in:wallet,card,bank_transfer',
            'amount' => 'required|numeric|min:0',
            'booking_for' => 'required|string|in:self,child',
            'child_id' => 'nullable|integer|exists:users,id',
        ]);

        $guardian = auth()->user();
        $guardianWallet = $guardian->guardianWallet;
        
        // Extract request data
        $teacherId = $request->input('teacher_id');
        $dates = $request->input('dates', []);
        $availabilityIds = $request->input('availability_ids', []);
        $subjects = $request->input('subjects', []);
        $noteToTeacher = $request->input('note_to_teacher', '');

        // Check if teacher is in holiday mode before processing payment
        $teacherAvailability = \DB::table('teacher_availabilities')
            ->where('teacher_id', $teacherId)
            ->first();
            
        if ($teacherAvailability && $teacherAvailability->holiday_mode) {
            return response()->json([
                'success' => false,
                'message' => 'This teacher is currently on holiday and not accepting new bookings.'
            ], 400);
        }

        // Get the first subject from the request or default to a general subject
        $selectedSubject = null;
        if (!empty($subjects) && is_array($subjects)) {
            $subjectName = $subjects[0]; // Get the first selected subject
            // Find the subject by name in the teacher's subjects
            // Get teacher profile ID first
            $teacherProfile = \App\Models\TeacherProfile::where('user_id', $teacherId)->first();
            
            if ($teacherProfile) {
                // Find the subject by name in the teacher's subjects using the template relationship
                $selectedSubject = \App\Models\Subject::where('teacher_profile_id', $teacherProfile->id)
                    ->whereHas('template', function($query) use ($subjectName) {
                        $query->where('name', 'like', '%' . $subjectName . '%');
                    })
                    ->first();
            }
        }

        // If no subject found, get the teacher's first available subject
        if (!$selectedSubject) {
            $teacherProfile = \App\Models\TeacherProfile::where('user_id', $teacherId)->first();
            if ($teacherProfile) {
                $selectedSubject = \App\Models\Subject::where('teacher_profile_id', $teacherProfile->id)
                    ->where('is_active', true)
                    ->first();
            }
        }

        // Fallback to a default subject if still no subject found
        if (!$selectedSubject) {
            $selectedSubject = \App\Models\Subject::where('is_active', true)->first();
        }

        // Verify wallet payment if selected
        if (in_array('wallet', $request->payment_methods)) {
            if (!$guardianWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Guardian wallet not found.'
                ], 400);
            }

            $requiredAmount = $request->amount;
            $walletBalance = (float)$guardianWallet->balance;

            // If currency is USD, convert to NGN for wallet comparison
            if ($request->currency === 'USD') {
                $requiredAmountNGN = $requiredAmount * 1500; // Convert USD to NGN
            } else {
                $requiredAmountNGN = $requiredAmount;
            }

            if ($walletBalance < $requiredAmountNGN) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient wallet balance.'
                ], 400);
            }

            // Deduct amount from wallet
            $guardianWallet->decrement('balance', $requiredAmountNGN);

            // Create wallet transaction record
            \App\Models\GuardianWalletTransaction::create([
                'wallet_id' => $guardianWallet->id,
                'transaction_type' => 'debit',
                'amount' => $requiredAmountNGN,
                'status' => 'completed',
                'description' => 'Class booking payment',
                'reference' => 'BOOKING-' . \Illuminate\Support\Str::random(10),
            ]);
        }

        DB::beginTransaction();
        
        try {
            // Determine who the booking is for
            $bookingFor = $request->input('booking_for');
            $studentId = null;
            
            if ($bookingFor === 'self') {
                // Guardian booking for themselves
                if (!$guardian->hasAnyRole('student')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Guardian does not have student role. Please contact support to enable learning for yourself.'
                    ], 400);
                }
                $studentId = $guardian->id;
            } else {
                // Guardian booking for a child
                $childId = $request->input('child_id');
                if (!$childId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Child ID is required when booking for a child.'
                    ], 400);
                }
                
                // Verify the child belongs to this guardian
                $guardianProfile = $guardian->guardianProfile;
                if (!$guardianProfile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Guardian profile not found.'
                    ], 400);
                }
                
                $child = $guardianProfile->students()->where('user_id', $childId)->first();
                if (!$child) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Child not found or does not belong to this guardian.'
                    ], 400);
                }
                
                $studentId = $childId;
            }

            // Create bookings for each date/availability combination
            $createdBookings = [];
            foreach ($dates as $date) {
                foreach ($availabilityIds as $availabilityId) {
                    $availability = \App\Models\TeacherAvailability::find($availabilityId);
                    if (!$availability) {
                        continue;
                    }

                    $startTime = \Carbon\Carbon::parse($availability->start_time);
                    $endTime = \Carbon\Carbon::parse($availability->end_time);
                    $durationMinutes = $startTime->diffInMinutes($endTime);

                    // Get teacher's current rates and lock them
                    $teacher = \App\Models\User::find($teacherId);
                    $teacherProfile = $teacher->teacherProfile;
                    $currencyService = app(\App\Services\CurrencyService::class);
                    
                    $hourlyRateNGN = $teacherProfile->hourly_rate_ngn ?? 0;
                    $hourlyRateUSD = $teacherProfile->hourly_rate_usd ?? 0;
                    $exchangeRate = $currencyService->getExchangeRate('NGN', 'USD');
                    
                    $booking = \App\Models\Booking::create([
                        'student_id' => $studentId, // Use determined student ID
                        'teacher_id' => $teacherId,
                        'subject_id' => $selectedSubject ? $selectedSubject->id : 1,
                        'booking_date' => $date,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                        'duration_minutes' => $durationMinutes,
                        'status' => 'pending',
                        'notes' => $noteToTeacher,
                        'created_by_id' => $guardian->id, // Guardian created the booking
                        'hourly_rate_ngn' => $hourlyRateNGN,
                        'hourly_rate_usd' => $hourlyRateUSD,
                        'rate_currency' => $teacherProfile->preferred_currency ?? 'NGN',
                        'exchange_rate_used' => $exchangeRate,
                        'rate_locked_at' => now(),
                    ]);

                    // Send notifications for booking creation
                    $this->bookingNotificationService->sendBookingCreatedNotifications($booking);

                    $createdBookings[] = $booking;
                }
            }

            // Teaching sessions will be created after teacher/admin approval
            // No need to create them here as guardian only books the session

            // Clear session data after successful booking
            $request->session()->forget('booking_session');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Booking created successfully!',
                'bookings' => $createdBookings,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage(),
            ], 500);
        }
    }
}
