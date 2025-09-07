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
use Inertia\Inertia;
use App\Models\User;
use App\Services\BookingNotificationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function __construct(
        private BookingNotificationService $bookingNotificationService
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
                'teachingSession',
                'history' => function($query) {
                    $query->latest()->take(1);
                }
            ])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get()
            ->map(function ($booking) {
                $teacher = $booking->teacher;
                $subject = $booking->subject;
                $session = $booking->teachingSession;
                
                $subjectTemplate = $subject?->template;
                $teacherProfile = $teacher?->teacherProfile;
                
                return [
                    'id' => $booking->id,
                    'booking_uuid' => $booking->booking_uuid,
                    'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
                    'teacher' => $teacher?->name ?? 'Unknown Teacher',
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
                ];
            });

        // Categorize bookings
        $now = now();
        $today = today();
        
        $upcomingBookings = $bookings->filter(function ($booking) use ($today) {
            return $booking['booking_date_raw'] >= $today && 
                   in_array($booking['status'], ['Pending', 'Approved', 'Confirmed']);
        })->values();
        
        $ongoingBookings = $bookings->filter(function ($booking) use ($today, $now) {
            return $booking['booking_date_raw']->isToday() && 
                   $booking['start_time_raw'] <= $now && 
                   $booking['status'] === 'Confirmed';
        })->values();
        
        $completedBookings = $bookings->filter(function ($booking) use ($today) {
            return $booking['status'] === 'Completed' || 
                   ($booking['booking_date_raw'] < $today && 
                    in_array($booking['status'], ['Approved', 'Confirmed']));
        })->values();

        return Inertia::render('student/my-bookings', [
            'bookings' => [
                'upcoming' => $upcomingBookings,
                'ongoing' => $ongoingBookings,
                'completed' => $completedBookings,
            ],
            'stats' => [
                'total' => $bookings->count(),
                'upcoming' => $upcomingBookings->count(),
                'ongoing' => $ongoingBookings->count(),
                'completed' => $completedBookings->count(),
            ]
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
                'teachingSession',
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

        // Format booking data
        $bookingData = [
            'id' => $booking->id,
            'booking_uuid' => $booking->booking_uuid,
            'title' => $subjectTemplate?->name ?? $subject?->name ?? 'Unknown Subject',
            'teacher' => $teacher?->name ?? 'Unknown Teacher',
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
        }

        // dd($teacher);
        // return;
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
        // Check for reschedule session data first
        $rescheduleData = $request->hasSession() ? $request->session()->get('reschedule_session', []) : [];
        $isReschedule = !empty($rescheduleData);
        
        if ($isReschedule) {
            $teacherId = $rescheduleData['teacher_id'] ?? null;
            $dates = $rescheduleData['dates'] ?? [];
            $availabilityIds = $rescheduleData['availability_ids'] ?? [];
            $subjects = $rescheduleData['subjects'] ?? [];
            $noteToTeacher = $rescheduleData['note_to_teacher'] ?? '';
        } else {
            // For GET requests, try to get data from session first, then from request
            $sessionData = $request->hasSession() ? $request->session()->get('booking_session', []) : [];
            
            $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
            $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
            $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
            $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
            $noteToTeacher = $request->input('note_to_teacher') ?? $sessionData['note_to_teacher'] ?? '';
        }
        
        if (!$teacherId || empty($dates) || empty($availabilityIds)) {
            // Redirect back to book class if missing required data
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        // Create a new request with the data
        $newRequest = new Request([
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'subjects' => $subjects,
            'note_to_teacher' => $noteToTeacher,
            'is_reschedule' => $isReschedule,
            'reschedule_data' => $isReschedule ? $rescheduleData : null,
        ]);

        return $this->renderSessionDetails($newRequest);
    }

    /**
     * Common method to render session details page
     */
    private function renderSessionDetails(Request $request)
    {
        $teacherId = $request->input('teacher_id');
        $dates = $request->input('dates', []);
        $availabilityIds = $request->input('availability_ids', []);
        $isReschedule = $request->input('is_reschedule', false);
        $rescheduleData = $request->input('reschedule_data');

        // Get teacher info for context
        $teacher = null;
        if ($teacherId) {
            $teacher = User::where('role', 'teacher')
                ->with(['teacherProfile.subjects.template'])
                ->find($teacherId);
        }

        $formattedTeacher = null;
        if ($teacher) {
            $subjects = [];
            $profile = $teacher->teacherProfile;
            
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
                'subjects' => $subjects,
                'recommended_teachers' => $recommendedTeachers,
            ];
        }

        // Determine previous page based on context
        $previousPage = null;
        if ($isReschedule) {
            // For reschedule, go back to book-class page so user can modify selections
            $previousPage = '/student/book-class?teacher_id=' . $teacherId . '&reschedule_booking_id=' . ($rescheduleData['booking_id'] ?? '');
        } else {
            $previousPage = '/student/book-class?teacherId=' . $teacherId;
        }

        return Inertia::render('student/session-details', [
            'teacher_id' => (int) $teacherId,
            'dates' => $dates,
            'availability_ids' => array_map('intval', $availabilityIds),
            'teacher' => $formattedTeacher,
            'is_reschedule' => $isReschedule,
            'reschedule_data' => $rescheduleData,
            'previous_page' => $previousPage,
        ]);
    }

    /**
     * Show the pricing and payment page (POST from session details)
     */
    public function pricingPayment(Request $request)
    {
        // Check if this is a reschedule request
        $isReschedule = $request->has('is_reschedule') && $request->input('is_reschedule');
        
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
        
        if ($isReschedule) {
            // Store reschedule session data
            $request->session()->put('reschedule_session', [
                'booking_id' => $request->input('booking_id'),
                'teacher_id' => $teacherId,
                'teacher_data' => $teacherData,
                'dates' => $request->input('dates', []),
                'availability_ids' => $request->input('availability_ids', []),
                'subjects' => $request->input('subjects', []),
                'note_to_teacher' => $request->input('note_to_teacher', ''),
            ]);
        } else {
            // Store booking session data in session for page refresh support
            $request->session()->put('booking_session', [
                'teacher_id' => $teacherId,
                'teacher_data' => $teacherData,
                'dates' => $request->input('dates', []),
                'availability_ids' => $request->input('availability_ids', []),
                'subjects' => $request->input('subjects', []),
                'note_to_teacher' => $request->input('note_to_teacher', ''),
            ]);
        }
        
        return $this->renderPricingPayment($request);
    }

    /**
     * Show the pricing and payment page (GET for direct navigation)
     */
    public function pricingPaymentGet(Request $request)
    {
        // Check if this is a reschedule request - use request parameter first, then session data
        $isReschedule = $request->has('is_reschedule') && $request->input('is_reschedule');
        $rescheduleData = [];
        
        if ($isReschedule) {
            $rescheduleData = $request->hasSession() ? $request->session()->get('reschedule_session', []) : [];
        }
        
        if ($isReschedule) {
            $teacherId = $rescheduleData['teacher_id'] ?? null;
            $dates = $rescheduleData['dates'] ?? [];
            $availabilityIds = $rescheduleData['availability_ids'] ?? [];
            $subjects = $rescheduleData['subjects'] ?? [];
            $noteToTeacher = $rescheduleData['note_to_teacher'] ?? '';
        } else {
            // For GET requests, try to get data from session first, then from request
            $sessionData = $request->hasSession() ? $request->session()->get('booking_session', []) : [];
            
            $teacherId = $request->input('teacher_id') ?? $sessionData['teacher_id'] ?? null;
            $dates = $request->input('dates') ?? $sessionData['dates'] ?? [];
            $availabilityIds = $request->input('availability_ids') ?? $sessionData['availability_ids'] ?? [];
            $subjects = $request->input('subjects') ?? $sessionData['subjects'] ?? [];
            $noteToTeacher = $request->input('note_to_teacher') ?? $sessionData['note_to_teacher'] ?? '';
        }
        
        // For reschedule mode, we don't need availability_ids or subjects validation since user will select new times and subjects
        // For new bookings, we need both subjects and availability_ids
        $validationFailed = false;
        if (!$teacherId || empty($dates)) {
            $validationFailed = true;
        } elseif (!$isReschedule && (empty($subjects) || empty($availabilityIds))) {
            $validationFailed = true;
        }
        
        if ($validationFailed) {
            \Log::info('Pricing Payment GET validation failed', [
                'teacher_id' => $teacherId,
                'dates' => $dates,
                'subjects' => $subjects,
                'availability_ids' => $availabilityIds,
                'is_reschedule' => $isReschedule,
                'reschedule_data' => $rescheduleData
            ]);
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        // Create a new request with the data
        $newRequest = new Request([
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'subjects' => $subjects,
            'note_to_teacher' => $noteToTeacher,
            'is_reschedule' => $isReschedule,
            'reschedule_data' => $isReschedule ? $rescheduleData : null,
        ]);

        return $this->renderPricingPayment($newRequest);
    }

    /**
     * Common method to render pricing and payment page
     */
    private function renderPricingPayment(Request $request)
    {
        $teacherId = $request->input('teacher_id');
        $dates = $request->input('dates', []);
        $availabilityIds = $request->input('availability_ids', []);
        $subjects = $request->input('subjects', []);
        $noteToTeacher = $request->input('note_to_teacher', '');
        $isReschedule = $request->input('is_reschedule', false);
        $rescheduleData = $request->input('reschedule_data');

        // Debug logging
        \Log::info('Pricing Payment Debug', [
            'teacher_id' => $teacherId,
            'dates' => $dates,
            'availability_ids' => $availabilityIds,
            'subjects' => $subjects,
            'is_reschedule' => $isReschedule,
            'reschedule_data' => $rescheduleData,
            'session_data' => $request->hasSession() ? $request->session()->get('booking_session', []) : 'No session available',
            'reschedule_session_data' => $request->hasSession() ? $request->session()->get('reschedule_session', []) : 'No reschedule session available'
        ]);

        // Get teacher info for pricing
        $teacher = null;
        $formattedTeacher = null;
        
        // Get teacher data - try reschedule session first, then fetch from database
        if ($isReschedule && $rescheduleData && isset($rescheduleData['teacher_data'])) {
            $formattedTeacher = $rescheduleData['teacher_data'];
            \Log::info('Using teacher data from reschedule session', [
                'teacher_data' => $formattedTeacher
            ]);
        } elseif ($teacherId) {
            $teacher = User::where('role', 'teacher')
                ->with(['teacherProfile'])
                ->find($teacherId);
            
            \Log::info('Teacher found', [
                'teacher_id' => $teacherId,
                'teacher_exists' => $teacher ? 'yes' : 'no',
                'has_profile' => $teacher && $teacher->teacherProfile ? 'yes' : 'no'
            ]);
        }
        
        // If still no teacher data, try to get it from the session
        if (!$formattedTeacher && $request->hasSession()) {
            if ($isReschedule) {
                $rescheduleSessionData = $request->session()->get('reschedule_session', []);
                if (isset($rescheduleSessionData['teacher_data'])) {
                    $formattedTeacher = $rescheduleSessionData['teacher_data'];
                    \Log::info('Using teacher data from reschedule session (fallback)', [
                        'teacher_data' => $formattedTeacher
                    ]);
                }
            } else {
                $bookingSessionData = $request->session()->get('booking_session', []);
                if (isset($bookingSessionData['teacher_data'])) {
                    $formattedTeacher = $bookingSessionData['teacher_data'];
                    \Log::info('Using teacher data from booking session (fallback)', [
                        'teacher_data' => $formattedTeacher
                    ]);
                }
            }
        }

        // Get student wallet balance
        $studentWallet = auth()->user()->studentWallet;
        $walletBalanceUSD = 0;
        $walletBalanceNGN = 0;
        
        if ($studentWallet) {
            // For now, we assume the balance is stored in NGN and we need to convert
            // This might need adjustment based on your actual wallet implementation
            $walletBalanceNGN = (float)$studentWallet->balance;
            // Convert NGN to USD using a simple rate (you might want to use a proper exchange rate service)
            $walletBalanceUSD = $walletBalanceNGN / 1500; // Approximate conversion rate
        }

        // Format teacher data if not already formatted from reschedule session
        if (!$formattedTeacher && $teacher) {
            $profile = $teacher->teacherProfile;
            
            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'hourly_rate_usd' => $profile?->hourly_rate_usd ? (float)$profile->hourly_rate_usd : 25.0, // Default rate
                'hourly_rate_ngn' => $profile?->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : 37500.0, // Default rate
            ];
        }

        // For reschedule mode, if subjects are empty, provide a default subject
        $finalSubjects = $subjects;
        if ($isReschedule && empty($subjects)) {
            $finalSubjects = ['General Tutoring']; // Default subject for reschedule
        }

        // Get time slot information for the selected availability IDs
        $timeSlots = [];
        if (!empty($availabilityIds)) {
            $availabilities = \App\Models\TeacherAvailability::whereIn('id', $availabilityIds)
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

        return Inertia::render('student/pricing-payment', [
            'teacher_id' => (int) $teacherId,
            'dates' => $dates,
            'availability_ids' => array_map('intval', $availabilityIds),
            'time_slots' => $timeSlots,
            'subjects' => $finalSubjects,
            'note_to_teacher' => $noteToTeacher,
            'is_reschedule' => $isReschedule,
            'reschedule_data' => $rescheduleData,
            'teacher' => $formattedTeacher,
            'wallet_balance_usd' => $walletBalanceUSD,
            'wallet_balance_ngn' => $walletBalanceNGN,
            'user' => [
                'id' => auth()->user()->id,
                'name' => auth()->user()->name,
                'email' => auth()->user()->email,
                'country' => auth()->user()->country ?? 'NG', // Default to Nigeria if not set
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
            'is_reschedule' => 'boolean',
            'booking_id' => 'required_if:is_reschedule,true|integer|exists:bookings,id',
        ]);

        $student = auth()->user();
        $studentWallet = $student->studentWallet;

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
                'description' => 'Class booking payment'
            ]);
        }

        // Handle reschedule vs new booking
        $isReschedule = $request->boolean('is_reschedule');
        $originalBooking = null;
        
        if ($isReschedule) {
            $originalBooking = Booking::findOrFail($request->booking_id);
            
            // Ensure the booking belongs to this student
            if ($originalBooking->student_id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to booking.'
                ], 403);
            }
            
            // Check if booking can be rescheduled
            if (!in_array($originalBooking->status, ['pending', 'approved', 'confirmed'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'This booking cannot be rescheduled.'
                ], 400);
            }
        }

        // Create actual booking and session records
        DB::beginTransaction();
        
        try {
            // Get teacher availability records to extract time information
            $availabilities = TeacherAvailability::whereIn('id', $request->availability_ids)
                ->orderBy('start_time')
                ->get();
            
            if ($availabilities->isEmpty()) {
                throw new \Exception('No valid availability slots found.');
            }
            
            // Get or create subject record
            $subject = null;
            if (!empty($request->subjects)) {
                // For now, use the first subject. In future, could handle multiple subjects per booking
                $subjectName = $request->subjects[0];
                $subjectTemplate = SubjectTemplates::where('name', $subjectName)->first();
                
                if ($subjectTemplate) {
                    // Get teacher profile
                    $teacherProfile = TeacherProfile::where('user_id', $request->teacher_id)->first();
                    
                    if ($teacherProfile) {
                        // Find or create a subject record for this teacher-template combination
                        $subject = Subject::where('teacher_profile_id', $teacherProfile->id)
                            ->where('subject_template_id', $subjectTemplate->id)
                            ->first();
                            
                        if (!$subject) {
                            $subject = Subject::create([
                                'teacher_profile_id' => $teacherProfile->id,
                                'subject_template_id' => $subjectTemplate->id,
                                'teacher_notes' => 'Auto-created for booking',
                                'is_active' => true,
                            ]);
                        }
                    }
                }
            }
            
            if (!$subject) {
                throw new \Exception('Subject not found or could not be created.');
            }
            
            $createdBookings = [];
            $dates = $request->dates;
            
            if ($isReschedule) {
                // For reschedule, update the existing booking
                $oldData = [
                    'booking_date' => $originalBooking->booking_date,
                    'start_time' => $originalBooking->start_time,
                    'end_time' => $originalBooking->end_time,
                ];
                
                // Find availabilities for the first date's day of week
                $dateObj = \Carbon\Carbon::parse($dates[0]);
                $dayOfWeek = $dateObj->dayOfWeek;
                
                $dayAvailabilities = $availabilities->filter(function($availability) use ($dayOfWeek) {
                    return $availability->day_of_week === $dayOfWeek;
                });
                
                if ($dayAvailabilities->isEmpty()) {
                    throw new \Exception('No valid availability slots found for the selected date.');
                }
                
                // Use first and last availability for timing
                $firstAvailability = $dayAvailabilities->first();
                $lastAvailability = $dayAvailabilities->last();
                
                // Calculate session duration
                $startTime = \Carbon\Carbon::parse($firstAvailability->start_time);
                $endTime = \Carbon\Carbon::parse($lastAvailability->end_time);
                $durationMinutes = $startTime->diffInMinutes($endTime);
                
                $newData = [
                    'booking_date' => $dates[0],
                    'start_time' => $firstAvailability->start_time,
                    'end_time' => $lastAvailability->end_time,
                    'duration_minutes' => $durationMinutes,
                    'rescheduled_by_id' => $student->id,
                    'rescheduled_at' => now(),
                    'reschedule_reason' => 'Student requested reschedule',
                ];
                
                // Update the original booking
                $originalBooking->update($newData);
                
                // Update teaching session if exists
                if ($originalBooking->teachingSession) {
                    $originalBooking->teachingSession->update([
                        'session_date' => $dates[0],
                        'start_time' => $firstAvailability->start_time,
                        'end_time' => $lastAvailability->end_time,
                    ]);
                }
                
                // Create booking history entry
                \App\Models\BookingHistory::create([
                    'booking_id' => $originalBooking->id,
                    'action' => 'rescheduled',
                    'previous_data' => $oldData,
                    'new_data' => $newData,
                    'performed_by_id' => $student->id,
                    'notes' => 'Student requested reschedule',
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
                
                // Send reschedule notifications
                $this->sendRescheduleNotifications($originalBooking, $oldData, $newData);
                
                $createdBookings[] = $originalBooking;
            } else {
                // Create a booking for each selected date (new booking)
                foreach ($dates as $date) {
                // Find availabilities for this specific date's day of week
                $dateObj = \Carbon\Carbon::parse($date);
                $dayOfWeek = $dateObj->dayOfWeek;
                
                $dayAvailabilities = $availabilities->filter(function($availability) use ($dayOfWeek) {
                    return $availability->day_of_week === $dayOfWeek;
                });
                
                if ($dayAvailabilities->isEmpty()) {
                    continue; // Skip this date if no availabilities
                }
                
                // Use first and last availability for timing
                $firstAvailability = $dayAvailabilities->first();
                $lastAvailability = $dayAvailabilities->last();
                
                // Calculate session duration
                $startTime = \Carbon\Carbon::parse($firstAvailability->start_time);
                $endTime = \Carbon\Carbon::parse($lastAvailability->end_time);
                $durationMinutes = $startTime->diffInMinutes($endTime);
                
                // Create booking record for this date
                $booking = Booking::create([
                    'booking_uuid' => Str::uuid(),
                    'student_id' => $student->id,
                    'teacher_id' => $request->teacher_id,
                    'subject_id' => $subject->id,
                    'booking_date' => $date,
                    'start_time' => $firstAvailability->start_time,
                    'end_time' => $lastAvailability->end_time,
                    'duration_minutes' => $durationMinutes,
                    'status' => 'pending', // Require teacher/admin approval
                    'notes' => $request->note_to_teacher,
                    'created_by_id' => $student->id,
                    'total_fee' => $request->amount / count($dates), // Split fee across dates
                ]);
                
                $createdBookings[] = $booking;
                
                // Send booking created notifications for each booking
                $this->bookingNotificationService->sendBookingCreatedNotifications($booking);
            }
            }
            
            if (empty($createdBookings)) {
                throw new \Exception('No valid bookings could be created for the selected dates.');
            }
            
            DB::commit();
            
            // Clear session data after successful booking/reschedule
            if ($isReschedule) {
                $request->session()->forget('reschedule_session');
            } else {
                $request->session()->forget('booking_session');
            }
            
            $message = $isReschedule 
                ? 'Booking rescheduled successfully!'
                : count($createdBookings) . ' booking request(s) submitted successfully! Your teacher will review and approve them soon.';
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'booking_ids' => array_map(fn($booking) => $booking->id, $createdBookings),
                'booking_uuids' => array_map(fn($booking) => $booking->booking_uuid, $createdBookings),
                'status' => $isReschedule ? $originalBooking->status : 'pending',
                'new_wallet_balance' => $studentWallet ? $studentWallet->balance : 0
            ]);
            
        } catch (\Exception $e) {
            DB::rollback();
            
            // If booking creation failed, refund the wallet if payment was deducted
            if (in_array('wallet', $request->payment_methods) && isset($requiredAmountNGN)) {
                $studentWallet->increment('balance', $requiredAmountNGN);
                
                // Create refund transaction record
                \App\Models\WalletTransaction::create([
                    'wallet_id' => $studentWallet->id,
                    'transaction_type' => 'credit',
                    'amount' => $requiredAmountNGN,
                    'status' => 'completed',
                    'description' => 'Refund for failed booking: ' . $e->getMessage()
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Booking failed: ' . $e->getMessage()
            ], 500);
        }
    }
}
