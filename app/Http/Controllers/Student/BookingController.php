<?php

namespace App\Http\Controllers\Student;

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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function __construct(
        private BookingNotificationService $bookingNotificationService,
        private StudentSessionService $sessionService
    ) {}

    /**
     * Display student's bookings
     */
    public function index(Request $request)
    {
        $student = auth()->user();
        
        // Get bookings with related data
        $bookings = Booking::where('student_id', $student->id)
            ->with([
                'teacher.teacherProfile',
                'subject.template', 
                'teachingSession.teacherReviews' => function($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
                'bookingNotes' => function($query) {
                    $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
                },
                'history' => function($query) {
                    $query->latest()->take(1);
                }
            ])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($booking) use ($student) {
                $teacher = $booking->teacher;
                $subject = $booking->subject;
                $session = $booking->teachingSession;
                
                $subjectTemplate = $subject?->template;
                $teacherProfile = $teacher?->teacherProfile;
                
                // Get teacher and student notes from BookingNote
                $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
                $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
                $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();
                
                // Get student review for this session
                $studentReview = $session?->teacherReviews->where('student_id', $student->id)->first();
                
                return [
                    'id' => $booking->id,
                    'booking_uuid' => $booking->booking_uuid,
                    'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                    'teacher' => $teacher?->name ?? 'Unknown Teacher',
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
                'bookingNotes' => $booking->bookingNotes,
                    // TeachingSession data
                    'teachingSession' => $session ? [
                        'id' => $session->id,
                        'teacher_notes' => $session->teacher_notes ?: $teacherNotes?->content,
                        'student_notes' => $session->student_notes ?: $studentNotes?->content,
                        'student_rating' => $studentReview?->rating ?: $session->student_rating,
                        'teacher_rating' => $session->teacher_rating,
                        'meeting_platform' => $session->meeting_platform,
                        'recording_url' => $session->recording_url,
                        'completion_date' => $session->completion_date?->format('Y-m-d H:i:s'),
                        'zoom_join_url' => $session->zoom_join_url,
                        'google_meet_link' => $session->google_meet_link,
                        // Additional data from relationships
                        'student_review' => $studentReview?->review,
                        'booking_notes' => [
                            'teacher_note' => $teacherNotes?->content,
                            'student_note' => $studentNotes?->content,
                            'student_review' => $studentReviewNote?->content,
                        ],
                    ] : null,
                ];
            });

        // Categorize bookings
        $now = now();
        $today = today();
        
        $upcomingBookings = $bookings->filter(function ($booking) use ($today) {
            return $booking['booking_date_raw'] >= $today && 
                   in_array($booking['status'], ['Pending', 'Approved', 'Upcoming']);
        })->values();
        
        $ongoingBookings = $bookings->filter(function ($booking) use ($today, $now) {
            return $booking['booking_date_raw']->isToday() && 
                   $booking['start_time_raw'] <= $now && 
                   $booking['status'] === 'Upcoming';
        })->values();
        
        $completedBookings = $bookings->filter(function ($booking) use ($today) {
            return $booking['status'] === 'Completed' || 
                   ($booking['booking_date_raw'] < $today && 
                    in_array($booking['status'], ['Approved', 'Upcoming']));
        })->values();

        return Inertia::render('student/my-bookings', [
            'bookings' => [
                'upcoming' => $upcomingBookings,
                'ongoing' => $ongoingBookings,
                'completed' => $completedBookings,
            ],
            'stats' => $this->sessionService->getStudentStats($student->id)
        ]);
    }

    /**
     * Show individual booking details
     */
    public function show(Request $request, $id)
    {
        $student = auth()->user();
        
        $booking = Booking::where('student_id', $student->id)
            ->where('id', $id)
            ->with([
                'teacher.teacherProfile.subjects.template',
                'teacher.teacherReviews',
                'subject.template',
                'teachingSession.teacherReviews' => function($query) use ($student) {
                    $query->where('student_id', $student->id);
                },
                'bookingNotes' => function($query) {
                    $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
                },
                'history' => function($query) {
                    $query->latest()->take(5);
                }
            ])
            ->firstOrFail();

        $teacher = $booking->teacher;
        $subject = $booking->subject;
        $session = $booking->teachingSession;
        $subjectTemplate = $subject?->template;
        $teacherProfile = $teacher?->teacherProfile;
        
        // Get teacher and student notes from BookingNote
        $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
        $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
        $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();
        
        // Get student review for this session
        $studentReview = $session?->teacherReviews->where('student_id', $student->id)->first();

        // Format booking data
        $bookingData = [
            'id' => $booking->id,
            'booking_uuid' => $booking->booking_uuid,
            'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
            'teacher' => $teacher?->name ?? 'Unknown Teacher',
            'teacher_id' => $teacher?->id,
            'teacher_avatar' => $teacherProfile?->profile_image_url ?? '/images/default-avatar.png',
            'subject' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
            'date' => $booking->booking_date->format('d M Y'),
            'time' => $booking->start_time->format('H:i') . ' - ' . $booking->end_time->format('H:i'),
            'status' => ucfirst($booking->status),
            'imageUrl' => $subjectTemplate?->image_url ?? '/images/subjects/default.png',
            'meetingUrl' => $session?->zoom_join_url,
            'session_uuid' => $session?->session_uuid,
            'duration' => $booking->duration_minutes ?? 60,
            'notes' => $booking->notes,
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
            // TeachingSession data
            'teachingSession' => $session ? [
                'id' => $session->id,
                'teacher_notes' => $session->teacher_notes ?: $teacherNotes?->content,
                'student_notes' => $session->student_notes ?: $studentNotes?->content,
                'student_rating' => $studentReview?->rating ?: $session->student_rating,
                'teacher_rating' => $session->teacher_rating,
                'meeting_platform' => $session->meeting_platform,
                'recording_url' => $session->recording_url,
                'completion_date' => $session->completion_date?->format('Y-m-d H:i:s'),
                'zoom_join_url' => $session->zoom_join_url,
                'google_meet_link' => $session->google_meet_link,
                // Additional data from relationships
                'student_review' => $studentReview?->review,
                'booking_notes' => [
                    'teacher_note' => $teacherNotes?->content,
                    'student_note' => $studentNotes?->content,
                    'student_review' => $studentReviewNote?->content,
                ],
            ] : null,
        ];

        // Get teacher subjects
        $teacherSubjects = $teacherProfile?->subjects ?? collect([]);
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

        return Inertia::render('student/class-details', [
            'booking' => $bookingData,
            'teacher' => $teacherData,
        ]);
    }

    /**
     * Show the book class page
     */
    public function create(Request $request)
    {
        // Clear any existing booking session when starting fresh
        $request->session()->forget('booking_session');
        
        $teacherId = $request->query('teacherId') ?? $request->query('teacher_id');
        $teacher = null;

        if ($teacherId) {
            $teacher = User::where('role', 'teacher')
                ->with([
                    'teacherProfile.subjects.template',
                    'teacherAvailabilities' => function ($query) {
                        $query->where('is_active', true)->orderBy('day_of_week')->orderBy('start_time');
                    },
                    'teacherReviews'
                ])
                ->find($teacherId);
                
            // Check if teacher is in holiday mode
            if ($teacher) {
                $availability = \DB::table('teacher_availabilities')
                    ->where('teacher_id', $teacherId)
                    ->first();
                    
                if ($availability && $availability->holiday_mode) {
                    return redirect()->route('student.browse-teachers')
                        ->with('error', 'This teacher is currently on holiday and not accepting new bookings.');
                }
            }
        }

        // Format teacher data for the page header
        $formattedTeacher = null;
        if ($teacher) {
            $profile = $teacher->teacherProfile;
            $availabilities = $teacher->teacherAvailabilities ?? collect([]);

            // Format availability string and process actual availability data
            $availabilityString = 'Available on request';
            $processedAvailabilities = [];
            
            if ($availabilities->isNotEmpty()) {
                // Group availabilities by day of week for easier processing
                $availabilitiesByDay = $availabilities->groupBy('day_of_week');
                
                // Create availability string from actual data
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $activeDays = $availabilitiesByDay->keys()->sort()->map(function($dayNum) use ($dayNames) {
                    return $dayNames[$dayNum];
                })->take(3)->implode(', ');
                
                if ($activeDays) {
                    $firstSlot = $availabilities->first();
                    $timeRange = date('g:i A', strtotime($firstSlot->start_time)) . ' - ' . date('g:i A', strtotime($firstSlot->end_time));
                    $availabilityString = $activeDays . ' | ' . $timeRange;
                }
                
                // Process availability data for frontend
                $processedAvailabilities = $availabilities->map(function ($availability) {
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
                })->toArray();
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
                        'location' => $recommendedProfile->location,
                        'rating' => $recommendedProfile->rating ? (float)$recommendedProfile->rating : null,
                        'price' => $recommendedProfile->hourly_rate_usd ? '$' . (int)$recommendedProfile->hourly_rate_usd : 'Price not set',
                        'avatarUrl' => null, // Use initials instead of images
                    ];
                })->toArray();

            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => null, // Use initials instead of images
                'rating' => $profile->rating ? (float)$profile->rating : null,
                'reviews_count' => (int)($profile->reviews_count ?? 0),
                'subjects' => $subjects,
                'location' => $profile->location ?? 'Location not set',
                'availability' => $availabilityString,
                'verified' => (bool)($profile->verified ?? false),
                'hourly_rate_usd' => $profile->hourly_rate_usd ? (float)$profile->hourly_rate_usd : null,
                'hourly_rate_ngn' => $profile->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : null,
                'bio' => $profile->bio ?? '',
                'experience_years' => $profile->experience_years ?? '5+ years',
                'availabilities' => $processedAvailabilities,
                'recommended_teachers' => $recommendedTeachers,
            ];
        }

        return Inertia::render('student/book-class', [
            'teacher' => $formattedTeacher,
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
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        $teacher = User::where('role', 'teacher')->with(['teacherProfile.subjects.template'])->find($teacherId);
        
        if (!$teacher) {
            return redirect()->route('student.browse-teachers')
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
            'subjects' => $profile?->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'template' => $subject->template ? [
                        'name' => $subject->template->name
                    ] : null
                ];
            }) ?? [],
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

        return Inertia::render('student/session-details', [
            'teacher_id' => (int) $teacherId,
            'dates' => $dates,
            'availability_ids' => array_map('intval', $availabilityIds),
            'time_slots' => $timeSlots,
            'subjects' => $subjects,
            'note_to_teacher' => $noteToTeacher,
            'teacher' => $formattedTeacher,
            'previous_page' => '/student/book-class?teacherId=' . $teacherId,
        ]);
    }

    /**
     * Show the pricing and payment page (POST from session details)
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
     * Show the pricing and payment page (GET for direct navigation)
     */
    public function pricingPaymentGet(Request $request)
    {
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
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        $teacher = User::where('role', 'teacher')->with(['teacherProfile'])->find($teacherId);
        
        if (!$teacher) {
            return redirect()->route('student.browse-teachers')
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

        // Get student wallet balance
        $studentWallet = auth()->user()->studentWallet;
        $walletBalanceUSD = 0;
        $walletBalanceNGN = 0;
        
        if ($studentWallet) {
            $walletBalanceNGN = (float)$studentWallet->balance;
            $walletBalanceUSD = $walletBalanceNGN / 1500; // Approximate conversion rate
        }

        return Inertia::render('student/pricing-payment', [
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
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'country' => auth()->user()->country ?? 'NG',
            ],
        ]);
    }

    /**
     * Process booking payment
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
        ]);

        $student = auth()->user();
        $studentWallet = $student->studentWallet;
        
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
            return redirect()->route('student.browse-teachers')
                ->with('error', 'This teacher is currently on holiday and not accepting new bookings.');
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
            if (!$studentWallet) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student wallet not found.'
                ], 400);
            }

            $requiredAmount = $request->amount;
            $walletBalance = (float)$studentWallet->balance;

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
            $studentWallet->decrement('balance', $requiredAmountNGN);

            // Create wallet transaction record
            \App\Models\WalletTransaction::create([
                'wallet_id' => $studentWallet->id,
                'transaction_type' => 'debit',
                'amount' => $requiredAmountNGN,
                'status' => 'completed',
                'description' => 'Class booking payment',
                'reference' => 'BOOKING-' . Str::random(10),
            ]);
        }

        DB::beginTransaction();
        
        try {

            // Create bookings for each date/availability combination
            $createdBookings = [];
            foreach ($dates as $date) {
                foreach ($availabilityIds as $availabilityId) {
                    $availability = TeacherAvailability::find($availabilityId);
                    if (!$availability) {
                        continue;
                    }

                    $startTime = \Carbon\Carbon::parse($availability->start_time);
                    $endTime = \Carbon\Carbon::parse($availability->end_time);
                    $durationMinutes = $startTime->diffInMinutes($endTime);

                    // Get teacher's current rates and lock them
                    $teacher = User::find($teacherId);
                    $teacherProfile = $teacher->teacherProfile;
                    $currencyService = app(\App\Services\CurrencyService::class);
                    
                    $hourlyRateNGN = $teacherProfile->hourly_rate_ngn ?? 0;
                    $hourlyRateUSD = $teacherProfile->hourly_rate_usd ?? 0;
                    $exchangeRate = $currencyService->getExchangeRate('NGN', 'USD');
                    
                    $booking = Booking::create([
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'subject_id' => $selectedSubject ? $selectedSubject->id : 1,
                        'booking_date' => $date,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                        'duration_minutes' => $durationMinutes,
                        'status' => 'pending',
                        'notes' => $noteToTeacher,
                        'created_by_id' => $student->id,
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
            // No need to create them here as student only books the session

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

    /**
     * Cancel a booking
     */
    public function cancel(Request $request, $id)
    {
        try {
            $booking = Booking::findOrFail($id);
            
            // Check if the booking belongs to the authenticated student
            if ($booking->student_id !== auth()->id()) {
                return back()->withErrors(['error' => 'Unauthorized to cancel this booking.']);
            }
            
            // Check if booking can be cancelled with specific reasons
            if (!in_array($booking->status, ['pending', 'approved', 'confirmed', 'upcoming', 'Upcoming'])) {
                $reason = $this->getCancellationNotAllowedReason($booking->status);
                return back()->withErrors(['error' => $reason]);
            }
            
            // Check timing restrictions based on booking status
            // Handle different start_time formats (could be time string or datetime)
            if ($booking->start_time instanceof \Carbon\Carbon) {
                $startTime = $booking->start_time->format('H:i:s');
            } else {
                $startTime = $booking->start_time;
            }
            
            $bookingDateTime = Carbon::parse($booking->booking_date->format('Y-m-d') . ' ' . $startTime);
            $hoursUntilBooking = now()->diffInHours($bookingDateTime, false);
            
            // If booking has already started or passed
            if ($hoursUntilBooking < 0) {
                return back()->withErrors(['error' => 'Cannot cancel a booking that has already started or passed.']);
            }
            
            // Pending bookings can always be cancelled (not even approved yet)
            if ($booking->status === 'pending') {
                // No time restrictions for pending bookings
            } 
            // Upcoming bookings can be cancelled with reasonable notice
            elseif (in_array($booking->status, ['upcoming', 'Upcoming'])) {
                if ($hoursUntilBooking < 2) {
                    return back()->withErrors(['error' => 'Cannot cancel upcoming bookings less than 2 hours before the scheduled time. Please contact support for assistance.']);
                }
            }
            // Approved/Confirmed bookings have a 2-hour minimum notice
            elseif (in_array($booking->status, ['approved', 'confirmed'])) {
                if ($hoursUntilBooking < 2) {
                    return back()->withErrors(['error' => 'Cannot cancel confirmed bookings less than 2 hours before the scheduled time. Please contact support for assistance.']);
                }
            }
            
            DB::beginTransaction();
            
            // Update booking status to cancelled
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancelled_by' => 'student'
            ]);
            
            // If payment was made, process refund (optional - implement refund logic here)
            // You might want to refund to student wallet or create a refund request
            
            // Send notification to teacher about cancellation
            // Implement notification logic here if needed
            
            DB::commit();
            
            // Return success response for Inertia
            return redirect()->route('student.my-bookings')->with('success', 'Booking cancelled successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors(['error' => 'Failed to cancel booking: ' . $e->getMessage()]);
        }
    }

    /**
     * Get specific reason why booking cannot be cancelled based on status
     */
    private function getCancellationNotAllowedReason(string $status): string
    {
        return match(strtolower($status)) {
            'completed' => 'Cannot cancel a booking that has already been completed.',
            'cancelled' => 'This booking has already been cancelled.',
            'rejected' => 'Cannot cancel a booking that was rejected by the teacher.',
            'in_progress', 'ongoing' => 'Cannot cancel a booking that is currently in progress.',
            'expired' => 'Cannot cancel an expired booking.',
            'no_show' => 'Cannot cancel a booking marked as no-show.',
            default => 'This booking cannot be cancelled due to its current status: ' . $status . '.'
        };
    }

    /**
     * Save student review for a completed booking
     */
    public function saveReview(Request $request, Booking $booking)
    {
        $student = auth()->user();
        
        // Validate that the booking belongs to the student
        if ($booking->student_id !== $student->id) {
            return back()->withErrors(['error' => 'You can only review your own bookings.']);
        }
        
        // Validate that the booking is completed
        if (!in_array(strtolower($booking->status), ['completed', 'finished'])) {
            return back()->withErrors(['error' => 'You can only review completed bookings.']);
        }
        
        // Validate the request
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            $session = $booking->teachingSession;
            
            if ($session) {
                // Update or create teacher review
                $teacherReview = TeacherReview::updateOrCreate(
                    [
                        'session_id' => $session->id,
                        'student_id' => $student->id,
                        'teacher_id' => $booking->teacher_id,
                    ],
                    [
                        'rating' => $request->rating,
                        'review' => $request->review,
                        'reviewed_at' => now(),
                    ]
                );
                
                // Also update the session with student rating and notes
                $session->update([
                    'student_rating' => $request->rating,
                    'student_notes' => $request->review,
                ]);
            }
            
            // Create or update booking note for student review
            BookingNote::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'user_id' => $student->id,
                    'note_type' => 'student_review',
                ],
                [
                    'content' => $request->review,
                    'created_at' => now(),
                ]
            );
            
            DB::commit();
            
            return back()->with('success', 'Your review has been saved successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors(['error' => 'Failed to save review: ' . $e->getMessage()]);
        }
    }

    /**
     * Save student personal notes for a booking
     */
    public function savePersonalNotes(Request $request, Booking $booking)
    {
        $student = auth()->user();
        
        // Validate that the booking belongs to the student
        if ($booking->student_id !== $student->id) {
            return back()->withErrors(['error' => 'You can only add notes to your own bookings.']);
        }
        
        // Validate the request
        $request->validate([
            'personal_notes' => 'nullable|string|max:1000',
        ]);
        
        try {
            DB::beginTransaction();
            
            $session = $booking->teachingSession;
            
            if ($session) {
                // Update the session with student notes
                $session->update([
                    'student_notes' => $request->personal_notes,
                ]);
            }
            
            // Create or update booking note for student personal notes
            BookingNote::updateOrCreate(
                [
                    'booking_id' => $booking->id,
                    'user_id' => $student->id,
                    'note_type' => 'student_note',
                ],
                [
                    'content' => $request->personal_notes,
                    'created_at' => now(),
                ]
            );
            
            DB::commit();
            
            return back()->with('success', 'Your personal notes have been saved successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()->withErrors(['error' => 'Failed to save personal notes: ' . $e->getMessage()]);
        }
    }

    /**
     * Download booking summary as PDF
     */
    public function downloadSummaryPdf(Request $request, Booking $booking)
    {
        $student = $request->user();
        
        // Ensure the booking belongs to the student
        if ($booking->student_id !== $student->id) {
            abort(403, 'Unauthorized access to booking');
        }

        // Load all necessary relationships
        $booking->load([
            'teacher',
            'subject',
            'teachingSession.teacherReviews' => function($query) use ($student) {
                $query->where('student_id', $student->id);
            },
            'bookingNotes' => function($query) {
                $query->whereIn('note_type', ['teacher_note', 'student_note', 'student_review']);
            }
        ]);

        // Get the teaching session
        $session = $booking->teachingSession;
        
        // Get notes
        $teacherNotes = $booking->bookingNotes->where('note_type', 'teacher_note')->first();
        $studentNotes = $booking->bookingNotes->where('note_type', 'student_note')->first();
        $studentReviewNote = $booking->bookingNotes->where('note_type', 'student_review')->first();
        
        // Get student review from teacher reviews
        $studentReview = $session?->teacherReviews->where('student_id', $student->id)->first();

        // Prepare data for PDF
        $data = [
            'booking' => $booking,
            'session' => $session,
            'teacher' => $booking->teacher,
            'subject' => $booking->subject,
            'student' => $student,
            'teacherNotes' => $teacherNotes?->content,
            'studentNotes' => $studentNotes?->content,
            'studentReview' => $studentReviewNote?->content ?? $studentReview?->feedback,
            'rating' => $studentReview?->rating ?? 0,
            'sessionDate' => $session?->session_date ?? $booking->booking_date,
            'startTime' => $session?->start_time ?? $booking->start_time,
            'endTime' => $session?->end_time ?? $booking->end_time,
            'duration' => $session?->duration_minutes ?? $booking->duration_minutes,
            'meetingPlatform' => $session?->meeting_platform ?? 'Zoom',
            'meetingUrl' => $session?->meeting_url ?? $session?->zoom_join_url,
            'recordingUrl' => $session?->recording_url,
            'materialsUrl' => $session?->materials_url,
        ];

        // Generate PDF
        $pdf = Pdf::loadView('pdf.booking-summary', $data);
        
        // Set filename
        $filename = 'Class_Summary_' . $booking->booking_uuid . '_' . now()->format('Y-m-d') . '.pdf';
        
        // Return PDF download
        return $pdf->download($filename);
    }
}