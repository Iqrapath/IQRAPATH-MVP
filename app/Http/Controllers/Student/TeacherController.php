<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\SubjectTemplates;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherController extends Controller
{
    /**
     * Display a listing of teachers for browsing.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $subject = $request->get('subject');
        $minRating = $request->get('min_rating');
        $maxPrice = $request->get('max_price');
        $experience = $request->get('experience');
        $language = $request->get('language');
        $verified = $request->get('verified');
        $availableNow = $request->get('available_now');
        $sort = $request->get('sort', 'rating');

        // Build base query for teachers
        $query = User::with(['teacherProfile.subjects.template'])
            ->where('role', 'teacher')
            ->whereHas('teacherProfile');

        // Apply search filter
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('teacherProfile', function ($subQ) use ($search) {
                      $subQ->where('bio', 'like', "%{$search}%");
                  })
                  ->orWhereHas('teacherProfile.subjects.template', function ($subQ) use ($search) {
                      $subQ->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Apply subject filter
        if ($subject && $subject !== 'all') {
            $query->whereHas('teacherProfile.subjects.template', function ($q) use ($subject) {
                $q->where('name', $subject);
            });
        }

        // Apply rating filter
        if ($minRating) {
            $query->whereHas('teacherProfile', function ($q) use ($minRating) {
                $q->where('rating', '>=', $minRating);
            });
        }

        // Apply price filter
        if ($maxPrice) {
            $query->whereHas('teacherProfile', function ($q) use ($maxPrice) {
                $q->where('hourly_rate_ngn', '<=', $maxPrice);
            });
        }

        // Apply verified filter
        if ($verified) {
            $query->whereHas('teacherProfile', function ($q) {
                $q->where('verified', true);
            });
        }

        // Apply sorting
        switch ($sort) {
            case 'rating':
                $query->leftJoin('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
                      ->orderByDesc('teacher_profiles.rating');
                break;
            case 'experience':
                $query->leftJoin('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
                      ->orderByDesc('teacher_profiles.experience_years');
                break;
            case 'price_low':
                $query->leftJoin('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
                      ->orderBy('teacher_profiles.hourly_rate_ngn');
                break;
            case 'price_high':
                $query->leftJoin('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
                      ->orderByDesc('teacher_profiles.hourly_rate_ngn');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $teachers = $query->select('users.*')->paginate(12);

        // Format teachers for frontend
        $formattedTeachers = $teachers->getCollection()->map(function ($teacher) {
            return $this->formatTeacherForListing($teacher);
        });

        $teachers->setCollection($formattedTeachers);

        // Get available subjects for filter
        $subjects = SubjectTemplates::orderBy('name')->pluck('name')->toArray();

        return Inertia::render('student/browse-teachers', [
            'teachers' => $teachers,
            'subjects' => $subjects,
            'filters' => [
                'search' => $search,
                'subject' => $subject,
                'min_rating' => $minRating,
                'max_price' => $maxPrice,
                'experience' => $experience,
                'language' => $language,
                'verified' => $verified,
                'available_now' => $availableNow,
                'sort' => $sort,
            ],
        ]);
    }

    /**
     * Display the specified teacher profile.
     */
    public function show(Request $request, User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found.');
        }

        // Load teacher with all necessary relationships
        $teacher->load([
            'teacherProfile.subjects.template',
            'teacherWallet',
            'teacherAvailabilities',
            'teacherReviews' => function ($query) {
                $query->with(['student'])
                      ->orderBy('created_at', 'desc')
                      ->take(10);
            },
            'teachingSessions' => function ($query) {
                $query->where('status', 'completed')
                      ->with(['student', 'subject.template'])
                      ->orderBy('completion_date', 'desc')
                      ->take(5);
            }
        ]);

        // Format teacher data for detailed view
        $teacherData = $this->formatTeacherForProfile($teacher);

        return response()->json([
            'teacher' => $teacherData,
        ]);
    }

    /**
     * Return teacher profile data as JSON for modal consumption.
     */
    public function profileData(Request $request, User $teacher)
    {
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found.');
        }

        $teacher->load([
            'teacherProfile.subjects.template',
            'teacherWallet',
            'teacherAvailabilities',
            'teacherReviews' => function ($query) {
                $query->with(['student'])
                      ->orderBy('created_at', 'desc')
                      ->take(10);
            },
            'teachingSessions' => function ($query) {
                $query->where('status', 'completed')
                      ->with(['student', 'subject.template'])
                      ->orderBy('completion_date', 'desc')
                      ->take(5);
            }
        ]);

        return response()->json([
            'teacher' => $this->formatTeacherForProfile($teacher)
        ]);
    }

    /**
     * Format teacher data for listing.
     */
    private function formatTeacherForListing(User $teacher): array
    {
        $profile = $teacher->teacherProfile;
        $subjects = $profile->subjects->take(3)->map(fn($s) => $s->template->name ?? $s->name)->filter()->toArray();

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar,
            'bio' => $profile->bio,
            'subjects' => $subjects,
            'rating' => $profile->rating ? (float) $profile->rating : 4.5,
            'reviews_count' => $profile->reviews_count,
            'hourly_rate_ngn' => $profile->hourly_rate_ngn,
            'hourly_rate_usd' => $profile->hourly_rate_usd,
            'experience_years' => $profile->experience_years,
            'location' => $teacher->location ?? 'Nigeria',
            'verified' => $profile->verified,
            'teaching_mode' => $profile->teaching_mode,
            'teaching_type' => $profile->teaching_type,
            'languages' => $profile->languages ?? ['English', 'Arabic'],
            'education' => $profile->education,
            'qualification' => $profile->qualification,
            'available_slots' => $profile->available_slots,
            'response_time' => 'Usually responds in 2 hours',
            'availability' => $profile->availability,
        ];
    }

    /**
     * Format teacher data for detailed profile view.
     */
    private function formatTeacherForProfile(User $teacher): array
    {
        $profile = $teacher->teacherProfile;
        
        // Get teacher's subjects with details
        $defaultRate = $profile->hourly_rate_ngn;
        $subjects = $profile->subjects->map(function ($subject) use ($defaultRate) {
            return [
                'id' => $subject->id,
                'name' => $subject->template?->name,
                'level' => $subject->level,
                'price' => $subject->price_per_hour ?? $defaultRate,
            ];
        })->toArray();

        // Get recent sessions/reviews
        $recentSessions = $teacher->teachingSessions->map(function ($session) {
            return [
                'id' => $session->id,
                'student_name' => $session->student->name,
                'subject' => $session->subject?->template?->name,
                'completion_date' => $session->completion_date?->format('M j, Y'),
                'rating' => $session->student_rating,
                'feedback' => $session->student_feedback,
            ];
        })->toArray();

        // Get availability data
        $availabilities = $teacher->teacherAvailabilities ?? collect([]);
        $availabilityType = $availabilities->first()?->availability_type;
        
        // Format time slots with colors
        $timeSlots = $availabilities->map(function ($availability, $index) {
            $colors = ['#22C55E', '#3B82F6', '#F97316', '#F59E0B', '#8B5CF6', '#EC4899'];
            
            // Format times for display
            $startTime = date('g:i A', strtotime($availability->start_time));
            $endTime = date('g:i A', strtotime($availability->end_time));
            
            return [
                'start_time' => $availability->start_time,
                'end_time' => $availability->end_time,
                'day_of_week' => (int) $availability->day_of_week,
                'label' => "{$startTime} - {$endTime}",
                'color' => $colors[$index % count($colors)],
                'is_active' => (bool) $availability->is_active,
                'time_zone' => $availability->time_zone,
            ];
        })->toArray();

        // Get wallet data for payment methods
        $wallet = $teacher->teacherWallet;
        $paymentMethods = $wallet?->payment_methods;
        $defaultPaymentMethod = $wallet?->default_payment_method;

        // Format review data
        $reviews = $teacher->teacherReviews->map(function ($review) {
            return [
                'id' => $review->id,
                'student_name' => $review->student->name,
                'rating' => $review->rating,
                'review' => $review->review,
                'created_at' => $review->created_at->format('M j, Y'),
                'formatted_date' => $review->formatted_date,
                'student_display_name' => $review->student_display_name,
            ];
        })->toArray();

        return [
            'id' => $teacher->id,
            'name' => $teacher->name,
            'avatar' => $teacher->avatar,
            'bio' => $profile->bio,
            'subjects' => $subjects,
            'rating' => $profile->rating ? (float) $profile->rating : 4.5,
            'reviews_count' => $profile->reviews_count,
            'hourly_rate_ngn' => $profile->hourly_rate_ngn,
            'hourly_rate_usd' => $profile->hourly_rate_usd,
            'experience_years' => $profile->experience_years,
            'location' => $teacher->location,
            'verified' => $profile->verified,
            'teaching_mode' => $profile->teaching_mode,
            'teaching_type' => $profile->teaching_type,
            'languages' => $profile->languages,
            'education' => $profile->education,
            'qualification' => $profile->qualification,
            'certifications' => ['Ijazah in Quran', 'Arabic Language Certificate'], // Mock data for now
            'availability' => $profile->availability,
            'response_time' => 'Usually responds in 2 hours',
            'total_students' => $profile->total_students,
            'total_hours_taught' => $profile->total_hours_taught,
            'recent_sessions' => $recentSessions,
            'member_since' => $teacher->created_at->format('F Y'),
            'availability_data' => [
                'availability_type' => $availabilityType,
                'time_slots' => $timeSlots,
            ],
            'wallet_data' => [
                'payment_methods' => $paymentMethods,
                'default_payment_method' => $defaultPaymentMethod,
                'balance' => $wallet?->balance,
                'total_earned' => $wallet?->total_earned,
            ],
            'reviews_data' => [
                'overall_rating' => $profile->rating,
                'total_reviews' => $profile->reviews_count,
                'recent_reviews' => $reviews,
            ],
        ];
    }
}
