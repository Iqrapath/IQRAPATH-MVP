<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Booking;
use App\Models\TeacherAvailability;
use App\Models\TeachingSession;
use App\Services\BookingNotificationService;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BookingController extends Controller
{
    protected $bookingNotificationService;
    protected $financialService;

    public function __construct(
        BookingNotificationService $bookingNotificationService,
        FinancialService $financialService
    ) {
        $this->bookingNotificationService = $bookingNotificationService;
        $this->financialService = $financialService;
    }

    /**
     * Display a listing of student bookings
     */
    public function index(Request $request)
    {
        $student = auth()->user();
        
        $bookings = Booking::where('student_id', $student->id)
            ->with(['teacher', 'subject.template', 'teachingSession'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $bookingsData = $bookings->map(function ($booking) {
            $teacher = $booking->teacher;
            $subject = $booking->subject;
            $subjectTemplate = $subject?->template;

            return [
                'id' => $booking->id,
                'booking_uuid' => $booking->booking_uuid,
                'status' => $booking->status,
                'booking_date' => $booking->booking_date->format('Y-m-d'),
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'duration_minutes' => $booking->duration_minutes,
                'notes' => $booking->notes,
                'created_at' => $booking->created_at,
                'teacher' => [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'avatar' => $teacher->avatar,
                ],
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subjectTemplate?->name ?? $subject->name ?? 'Unknown Subject',
                ],
                'can_reschedule' => in_array($booking->status, ['pending', 'approved']) && 
                    $booking->booking_date > now()->addHours(12),
                'can_cancel' => in_array($booking->status, ['pending', 'approved']) && 
                    $booking->booking_date > now()->addHours(12),
            ];
        });

        return Inertia::render('student/my-bookings', [
            'bookings' => $bookingsData,
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    /**
     * Show booking details
     */
    public function show(Booking $booking)
    {
        $student = auth()->user();
        
        // Ensure the booking belongs to this student
        if ($booking->student_id !== $student->id) {
            abort(403, 'Unauthorized access to booking.');
        }

        $teacher = $booking->teacher;
        $teacherProfile = $teacher->teacherProfile;
        $subject = $booking->subject;
        $subjectTemplate = $subject?->template;

        $bookingData = [
            'id' => $booking->id,
            'booking_uuid' => $booking->booking_uuid,
            'status' => $booking->status,
            'booking_date' => $booking->booking_date->format('Y-m-d'),
            'start_time' => $booking->start_time,
            'end_time' => $booking->end_time,
            'duration_minutes' => $booking->duration_minutes,
            'notes' => $booking->notes,
            'created_at' => $booking->created_at,
            'can_reschedule' => in_array($booking->status, ['pending', 'approved']) && 
                $booking->booking_date > now()->addHours(12),
            'can_cancel' => in_array($booking->status, ['pending', 'approved']) && 
                $booking->booking_date > now()->addHours(12),
        ];

        $teacherData = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar,
            'rating' => $teacherProfile?->rating ?? 4.5,
            'reviews_count' => $teacher->teacherReviews?->count() ?? 0,
            'subjects' => $teacherProfile?->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'template' => [
                        'name' => $subject->template?->name ?? $subject->name ?? 'Unknown Subject'
                    ]
                ];
            })->toArray() ?? [],
            'location' => $teacherProfile?->location ?? 'Not specified',
            'availability' => $teacherProfile?->teaching_mode ?? 'Available on request',
            'verified' => $teacherProfile?->verified ?? false,
            'hourly_rate_ngn' => $teacherProfile?->hourly_rate_ngn ?? 0,
            'hourly_rate_usd' => $teacherProfile?->hourly_rate_usd ?? 0,
            'bio' => $teacherProfile?->bio ?? '',
            'experience_years' => $teacherProfile?->experience_years ?? '5+ years',
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
                ->with(['teacherProfile.subjects.template', 'teacherReviews'])
                ->find($teacherId);

            if (!$teacher) {
                return redirect()->route('student.find-teacher')
                    ->with('error', 'Teacher not found.');
            }

            $profile = $teacher->teacherProfile;
            $availabilities = TeacherAvailability::where('teacher_id', $teacher->id)
                ->where('is_active', true)
                ->orderBy('day_of_week')
                ->orderBy('start_time')
                ->get();

            $processedAvailabilities = $availabilities->map(function ($availability) {
                $startTime = \Carbon\Carbon::parse($availability->start_time);
                $endTime = \Carbon\Carbon::parse($availability->end_time);
                
                return [
                    'id' => $availability->id,
                    'day_of_week' => $availability->day_of_week,
                    'start_time' => $availability->start_time,
                    'end_time' => $availability->end_time,
                    'is_active' => $availability->is_active,
                    'time_zone' => $availability->time_zone,
                    'formatted_time' => $startTime->format('g:i A') . ' - ' . $endTime->format('g:i A'),
                    'availability_type' => $availability->availability_type,
                ];
            });

            $subjects = $profile?->subjects->map(function($subject) {
                return [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'template' => [
                        'name' => $subject->template?->name ?? $subject->name ?? 'Unknown Subject'
                    ]
                ];
            })->toArray() ?? [];

            $reviewsCount = $teacher->teacherReviews?->count() ?? 0;

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
                        'rating' => $recommendedProfile->rating ? (float) $recommendedProfile->rating : null,
                        'price' => $recommendedProfile->hourly_rate_ngn ? '₦' . number_format($recommendedProfile->hourly_rate_ngn) . ' / session' : '₦5,000 / session',
                        'avatarUrl' => null,
                    ];
                })->toArray();

            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => null,
                'rating' => $profile?->rating ?? 4.5,
                'reviews_count' => $reviewsCount,
                'subjects' => $subjects,
                'location' => $profile?->location ?? 'Not specified',
                'availability' => $profile?->teaching_mode ?? 'Available on request',
                'verified' => $profile?->verified ?? false,
                'hourly_rate_ngn' => $profile?->hourly_rate_ngn ?? 0,
                'hourly_rate_usd' => $profile?->hourly_rate_usd ?? 0,
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

        if (!$teacherId || empty($dates) || empty($subjects) || empty($availabilityIds)) {
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        $teacher = User::where('role', 'teacher')->with(['teacherProfile'])->find($teacherId);
        
        if (!$teacher) {
            return redirect()->route('student.find-teacher')
                ->with('error', 'Teacher not found.');
        }

        $profile = $teacher->teacherProfile;
        $formattedTeacher = [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'hourly_rate_usd' => $profile?->hourly_rate_usd ? (float)$profile->hourly_rate_usd : 25.0,
            'hourly_rate_ngn' => $profile?->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : 37500.0,
        ];

        return Inertia::render('student/session-details', [
            'teacher_id' => (int) $teacherId,
            'dates' => $dates,
            'availability_ids' => array_map('intval', $availabilityIds),
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
            return redirect()->route('student.find-teacher')
                ->with('error', 'Teacher not found.');
        }

        $profile = $teacher->teacherProfile;
        $formattedTeacher = [
            'id' => $teacher->id,
            'name' => $teacher->name,
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
            'dates' => 'required|array|min:1',
            'dates.*' => 'required|date|after:today',
            'availability_ids' => 'required|array|min:1',
            'availability_ids.*' => 'integer|exists:teacher_availabilities,id',
            'subjects' => 'required|array|min:1',
            'subjects.*' => 'string',
            'note_to_teacher' => 'nullable|string|max:1000',
            'payment_method' => 'required|string|in:wallet,card',
            'currency' => 'required|string|in:USD,NGN',
        ]);

        $student = auth()->user();
        $teacherId = $request->input('teacher_id');
        $dates = $request->input('dates');
        $availabilityIds = $request->input('availability_ids');
        $subjects = $request->input('subjects');
        $noteToTeacher = $request->input('note_to_teacher');
        $paymentMethod = $request->input('payment_method');
        $currency = $request->input('currency');

        DB::beginTransaction();
        
        try {
            $teacher = User::where('role', 'teacher')->find($teacherId);
            if (!$teacher) {
                throw new \Exception('Teacher not found.');
            }

            $profile = $teacher->teacherProfile;
            $hourlyRate = $currency === 'USD' 
                ? ($profile?->hourly_rate_usd ?? 25.0)
                : ($profile?->hourly_rate_ngn ?? 37500.0);

            $totalAmount = $hourlyRate * count($availabilityIds);

            // Process payment
            $paymentResult = $this->financialService->processPayment([
                'student_id' => $student->id,
                'teacher_id' => $teacherId,
                'amount' => $totalAmount,
                'currency' => $currency,
                'payment_method' => $paymentMethod,
                'description' => 'Class booking payment',
            ]);

            if (!$paymentResult['success']) {
                throw new \Exception($paymentResult['message']);
            }

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

                    $booking = Booking::create([
                        'student_id' => $student->id,
                        'teacher_id' => $teacherId,
                        'subject_id' => 1, // Default subject, can be enhanced
                        'booking_date' => $date,
                        'start_time' => $availability->start_time,
                        'end_time' => $availability->end_time,
                        'duration_minutes' => $durationMinutes,
                        'status' => 'pending',
                        'notes' => $noteToTeacher,
                        'created_by_id' => $student->id,
                    ]);

                    $createdBookings[] = $booking;
                }
            }

            // Create teaching sessions
            foreach ($createdBookings as $booking) {
                TeachingSession::create([
                    'booking_id' => $booking->id,
                    'teacher_id' => $teacherId,
                    'student_id' => $student->id,
                    'session_date' => $booking->booking_date,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'status' => 'scheduled',
                ]);
            }

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
