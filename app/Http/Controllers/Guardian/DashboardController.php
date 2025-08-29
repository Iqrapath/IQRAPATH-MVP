<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\StudentProfile;
use App\Models\StudentLearningProgress;
use App\Models\SubjectTemplates;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the guardian dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $guardianProfile = $user->guardianProfile;
        
        // Get children profiles (student profiles managed by this guardian)
        $children = StudentProfile::where('guardian_id', $user->id)
                                 ->whereHas('user', function($query) use ($user) {
                                     $query->where('email', 'like', '%child.of.' . str_replace('@', '.', $user->email));
                                 })
                                 ->with(['user', 'user.studentLearningSchedules'])
                                 ->get()
                                 ->map(function ($child) {
                                     // Extract preferred learning times from schedules
                                     $preferredTimes = [
                                         'monday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'tuesday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'wednesday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'thursday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'friday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'saturday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                         'sunday' => ['enabled' => false, 'from' => '', 'to' => ''],
                                     ];
                                     
                                     if ($child->user && $child->user->studentLearningSchedules) {
                                         $schedules = $child->user->studentLearningSchedules->where('is_active', true);
                                         
                                         // Map day numbers to day names
                                         $dayNames = [
                                             0 => 'sunday',
                                             1 => 'monday', 
                                             2 => 'tuesday',
                                             3 => 'wednesday',
                                             4 => 'thursday',
                                             5 => 'friday',
                                             6 => 'saturday',
                                         ];
                                         
                                         foreach ($schedules as $schedule) {
                                             $dayName = $dayNames[$schedule->day_of_week] ?? null;
                                             if ($dayName) {
                                                 $preferredTimes[$dayName] = [
                                                     'enabled' => true,
                                                     'from' => substr($schedule->start_time, 0, 5), // Remove seconds
                                                     'to' => substr($schedule->end_time, 0, 5),     // Remove seconds
                                                 ];
                                             }
                                         }
                                     }
                                     
                                     return [
                                         'id' => $child->id,
                                         'name' => $child->user->name,
                                         'age' => $child->age_group,
                                         'gender' => $child->gender,
                                         'preferred_subjects' => $child->subjects_of_interest ?? [],
                                         'preferred_learning_times' => $preferredTimes,
                                     ];
                                 });
        
        // Calculate guardian dashboard stats
        $guardianStudentIds = $children->pluck('id')->toArray();
        $stats = $this->calculateGuardianStats($guardianStudentIds);
        
        // Get guardian overview data
        $overviewData = $this->getGuardianOverviewData($user);
        
        // Get upcoming classes
        $upcomingClasses = $this->getUpcomingClasses($guardianStudentIds);
        
        // Get learning progress data
        $learningProgressData = $this->getLearningProgressData($guardianStudentIds);
        
        // Get top rated teachers recommended for this guardian
        $topRatedTeachers = $this->getTopRatedTeachers($guardianStudentIds);
        
        return Inertia::render('guardian/dashboard', [
            'guardianProfile' => $guardianProfile,
            'children' => $children,
            'students' => $guardianProfile?->students()->with('user')->get(), // Keep existing students
            'stats' => $stats,
            'overviewData' => $overviewData,
            'upcomingClasses' => $upcomingClasses,
            'learningProgressData' => $learningProgressData,
            'topRatedTeachers' => $topRatedTeachers,
            'availableSubjects' => SubjectTemplates::where('is_active', true)
                                                  ->orderBy('name')
                                                  ->pluck('name')
                                                  ->toArray(),
            'showOnboarding' => $request->session()->get('showOnboarding', false),
        ]);
    }
    
    /**
     * View children details page
     */
    public function childrenIndex(Request $request): Response
    {
        $user = $request->user();
        
        // Fetch guardian basic info
        $guardian = [
            'name' => $user->name,
            'email' => $user->email,
        ];
        
        // Fetch children with essential fields
        $children = StudentProfile::where('guardian_id', $user->id)
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($child) {
                $subjects = $child->subjects_of_interest ?? [];
                return [
                    'id' => $child->id,
                    'name' => $child->user?->name,
                    'age' => $child->age_group,
                    'status' => $child->status ?? 'active',
                    'subjects' => $subjects,
                ];
            })->values();
        
        // Get student IDs for top rated teachers recommendation
        $studentIds = StudentProfile::where('guardian_id', $user->id)->pluck('id')->toArray();
        $topRatedTeachers = $this->getTopRatedTeachers($studentIds);
        
        return Inertia::render('guardian/children/index', [
            'guardian' => $guardian,
            'children' => $children,
            'topRatedTeachers' => $topRatedTeachers,
        ]);
    }

    /**
     * Show the form for creating a new child
     */
    public function createChild(Request $request): Response
    {
        $availableSubjects = SubjectTemplates::where('is_active', true)
            ->pluck('name')
            ->toArray();

        return Inertia::render('guardian/children/create', [
            'availableSubjects' => $availableSubjects,
        ]);
    }

    /**
     * Store a newly created child
     */
    public function storeChild(Request $request)
    {
        $request->validate([
            'children' => 'required|array|min:1',
            'children.*.name' => 'required|string|max:255',
            'children.*.age' => 'required|string|max:50',
            'children.*.gender' => 'required|in:male,female',
            'children.*.preferred_subjects' => 'array',
            'children.*.preferred_learning_times' => 'array',
        ]);

        $user = $request->user();

        foreach ($request->children as $childData) {
            // Create a new user account for the child
            $childUser = User::create([
                'name' => $childData['name'],
                'email' => $this->generateChildEmail($childData['name'], $user->id),
                'password' => bcrypt(Str::random(12)), // Generate random password
                'role' => 'student',
            ]);

            // Create student profile
            StudentProfile::create([
                'user_id' => $childUser->id,
                'guardian_id' => $user->id,
                'age_group' => $childData['age'],
                'gender' => $childData['gender'],
                'subjects_of_interest' => $childData['preferred_subjects'] ?? [],
                'preferred_learning_times' => $childData['preferred_learning_times'] ?? [],
                'status' => 'active',
            ]);
        }

        $childCount = count($request->children);
        $message = $childCount === 1 ? 'Child registered successfully!' : "{$childCount} children registered successfully!";

        return redirect()->route('guardian.children.index')
            ->with('success', $message);
    }

    /**
     * Show child progress page
     */
    public function childProgress(Request $request, $childId): Response
    {
        $user = $request->user();
        
        // Get the child's profile
        $child = StudentProfile::where('id', $childId)
            ->where('guardian_id', $user->id)
            ->with(['user', 'learningProgress'])
            ->firstOrFail();

        // Get weekly progress data (this would come from actual session data)
        $weeklyProgress = $this->getWeeklyProgress($child->user_id);
        
        // Calculate attendance statistics
        $attendanceStats = $this->calculateAttendanceStats($child->user_id);

        $progressData = [
            'childName' => $child->user->name,
            'weeklyProgress' => $weeklyProgress,
            'weeklyAttendanceData' => $this->getWeeklyAttendanceData($child->user_id),
            'totalSessions' => $attendanceStats['total'],
            'attendedSessions' => $attendanceStats['attended'],
            'missedSessions' => $attendanceStats['missed'],
            'attendanceRate' => $attendanceStats['rate'],
            'upcomingGoal' => $this->getUpcomingGoal($child->user_id),
            'learningProgress' => $this->getLearningProgress($child->user_id),
        ];

        return Inertia::render('guardian/children/progress', [
            'childId' => $childId,
            'progressData' => $progressData,
        ]);
    }

    /**
     * Get weekly progress for a child
     */
    private function getWeeklyProgress(int $userId): array
    {
        // This would typically query actual session/attendance data
        // For now, returning sample data
        return [
            'monday' => 'attended',
            'tuesday' => 'attended',
            'wednesday' => 'missed',
            'thursday' => 'attended',
            'friday' => 'attended',
            'saturday' => 'no-session',
            'sunday' => 'no-session',
        ];
    }

    /**
     * Calculate attendance statistics
     */
    private function calculateAttendanceStats(int $userId): array
    {
        // This would typically query actual session data
        // For now, returning sample data
        return [
            'total' => 5,
            'attended' => 4,
            'missed' => 1,
            'rate' => 80,
        ];
    }

    /**
     * Get weekly attendance data for bar chart
     */
    private function getWeeklyAttendanceData(int $userId): array
    {
        // This would typically query actual session data
        // For now, returning sample data matching the image exactly
        return [
            'monday' => 100,    // 100% attendance - tall green bar
            'tuesday' => 100,   // 100% attendance - tall green bar
            'wednesday' => 55,  // 55% attendance - shorter red bar
            'thursday' => 100,  // 100% attendance - tall green bar
            'friday' => 100,    // 100% attendance - tall green bar
            'saturday' => 5,    // Very low - barely visible gray bar
            'sunday' => 5,      // Very low - barely visible gray bar
        ];
    }

    /**
     * Get upcoming goal for the child
     */
    private function getUpcomingGoal(int $userId): string
    {
        // This would typically query actual goal data from the database
        // For now, returning sample data matching the image
        return "Complete Surah At-Tariq by next Friday.";
    }

    /**
     * Get learning progress data for the child
     */
    private function getLearningProgress(int $userId): array
    {
        // This would typically query actual learning progress data from the database
        // For now, returning sample data matching the image
        return [
            'currentJuz' => "Juz' Amma",
            'progressPercentage' => 77,
            'subjects' => [
                [
                    'name' => 'Tajweed',
                    'status' => 'Intermediate',
                    'color' => 'yellow'
                ],
                [
                    'name' => 'Quran Recitation',
                    'status' => 'Good',
                    'color' => 'green'
                ],
                [
                    'name' => 'Memorization',
                    'status' => 'In Progress (8 Surahs completed)',
                    'color' => 'none'
                ]
            ]
        ];
    }

    /**
     * Generate a unique email for the child
     */
    private function generateChildEmail(string $childName, int $guardianId): string
    {
        $baseEmail = strtolower(str_replace(' ', '.', $childName)) . '.child' . $guardianId . '@iqrapath.com';
        
        // Check if email exists and append number if needed
        $counter = 1;
        $email = $baseEmail;
        
        while (User::where('email', $email)->exists()) {
            $email = str_replace('@iqrapath.com', $counter . '@iqrapath.com', $baseEmail);
            $counter++;
        }
        
        return $email;
    }
    /**
     * Calculate guardian dashboard statistics
     */
    private function calculateGuardianStats(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [
                'total_classes' => 0,
                'completed_classes' => 0,
                'upcoming_classes' => 0,
            ];
        }
        
        // Get user IDs for the student profiles
        $userIds = StudentProfile::whereIn('id', $studentIds)->pluck('user_id')->toArray();
        
        // Total classes (all bookings for guardian's children)
        $totalClasses = Booking::whereIn('student_id', $userIds)->count();
        
        // Completed classes
        $completedClasses = Booking::whereIn('student_id', $userIds)
            ->where('status', 'completed')
            ->count();
        
        // Upcoming classes (approved and scheduled for future dates)
        $upcomingClasses = Booking::whereIn('student_id', $userIds)
            ->whereIn('status', ['approved', 'upcoming'])
            ->where('booking_date', '>=', now()->format('Y-m-d'))
            ->count();
        
        return [
            'total_classes' => $totalClasses,
            'completed_classes' => $completedClasses,
            'upcoming_classes' => $upcomingClasses,
        ];
    }
    
    /**
     * Get guardian overview data
     */
    private function getGuardianOverviewData($user): array
    {
        // Get active subscription
        $activeSubscription = $user->activeSubscription();
        $activePlan = $activeSubscription && $activeSubscription->plan 
            ? $activeSubscription->plan->name 
            : 'No Active Plan';
        
        // Count registered children
        $registeredChildren = StudentProfile::where('guardian_id', $user->id)->count();
        
        return [
            'guardian_name' => $user->name,
            'email' => $user->email,
            'registered_children' => $registeredChildren,
            'active_plan' => $activePlan,
        ];
    }
    
    /**
     * Get upcoming classes for guardian's children
     */
    private function getUpcomingClasses(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }
        
        // Get user IDs for the student profiles
        $userIds = StudentProfile::whereIn('id', $studentIds)->pluck('user_id')->toArray();
        
        // Get upcoming bookings with relationships
        $bookings = Booking::whereIn('student_id', $userIds)
            ->whereIn('status', ['approved', 'upcoming'])
            ->where('booking_date', '>=', now()->format('Y-m-d'))
            ->with([
                'teacher.teacherProfile',
                'subject.template',
                'student'
            ])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5) // Limit to 5 most recent
            ->get();
        
        return $bookings->map(function ($booking) {
            // Map booking status to component status
            $status = $booking->status === 'approved' ? 'Confirmed' : 'Pending';
            
            // Format date and time
            $date = $booking->booking_date->format('l, F j, Y');
            $startTime = $booking->start_time->format('g:i A');
            $endTime = $booking->end_time->format('g:i A');
            $time = "{$startTime} - {$endTime}";
            
            // Get subject name and teacher name
            $subjectName = $booking->subject->template->name ?? 'Unknown Subject';
            $teacherName = $booking->teacher->name ?? 'Unknown Teacher';
            
            // Generate title (e.g., "Tajweed (Intermediate)")
            $title = $subjectName;
            
            // Get teacher avatar or use default
            $teacherAvatar = $booking->teacher->avatar ?? '/assets/images/teacher/default-teacher.png';
            
            return [
                'id' => $booking->id,
                'title' => $title,
                'teacher' => $teacherName,
                'date' => $date,
                'time' => $time,
                'status' => $status,
                'imageUrl' => $teacherAvatar,
            ];
        })->toArray();
    }
    
    /**
     * Get learning progress data for guardian's children
     */
    private function getLearningProgressData(array $studentIds): array
    {
        if (empty($studentIds)) {
            return [];
        }
        
        // Get student profiles with user information
        $students = StudentProfile::whereIn('id', $studentIds)
            ->with(['user'])
            ->get();
        
        $childrenProgress = [];
        
        foreach ($students as $student) {
            // Get learning progress for this specific child
            $progressRecords = StudentLearningProgress::where('user_id', $student->user_id)
                ->with(['subject.template'])
                ->get();
            
            if ($progressRecords->isEmpty()) {
                // Get available subjects from database for this child
                $availableSubjects = SubjectTemplates::where('is_active', true)
                    ->orderBy('name')
                    ->pluck('name')
                    ->take(3)
                    ->map(function ($subjectName) {
                        return [
                            'label' => $subjectName,
                            'status' => 'Not Started',
                            'dot_color' => 'yellow'
                        ];
                    })
                    ->toArray();
                
                $childrenProgress[] = [
                    'child_name' => $student->user->name,
                    'overall_percent' => 0,
                    'subjects' => $availableSubjects,
                ];
                continue;
            }
            
            // Calculate overall progress for this child
            $overallPercent = $progressRecords->avg('progress_percentage') ?? 0;
            
            // Get subject progress for this child
            $subjectProgress = $progressRecords->groupBy('subject.template.name')
                ->map(function ($records, $subjectName) {
                    $progress = $records->first();
                    $progressPercent = $progress->progress_percentage;
                    $completedSessions = $progress->completed_sessions;
                    
                    // Determine status and color based on progress
                    if ($progressPercent >= 80) {
                        $status = 'Advanced';
                        $dotColor = 'green';
                    } elseif ($progressPercent >= 50) {
                        $status = 'Intermediate';
                        $dotColor = 'yellow';
                    } elseif ($progressPercent > 0) {
                        $status = 'In Progress (' . $completedSessions . ' sessions completed)';
                        $dotColor = 'yellow';
                    } else {
                        $status = 'Not Started';
                        $dotColor = 'yellow';
                    }
                    
                    return [
                        'label' => $subjectName,
                        'status' => $status,
                        'dot_color' => $dotColor,
                    ];
                })
                ->values()
                ->toArray();
            
            $childrenProgress[] = [
                'child_name' => $student->user->name,
                'overall_percent' => round($overallPercent),
                'subjects' => $subjectProgress,
            ];
        }
        
        return $childrenProgress;
    }
    
    /**
     * Get top rated teachers recommended for this guardian
     */
    private function getTopRatedTeachers(array $studentIds): array
    {
        // Get user IDs for the student profiles
        $userIds = [];
        if (!empty($studentIds)) {
            $userIds = StudentProfile::whereIn('id', $studentIds)->pluck('user_id')->toArray();
        }
        
        // Get children's subjects of interest
        $childrenSubjectInterests = [];
        if (!empty($userIds)) {
            $childrenSubjectInterests = StudentProfile::whereIn('user_id', $userIds)
                ->whereNotNull('subjects_of_interest')
                ->pluck('subjects_of_interest')
                ->flatten()
                ->unique()
                ->toArray();
        }
        
        // Build the query for recommended teachers
        $query = TeacherProfile::query()
            ->where('verified', true) // Only verified teachers
            ->where('rating', '>=', 4.0) // High rated (4.0+)
            ->where('reviews_count', '>=', 3) // Minimum 3 reviews for reliability
            ->whereNotNull('rating') // Must have ratings
            ->with(['user', 'subjects.template']);
        
        // If we have subject interests, prioritize teachers who teach those subjects
        if (!empty($childrenSubjectInterests)) {
            $query->whereHas('subjects.template', function ($q) use ($childrenSubjectInterests) {
                $q->whereIn('name', $childrenSubjectInterests);
            });
        }
        
        // Get teachers ordered by rating and review count
        $teachers = $query->orderByDesc('rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('experience_years')
            ->limit(10) // Get up to 10 subject-matched teachers
            ->get();
        
        // If we don't have enough teachers with subject match, get general high-rated teachers
        if ($teachers->count() < 6) {
            $additionalTeachers = TeacherProfile::query()
                ->where('verified', true)
                ->where('rating', '>=', 4.0)
                ->where('reviews_count', '>=', 3)
                ->whereNotNull('rating')
                ->whereNotIn('id', $teachers->pluck('id')->toArray())
                ->with(['user', 'subjects.template'])
                ->orderByDesc('rating')
                ->orderByDesc('reviews_count')
                ->limit(10 - $teachers->count()) // Fill up to 10 total
                ->get();
            
            $teachers = $teachers->merge($additionalTeachers);
        }
        
        // Filter out teachers with incomplete data and return all recommendations
        return $teachers->filter(function ($teacher) {
            return $teacher->user && $teacher->user->name && $teacher->rating;
        })->map(function ($teacher) {
            // Get teacher's subjects from database
            $subjects = $teacher->subjects->map(function ($subject) {
                return $subject->template->name ?? null;
            })->filter()->unique()->take(3)->implode(', ');
            
            // Only include teachers with complete data
            if (!$teacher->user->name || !$teacher->rating || !$subjects) {
                return null;
            }
            
            // Get teacher's location from teaching_mode or default to online
            $location = $teacher->teaching_mode === 'In-person' ? 'In-person' : 'Online';
            
            // Format price - only use database values
            $price = null;
            if ($teacher->hourly_rate_ngn) {
                $price = '₦' . number_format($teacher->hourly_rate_ngn, 0) . ' / hour';
            } elseif ($teacher->hourly_rate_usd) {
                $price = '$' . number_format($teacher->hourly_rate_usd, 0) . ' / hour';
            }
            
            // Only include teachers with pricing information
            if (!$price) {
                return null;
            }
            
            return [
                'id' => $teacher->user->id,
                'name' => $teacher->user->name,
                'subjects' => $subjects,
                'location' => $location,
                'rating' => round($teacher->rating, 1),
                'price' => $price,
                'avatarUrl' => $teacher->user->avatar,
            ];
        })->filter()->values()->toArray(); // Remove null entries and reindex
    }
}
