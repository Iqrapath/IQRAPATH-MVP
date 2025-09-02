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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    /**
     * Show the book class page
     */
    public function create(Request $request)
    {
        $teacherId = $request->query('teacherId');
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
                        'avatarUrl' => $recommendedTeacher->avatar ?? "/assets/avatars/teacher-{$recommendedTeacher->id}.png",
                    ];
                })->toArray();

            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'avatar' => $teacher->avatar,
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
        return $this->renderSessionDetails($request);
    }

    /**
     * Show the session details page (GET for direct navigation)
     */
    public function sessionDetailsGet(Request $request)
    {
        // For GET requests, check if we have the required parameters
        if (!$request->has(['teacher_id', 'date', 'availability_ids'])) {
            // Redirect back to book class if missing required data
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        return $this->renderSessionDetails($request);
    }

    /**
     * Common method to render session details page
     */
    private function renderSessionDetails(Request $request)
    {
        $teacherId = $request->input('teacher_id');
        $date = $request->input('date');
        $availabilityIds = $request->input('availability_ids', []);

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
                        'avatarUrl' => $recommendedTeacher->avatar ?? "/assets/avatars/teacher-{$recommendedTeacher->id}.png",
                    ];
                })->toArray();

            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'subjects' => $subjects,
                'recommended_teachers' => $recommendedTeachers,
            ];
        }

        return Inertia::render('student/session-details', [
            'teacher_id' => (int) $teacherId,
            'date' => $date,
            'availability_ids' => array_map('intval', $availabilityIds),
            'teacher' => $formattedTeacher,
        ]);
    }

    /**
     * Show the pricing and payment page (POST from session details)
     */
    public function pricingPayment(Request $request)
    {
        return $this->renderPricingPayment($request);
    }

    /**
     * Show the pricing and payment page (GET for direct navigation)
     */
    public function pricingPaymentGet(Request $request)
    {
        // For GET requests, check if we have the required parameters
        if (!$request->has(['teacher_id', 'date', 'availability_ids', 'subjects'])) {
            return redirect()->route('student.book-class')
                ->with('error', 'Please start the booking process from the beginning.');
        }

        return $this->renderPricingPayment($request);
    }

    /**
     * Common method to render pricing and payment page
     */
    private function renderPricingPayment(Request $request)
    {
        $teacherId = $request->input('teacher_id');
        $date = $request->input('date');
        $availabilityIds = $request->input('availability_ids', []);
        $subjects = $request->input('subjects', []);
        $noteToTeacher = $request->input('note_to_teacher', '');

        // Get teacher info for pricing
        $teacher = null;
        if ($teacherId) {
            $teacher = User::where('role', 'teacher')
                ->with(['teacherProfile'])
                ->find($teacherId);
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

        $formattedTeacher = null;
        if ($teacher && $teacher->teacherProfile) {
            $profile = $teacher->teacherProfile;
            
            $formattedTeacher = [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'hourly_rate_usd' => $profile->hourly_rate_usd ? (float)$profile->hourly_rate_usd : null,
                'hourly_rate_ngn' => $profile->hourly_rate_ngn ? (float)$profile->hourly_rate_ngn : null,
            ];
        }

        return Inertia::render('student/pricing-payment', [
            'teacher_id' => (int) $teacherId,
            'date' => $date,
            'availability_ids' => array_map('intval', $availabilityIds),
            'subjects' => $subjects,
            'note_to_teacher' => $noteToTeacher,
            'teacher' => $formattedTeacher,
            'wallet_balance_usd' => $walletBalanceUSD,
            'wallet_balance_ngn' => $walletBalanceNGN,
        ]);
    }

    /**
     * Process booking payment
     */
    public function processPayment(Request $request)
    {
        $request->validate([
            'teacher_id' => 'required|integer|exists:users,id',
            'date' => 'required|date',
            'availability_ids' => 'required|array',
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
            
            // Use first availability for timing (can be enhanced to handle multiple slots)
            $firstAvailability = $availabilities->first();
            $lastAvailability = $availabilities->last();
            
            // Calculate session duration
            $startTime = \Carbon\Carbon::parse($firstAvailability->start_time);
            $endTime = \Carbon\Carbon::parse($lastAvailability->end_time);
            $durationMinutes = $startTime->diffInMinutes($endTime);
            
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
            
            // Create booking record
            $booking = Booking::create([
                'booking_uuid' => Str::uuid(),
                'student_id' => $student->id,
                'teacher_id' => $request->teacher_id,
                'subject_id' => $subject->id,
                'booking_date' => $request->date,
                'start_time' => $firstAvailability->start_time,
                'end_time' => $lastAvailability->end_time,
                'duration_minutes' => $durationMinutes,
                'status' => 'approved', // Auto-approve for wallet payments
                'notes' => $request->note_to_teacher,
                'created_by_id' => $student->id,
                'approved_by_id' => $student->id, // Auto-approval
                'approved_at' => now(),
            ]);
            
            // Create teaching session
            $session = TeachingSession::create([
                'session_uuid' => Str::uuid(),
                'booking_id' => $booking->id,
                'teacher_id' => $request->teacher_id,
                'student_id' => $student->id,
                'subject_id' => $subject->id,
                'session_date' => $request->date,
                'start_time' => $firstAvailability->start_time,
                'end_time' => $lastAvailability->end_time,
                'status' => 'scheduled',
                'meeting_platform' => 'zoom', // Default to zoom
            ]);
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Booking successful! Your session has been scheduled.',
                'booking_id' => $booking->id,
                'session_id' => $session->id,
                'session_uuid' => $session->session_uuid,
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
