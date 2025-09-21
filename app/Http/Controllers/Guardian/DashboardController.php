<?php

namespace App\Http\Controllers\Guardian;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\StudentProfile;
use App\Models\StudentLearningProgress;
use App\Models\SubjectTemplates;
use App\Models\TeacherProfile;
use App\Models\TeachingSession;
use App\Models\SessionProgress;
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
        
        // Ensure guardian wallet exists
        if (!$user->guardianWallet) {
            $user->guardianWallet()->create([
                'balance' => 0,
                'total_spent_on_children' => 0,
                'total_refunded' => 0,
                'auto_fund_children' => false,
                'auto_fund_threshold' => 0,
                'family_spending_limits' => [],
                'child_allowances' => []
            ]);
        }
        
        $user = $user->load('guardianWallet');
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
        
        // Get recent notifications for the guardian
        $notifications = \App\Models\Notification::where('notifiable_type', \App\Models\User::class)
            ->where('notifiable_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($notification) {
                $data = $notification->data ?? [];
                return [
                    'id' => $notification->id,
                    'sender' => $data['teacher_name'] ?? $data['sender'] ?? 'System Update',
                    'message' => $data['message'] ?? $data['body'] ?? 'No message',
                    'timestamp' => $notification->created_at->diffForHumans(),
                    'avatar' => $data['teacher_avatar'] ?? null,
                    'type' => $data['level'] ?? 'info',
                    'is_read' => $notification->read_at !== null,
                ];
            });

        return Inertia::render('guardian/dashboard', [
            'guardianProfile' => $guardianProfile,
            'children' => $children,
            'students' => $guardianProfile?->students()->with('user')->get(), // Keep existing students
            'stats' => $stats,
            'overviewData' => $overviewData,
            'upcomingClasses' => $upcomingClasses,
            'learningProgressData' => $learningProgressData,
            'topRatedTeachers' => $topRatedTeachers,
            'notifications' => $notifications,
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
                    'id' => $child->user->id, // Use User ID, not StudentProfile ID
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
     * Refresh progress data for a child (API endpoint)
     */
    public function refreshProgress(Request $request, $childId)
    {
        $user = $request->user();
        
        // Verify the child belongs to this guardian
        $child = StudentProfile::where('id', $childId)
            ->where('guardian_id', $user->id)
            ->with(['user'])
            ->firstOrFail();

        // Get updated progress data
        $weeklyProgress = $this->getWeeklyProgress($child->user_id);
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

        return response()->json([
            'success' => true,
            'data' => $progressData,
            'lastUpdated' => now()->toISOString()
        ]);
    }

    /**
     * Get weekly progress for a child
     */
    private function getWeeklyProgress(int $userId): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        // Get sessions for this week
        $sessions = TeachingSession::forStudent($userId)
            ->whereBetween('session_date', [$startOfWeek, $endOfWeek])
            ->get();
        
        $weeklyProgress = [
            'monday' => 'no-session',
            'tuesday' => 'no-session',
            'wednesday' => 'no-session',
            'thursday' => 'no-session',
            'friday' => 'no-session',
            'saturday' => 'no-session',
            'sunday' => 'no-session',
        ];
        
        foreach ($sessions as $session) {
            $dayName = strtolower($session->session_date->format('l'));
            
            if ($session->status === 'completed') {
                if ($session->teacher_marked_present && $session->student_marked_present) {
                    $weeklyProgress[$dayName] = 'attended';
                } else {
                    $weeklyProgress[$dayName] = 'missed';
                }
            } elseif ($session->status === 'scheduled') {
                $weeklyProgress[$dayName] = 'no-session';
            }
        }
        
        return $weeklyProgress;
    }

    /**
     * Calculate attendance statistics
     */
    private function calculateAttendanceStats(int $userId): array
    {
        $totalSessions = TeachingSession::forStudent($userId)
            ->where('status', 'completed')
            ->count();
            
        $attendedSessions = TeachingSession::forStudent($userId)
            ->where('status', 'completed')
            ->where('teacher_marked_present', true)
            ->where('student_marked_present', true)
            ->count();
            
        $missedSessions = $totalSessions - $attendedSessions;
        $attendanceRate = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;
        
        return [
            'total' => $totalSessions,
            'attended' => $attendedSessions,
            'missed' => $missedSessions,
            'rate' => $attendanceRate,
        ];
    }

    /**
     * Get weekly attendance data for bar chart
     */
    private function getWeeklyAttendanceData(int $userId): array
    {
        $startOfWeek = now()->startOfWeek();
        $endOfWeek = now()->endOfWeek();
        
        $weeklyData = [
            'monday' => 0,
            'tuesday' => 0,
            'wednesday' => 0,
            'thursday' => 0,
            'friday' => 0,
            'saturday' => 0,
            'sunday' => 0,
        ];
        
        // Get sessions for this week grouped by day
        $sessions = TeachingSession::forStudent($userId)
            ->whereBetween('session_date', [$startOfWeek, $endOfWeek])
            ->where('status', 'completed')
            ->get()
            ->groupBy(function ($session) {
                return strtolower($session->session_date->format('l'));
            });
        
        foreach ($weeklyData as $day => $value) {
            if (isset($sessions[$day])) {
                $daySessions = $sessions[$day];
                $totalSessions = $daySessions->count();
                $attendedSessions = $daySessions->where('teacher_marked_present', true)
                    ->where('student_marked_present', true)
                    ->count();
                
                $weeklyData[$day] = $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0;
            }
        }
        
        return $weeklyData;
    }

    /**
     * Get upcoming goal for the child from database
     */
    private function getUpcomingGoal(int $userId): string
    {
        // Get the most recent session progress to determine next goal
        $latestProgress = SessionProgress::whereHas('session', function ($query) use ($userId) {
            $query->where('student_id', $userId)
                  ->where('status', 'completed');
        })
        ->orderBy('created_at', 'desc')
        ->first();
        
        if ($latestProgress && $latestProgress->next_steps) {
            return $latestProgress->next_steps;
        }
        
        // If no next steps, check if there are any scheduled sessions
        $upcomingSession = TeachingSession::forStudent($userId)
            ->where('status', 'scheduled')
            ->where('session_date', '>=', now())
            ->with('subject')
            ->orderBy('session_date', 'asc')
            ->first();
            
        if ($upcomingSession && $upcomingSession->subject) {
            return "Next session: " . $upcomingSession->subject->name . " on " . $upcomingSession->session_date->format('M j');
        }
        
        // If no progress data and no upcoming sessions
        return "No upcoming goal set";
    }

    /**
     * Get learning progress data for the child
     */
    private function getLearningProgress(int $userId): array
    {
        // Get recent session progress data
        $recentProgress = SessionProgress::whereHas('session', function ($query) use ($userId) {
            $query->where('student_id', $userId)
                  ->where('status', 'completed');
        })
        ->orderBy('created_at', 'desc')
        ->limit(10)
        ->get();
        
        // Calculate average proficiency level
        $proficiencyLevels = $recentProgress->pluck('proficiency_level')->filter();
        $averageProficiency = $proficiencyLevels->isNotEmpty() 
            ? $proficiencyLevels->mode()[0] ?? 'beginner'
            : 'beginner';
        
        // Calculate progress percentage based on actual session progress
        $totalSessions = TeachingSession::forStudent($userId)
            ->where('status', 'completed')
            ->count();
            
        // Calculate progress based on actual proficiency levels from database
        if ($recentProgress->isNotEmpty()) {
            $proficiencyProgress = $recentProgress->map(function ($progress) {
                return match($progress->proficiency_level) {
                    'beginner' => 25,
                    'intermediate' => 50,
                    'advanced' => 75,
                    default => 0
                };
            })->avg();
            
            $progressPercentage = round($proficiencyProgress);
        } else {
            // If no progress records, calculate based on completed sessions
            $progressPercentage = min($totalSessions * 5, 100); // 5% per session, max 100%
        }
        
        // Get subjects from recent sessions with their actual progress
        $subjects = TeachingSession::forStudent($userId)
            ->where('status', 'completed')
            ->with(['subject', 'progress'])
            ->get()
            ->groupBy('subject.name')
            ->map(function ($sessions, $subjectName) {
                // Get the latest progress for this subject
                $latestProgress = $sessions->sortByDesc('created_at')->first()?->progress;
                $proficiencyLevel = $latestProgress?->proficiency_level ?? 'beginner';
                
                $color = match($proficiencyLevel) {
                    'beginner' => 'yellow',
                    'intermediate' => 'yellow', 
                    'advanced' => 'green',
                    default => 'none'
                };
                
                $status = match($proficiencyLevel) {
                    'beginner' => 'Beginner',
                    'intermediate' => 'Intermediate',
                    'advanced' => 'Advanced',
                    default => 'Starting'
                };
                
                // Add session count to status
                $sessionCount = $sessions->count();
                if ($sessionCount > 1) {
                    $status .= " ({$sessionCount} sessions)";
                }
                
                return [
                    'name' => $subjectName,
                    'status' => $status,
                    'color' => $color
                ];
            })
            ->values()
            ->toArray();
        
        // If no subjects, provide default based on student profile
        if (empty($subjects)) {
            $studentProfile = StudentProfile::where('user_id', $userId)->first();
            $subjectsOfInterest = $studentProfile?->subjects_of_interest ?? ['Quran Recitation'];
            
            $subjects = array_map(function ($subject) {
                return [
                    'name' => $subject,
                    'status' => 'Not Started',
                    'color' => 'none'
                ];
            }, $subjectsOfInterest);
        }
        
        // Get current Juz based on actual progress
        $currentJuz = $this->getCurrentJuz($userId);
        
        return [
            'currentJuz' => $currentJuz,
            'progressPercentage' => $progressPercentage,
            'subjects' => $subjects
        ];
    }

    /**
     * Get current Juz based on student's progress from database
     */
    private function getCurrentJuz(int $userId): string
    {
        // Get all session progress records for this student
        $progressRecords = SessionProgress::whereHas('session', function ($query) use ($userId) {
            $query->where('student_id', $userId)
                  ->where('status', 'completed');
        })
        ->orderBy('created_at', 'desc')
        ->get();
        
        if ($progressRecords->isEmpty()) {
            return "Not Started";
        }
        
        // Get the most recent progress
        $latestProgress = $progressRecords->first();
        
        if ($latestProgress && $latestProgress->topic_covered) {
            return $latestProgress->topic_covered;
        }
        
        // If no topic covered, get the most recent session's subject
        $latestSession = TeachingSession::forStudent($userId)
            ->where('status', 'completed')
            ->with('subject')
            ->orderBy('completion_date', 'desc')
            ->first();
            
        if ($latestSession && $latestSession->subject) {
            return "Learning: " . $latestSession->subject->name;
        }
        
        return "In Progress";
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

    /**
     * Show the form for editing a child.
     */
    public function editChild(Request $request, $child): Response
    {
        $user = $request->user();
        
        // Find the child (student) that belongs to this guardian
        $child = User::where('id', $child)
            ->where('role', 'student')
            ->whereHas('studentProfile', function ($query) use ($user) {
                $query->where('guardian_id', $user->id);
            })
            ->with('studentProfile')
            ->firstOrFail();

        // Get available subjects
        $availableSubjects = SubjectTemplates::where('is_active', true)
            ->orderBy('name')
            ->pluck('name')
            ->toArray();

        // Format child data for the form
        $childData = [
            'id' => $child->id,
            'name' => $child->name,
            'age' => (string) ($child->studentProfile->age ?? ''),
            'gender' => $child->studentProfile->gender ?? '',
            'preferred_subjects' => $child->studentProfile->subjects_of_interest ?? [],
            'preferred_learning_times' => [
                'monday' => [
                    'enabled' => $child->studentProfile->monday_enabled ?? false,
                    'from' => $child->studentProfile->monday_from ?? '',
                    'to' => $child->studentProfile->monday_to ?? '',
                ],
                'tuesday' => [
                    'enabled' => $child->studentProfile->tuesday_enabled ?? false,
                    'from' => $child->studentProfile->tuesday_from ?? '',
                    'to' => $child->studentProfile->tuesday_to ?? '',
                ],
                'wednesday' => [
                    'enabled' => $child->studentProfile->wednesday_enabled ?? false,
                    'from' => $child->studentProfile->wednesday_from ?? '',
                    'to' => $child->studentProfile->wednesday_to ?? '',
                ],
                'thursday' => [
                    'enabled' => $child->studentProfile->thursday_enabled ?? false,
                    'from' => $child->studentProfile->thursday_from ?? '',
                    'to' => $child->studentProfile->thursday_to ?? '',
                ],
                'friday' => [
                    'enabled' => $child->studentProfile->friday_enabled ?? false,
                    'from' => $child->studentProfile->friday_from ?? '',
                    'to' => $child->studentProfile->friday_to ?? '',
                ],
                'saturday' => [
                    'enabled' => $child->studentProfile->saturday_enabled ?? false,
                    'from' => $child->studentProfile->saturday_from ?? '',
                    'to' => $child->studentProfile->saturday_to ?? '',
                ],
                'sunday' => [
                    'enabled' => $child->studentProfile->sunday_enabled ?? false,
                    'from' => $child->studentProfile->sunday_from ?? '',
                    'to' => $child->studentProfile->sunday_to ?? '',
                ],
            ],
        ];

        return Inertia::render('guardian/children/edit', [
            'child' => $childData,
            'availableSubjects' => $availableSubjects,
        ]);
    }

    /**
     * Update a child's information.
     */
    public function updateChild(Request $request, $child): \Illuminate\Http\RedirectResponse
    {
        $user = $request->user();
        
        // Find the child (student) that belongs to this guardian
        $child = User::where('id', $child)
            ->where('role', 'student')
            ->whereHas('studentProfile', function ($query) use ($user) {
                $query->where('guardian_id', $user->id);
            })
            ->with('studentProfile')
            ->firstOrFail();

        $request->validate([
            'children' => 'required|array|min:1',
            'children.*.name' => 'required|string|max:255',
            'children.*.age' => 'required|max:50',
            'children.*.gender' => 'required|string|in:male,female',
            'children.*.preferred_subjects' => 'nullable',
            'children.*.preferred_learning_times' => 'array',
            'children.*.preferred_learning_times.monday.enabled' => 'boolean',
            'children.*.preferred_learning_times.monday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.monday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.tuesday.enabled' => 'boolean',
            'children.*.preferred_learning_times.tuesday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.tuesday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.wednesday.enabled' => 'boolean',
            'children.*.preferred_learning_times.wednesday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.wednesday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.thursday.enabled' => 'boolean',
            'children.*.preferred_learning_times.thursday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.thursday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.friday.enabled' => 'boolean',
            'children.*.preferred_learning_times.friday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.friday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.saturday.enabled' => 'boolean',
            'children.*.preferred_learning_times.saturday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.saturday.to' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.sunday.enabled' => 'boolean',
            'children.*.preferred_learning_times.sunday.from' => 'nullable|date_format:H:i',
            'children.*.preferred_learning_times.sunday.to' => 'nullable|date_format:H:i',
        ]);

        $childData = $request->children[0]; // We're editing one child at a time

        // Ensure data types are correct
        $age = is_numeric($childData['age']) ? (string) $childData['age'] : $childData['age'];
        
        // Handle preferred_subjects - it might be a JSON string or array
        $subjects = $childData['preferred_subjects'] ?? [];
        if (is_string($subjects)) {
            $subjects = json_decode($subjects, true) ?? [];
        }
        if (!is_array($subjects)) {
            $subjects = [];
        }

        // Update child's basic information
        $child->update([
            'name' => $childData['name'],
        ]);

        // Update student profile
        $child->studentProfile->update([
            'age' => $age,
            'gender' => $childData['gender'],
            'subjects_of_interest' => $subjects,
            'monday_enabled' => $childData['preferred_learning_times']['monday']['enabled'] ?? false,
            'monday_from' => $childData['preferred_learning_times']['monday']['from'] ?? null,
            'monday_to' => $childData['preferred_learning_times']['monday']['to'] ?? null,
            'tuesday_enabled' => $childData['preferred_learning_times']['tuesday']['enabled'] ?? false,
            'tuesday_from' => $childData['preferred_learning_times']['tuesday']['from'] ?? null,
            'tuesday_to' => $childData['preferred_learning_times']['tuesday']['to'] ?? null,
            'wednesday_enabled' => $childData['preferred_learning_times']['wednesday']['enabled'] ?? false,
            'wednesday_from' => $childData['preferred_learning_times']['wednesday']['from'] ?? null,
            'wednesday_to' => $childData['preferred_learning_times']['wednesday']['to'] ?? null,
            'thursday_enabled' => $childData['preferred_learning_times']['thursday']['enabled'] ?? false,
            'thursday_from' => $childData['preferred_learning_times']['thursday']['from'] ?? null,
            'thursday_to' => $childData['preferred_learning_times']['thursday']['to'] ?? null,
            'friday_enabled' => $childData['preferred_learning_times']['friday']['enabled'] ?? false,
            'friday_from' => $childData['preferred_learning_times']['friday']['from'] ?? null,
            'friday_to' => $childData['preferred_learning_times']['friday']['to'] ?? null,
            'saturday_enabled' => $childData['preferred_learning_times']['saturday']['enabled'] ?? false,
            'saturday_from' => $childData['preferred_learning_times']['saturday']['from'] ?? null,
            'saturday_to' => $childData['preferred_learning_times']['saturday']['to'] ?? null,
            'sunday_enabled' => $childData['preferred_learning_times']['sunday']['enabled'] ?? false,
            'sunday_from' => $childData['preferred_learning_times']['sunday']['from'] ?? null,
            'sunday_to' => $childData['preferred_learning_times']['sunday']['to'] ?? null,
        ]);

        return redirect()->route('guardian.children.index')
            ->with('success', 'Child information updated successfully.');
    }
}
