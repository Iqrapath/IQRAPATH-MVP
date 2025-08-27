<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\StudentProfile;
use App\Models\TeachingSession;
use App\Models\BookingHistory;
use App\Models\Subscription;
use App\Models\Subject;
use App\Models\SubjectTemplates;
use App\Services\StudentManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class StudentParentManagementController extends Controller
{
    protected $studentService;

    public function __construct(StudentManagementService $studentService)
    {
        $this->studentService = $studentService;
    }

    /**
     * Display a listing of students and parents/guardians.
     */
    public function index(Request $request)
    {
        $query = User::query()
            ->whereIn('role', ['student', 'guardian'])
            ->with(['studentProfile', 'studentProfile.guardian', 'guardianProfile', 'guardianProfile.students']);

        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->has('role') && $request->role && $request->role !== 'all') {
            $query->where('role', $request->role);
        }

        // Filter by status
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->whereHas('studentProfile', function ($subQ) use ($request) {
                    $subQ->where('status', $request->status);
                })->orWhereHas('guardianProfile', function ($subQ) use ($request) {
                    $subQ->where('status', $request->status);
                });
            });
        }

        // Filter by subject (only for students)
        if ($request->has('subject') && $request->subject && $request->subject !== 'all') {
            $query->whereHas('studentProfile', function ($q) use ($request) {
                $q->whereJsonContains('subjects_of_interest', $request->subject);
            });
        }

        // Filter by rating (based on average engagement)
        if ($request->has('rating') && $request->rating && $request->rating !== 'all') {
            // This would need to be implemented with a more complex query
            // For now, we'll skip this filter
        }

        // Get paginated users with additional data
        $users = $query->select('id', 'name', 'email', 'avatar', 'role', 'email_verified_at')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->through(function ($user) {
                if ($user->role === 'student') {
                    $studentProfile = $user->studentProfile;
                    $guardian = $studentProfile ? $studentProfile->guardian : null;
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'role' => 'Student',
                        'status' => $studentProfile ? $studentProfile->status : 'inactive',
                        'guardian_name' => $guardian ? $guardian->name : null,
                        'registration_date' => $studentProfile ? $studentProfile->formatted_registration_date : null,
                        'completed_sessions' => $studentProfile ? $studentProfile->completed_sessions_count : 0,
                        'attendance_percentage' => $studentProfile ? $studentProfile->attendance_percentage : 0,
                        'children_count' => null,
                    ];
                } else if ($user->role === 'guardian') {
                    $guardianProfile = $user->guardianProfile;
                    
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'avatar' => $user->avatar,
                        'role' => 'Parent',
                        'status' => $guardianProfile ? $guardianProfile->status : 'inactive',
                        'guardian_name' => null,
                        'registration_date' => $guardianProfile ? $guardianProfile->formatted_registration_date : null,
                        'completed_sessions' => null,
                        'attendance_percentage' => null,
                        'children_count' => $guardianProfile ? $guardianProfile->children_count : 0,
                    ];
                }
                
                // Return empty array for unknown roles instead of null
                return [];
            });

        // Get available subjects for filter dropdown (from templates to avoid duplicates)
        $subjects = SubjectTemplates::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/students/index', [
            'students' => $users, // Keep the same key name for frontend compatibility
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? 'all',
                'subject' => $request->subject ?? 'all',
                'rating' => $request->rating ?? 'all',
                'role' => $request->role ?? 'all',
            ],
            'subjects' => $subjects,
        ]);
    }

    /**
     * Display the specified student or parent.
     */
    public function show(User $user)
    {
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404, 'User not found');
        }

        // Load relationships based on user role
        if ($user->role === 'student') {
            $user->load([
                'studentProfile',
                'studentProfile.guardian',
                'studentProfile.subscription',
                'studentProfile.learningProgress',
            ]);
        } else {
            $user->load([
                'guardianProfile',
                'guardianProfile.students',
                'guardianProfile.students.user',
            ]);
        }

        $studentProfile = $user->studentProfile;
        $guardianProfile = $user->guardianProfile;
        
        // Get subscription information (for students) or children's subscriptions (for guardians)
        $subscriptionInfo = null;
        if ($studentProfile) {
            $activeSubscription = $studentProfile->activeSubscription();
            if ($activeSubscription) {
                $subscriptionInfo = [
                    'plan_name' => $activeSubscription->plan->name ?? 'Unknown Plan',
                    'start_date' => $activeSubscription->start_date->format('M j, Y'),
                    'end_date' => $activeSubscription->end_date->format('M j, Y'),
                    'amount_paid' => $activeSubscription->amount_paid,
                    'currency' => $activeSubscription->currency,
                    'status' => $activeSubscription->status,
                    'auto_renew' => $activeSubscription->auto_renew,
                ];
            }
        } elseif ($guardianProfile) {
            // For guardians, get subscription info for all children
            $childrenSubscriptions = [];
            foreach ($guardianProfile->students as $studentProfile) {
                $activeSubscription = $studentProfile->activeSubscription();
                if ($activeSubscription) {
                    $childrenSubscriptions[] = [
                        'student_name' => $studentProfile->user->name,
                        'plan_name' => $activeSubscription->plan->name ?? 'Unknown Plan',
                        'start_date' => $activeSubscription->start_date->format('M j, Y'),
                        'end_date' => $activeSubscription->end_date->format('M j, Y'),
                        'amount_paid' => $activeSubscription->amount_paid,
                        'currency' => $activeSubscription->currency,
                        'status' => $activeSubscription->status,
                        'auto_renew' => $activeSubscription->auto_renew,
                    ];
                }
            }
            $subscriptionInfo = $childrenSubscriptions;
        }

        // Get class history (recent teaching sessions) - for students or guardian's children
        $classHistory = [];
        try {
            if ($studentProfile) {
                $classHistory = TeachingSession::where('student_id', $user->id)
                    ->with(['teacher', 'subject'])
                    ->orderBy('session_date', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'date' => $session->session_date->format('M j, Y'),
                            'time' => $session->start_time->format('H:i'),
                            'teacher' => $session->teacher->name,
                            'subject' => $session->subject->name,
                            'status' => $session->status,
                            'attendance' => $session->student_marked_present ? 'Present' : 'Absent',
                            'rating' => $session->student_rating,
                        ];
                    });
            } elseif ($guardianProfile) {
                // For guardians, get class history for all children
                $studentIds = $guardianProfile->students->pluck('user_id');
                $classHistory = TeachingSession::whereIn('student_id', $studentIds)
                    ->with(['teacher', 'subject', 'student'])
                    ->orderBy('session_date', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'date' => $session->session_date->format('M j, Y'),
                            'time' => $session->start_time->format('H:i'),
                            'teacher' => $session->teacher->name,
                            'subject' => $session->subject->name,
                            'student_name' => $session->student->name ?? 'Unknown Student',
                            'status' => $session->status,
                            'attendance' => $session->student_marked_present ? 'Present' : 'Absent',
                            'rating' => $session->student_rating,
                        ];
                    });
            }
        } catch (\Exception $e) {
            // If teaching sessions table doesn't exist or there's an error, just use empty array
            $classHistory = [];
        }

        // Get booking activity (recent booking changes) - safely handle if table doesn't exist
        $bookingActivity = [];
        try {
            if ($studentProfile) {
                $bookingActivity = BookingHistory::whereHas('booking', function ($q) use ($user) {
                        $q->where('student_id', $user->id);
                    })
                    ->with(['booking', 'performedBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($history) {
                        return [
                            'id' => $history->id,
                            'action' => $history->action,
                            'date' => $history->created_at->format('M j, Y'),
                            'time' => $history->created_at->format('H:i'),
                            'performed_by' => $history->performedBy->name,
                            'booking_date' => $history->booking->booking_date->format('M j, Y'),
                        ];
                    });
            } else if ($guardianProfile) {
                // For guardians, get booking activity for all their students
                $studentIds = $guardianProfile->students->pluck('user_id');
                $bookingActivity = BookingHistory::whereHas('booking', function ($q) use ($studentIds) {
                        $q->whereIn('student_id', $studentIds);
                    })
                    ->with(['booking', 'performedBy'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($history) {
                        return [
                            'id' => $history->id,
                            'action' => $history->action,
                            'date' => $history->created_at->format('M j, Y'),
                            'time' => $history->created_at->format('H:i'),
                            'performed_by' => $history->performedBy->name,
                            'booking_date' => $history->booking->booking_date->format('M j, Y'),
                        ];
                    });
            }
        } catch (\Exception $e) {
            // If booking history table doesn't exist or there's an error, just use empty array
            $bookingActivity = [];
        }

        // Get learning progress - for students or guardian's children
        $learningProgress = [];
        try {
            if ($studentProfile) {
                $learningProgress = $studentProfile->learningProgress()
                    ->with('subject')
                    ->get()
                    ->map(function ($progress) {
                        return [
                            'subject' => $progress->subject->name,
                            'progress_percentage' => $progress->progress_percentage,
                            'completed_sessions' => $progress->completed_sessions,
                            'total_sessions' => $progress->total_sessions,
                            'certificates_earned' => count($progress->certificates_earned ?? []),
                        ];
                    });
            } elseif ($guardianProfile) {
                // For guardians, get learning progress for all children
                $allProgress = [];
                foreach ($guardianProfile->students as $studentProfile) {
                    $childProgress = $studentProfile->learningProgress()
                        ->with('subject')
                        ->get()
                        ->map(function ($progress) use ($studentProfile) {
                            return [
                                'student_name' => $studentProfile->user->name,
                                'subject' => $progress->subject->name,
                                'progress_percentage' => $progress->progress_percentage,
                                'completed_sessions' => $progress->completed_sessions,
                                'total_sessions' => $progress->total_sessions,
                                'certificates_earned' => count($progress->certificates_earned ?? []),
                            ];
                        });
                    $allProgress = array_merge($allProgress, $childProgress->toArray());
                }
                $learningProgress = $allProgress;
            }
        } catch (\Exception $e) {
            // If learning progress table doesn't exist or there's an error, just use empty array
            $learningProgress = [];
        }

        // Get upcoming sessions - for students or guardian's children
        $upcomingSessions = [];
        try {
            if ($studentProfile) {
                $upcomingSessions = TeachingSession::where('student_id', $user->id)
                    ->where('session_date', '>=', now())
                    ->where('status', 'scheduled')
                    ->with(['teacher', 'subject'])
                    ->orderBy('session_date', 'asc')
                    ->limit(5)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'date' => $session->session_date->format('M j, Y'),
                            'time' => $session->start_time->format('H:i'),
                            'teacher_name' => $session->teacher->name,
                        ];
                    });
            } elseif ($guardianProfile) {
                // For guardians, get upcoming sessions for all children
                $studentIds = $guardianProfile->students->pluck('user_id');
                $upcomingSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('session_date', '>=', now())
                    ->where('status', 'scheduled')
                    ->with(['teacher', 'subject', 'student'])
                    ->orderBy('session_date', 'asc')
                    ->limit(5)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'date' => $session->session_date->format('M j, Y'),
                            'time' => $session->start_time->format('H:i'),
                            'teacher_name' => $session->teacher->name,
                            'student_name' => $session->student->name ?? 'Unknown Student',
                        ];
                    });
            }
        } catch (\Exception $e) {
            // If teaching sessions table doesn't exist or there's an error, just use empty array
            $upcomingSessions = [];
        }

        // Get rescheduled sessions count - for students or guardian's children
        $rescheduledSessionsCount = 0;
        try {
            if ($studentProfile) {
                $rescheduledSessionsCount = TeachingSession::where('student_id', $user->id)
                    ->where('status', 'rescheduled')
                    ->count();
            } elseif ($guardianProfile) {
                // For guardians, get rescheduled sessions count for all children
                $studentIds = $guardianProfile->students->pluck('user_id');
                $rescheduledSessionsCount = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('status', 'rescheduled')
                    ->count();
            }
        } catch (\Exception $e) {
            // If teaching sessions table doesn't exist or there's an error, just use 0
            $rescheduledSessionsCount = 0;
        }

        // Prepare user data based on role
        $userData = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar' => $user->avatar,
            'role' => $user->role,
            'location' => $user->location,
        ];

        if ($studentProfile) {
            // Student data
            $userData = array_merge($userData, [
                'status' => $user->account_status, // Use user's account_status instead of profile status
                'registration_date' => $studentProfile->formatted_registration_date,
                'guardian' => $studentProfile->guardian ? [
                    'id' => $studentProfile->guardian->id,
                    'name' => $studentProfile->guardian->name,
                    'email' => $studentProfile->guardian->email,
                    'phone' => $studentProfile->guardian->phone,
                ] : null,
                'profile' => [
                    'date_of_birth' => $studentProfile->date_of_birth?->format('M j, Y'),
                    'gender' => $studentProfile->gender,
                    'grade_level' => $studentProfile->grade_level,
                    'school_name' => $studentProfile->school_name,
                    'learning_goals' => $studentProfile->learning_goals,
                    'subjects_of_interest' => $studentProfile->subjects_of_interest,
                    'preferred_learning_times' => $studentProfile->preferred_learning_times,
                    'teaching_mode' => $studentProfile->teaching_mode,
                    'additional_notes' => $studentProfile->additional_notes,
                    'age_group' => $studentProfile->age_group,
                ],
                'stats' => [
                    'completed_sessions' => $studentProfile->completed_sessions_count,
                    'total_sessions' => $studentProfile->total_sessions_count,
                    'attendance_percentage' => $studentProfile->attendance_percentage,
                    'missed_sessions' => $studentProfile->missed_sessions_count,
                    'average_engagement' => $studentProfile->average_engagement,
                ],
                'upcoming_sessions' => $upcomingSessions,
                'rescheduled_sessions' => $rescheduledSessionsCount,
            ]);
        } else if ($guardianProfile) {
            // Guardian data - show children's information
            $children = $guardianProfile->students->map(function ($studentProfile) {
                return [
                    'id' => $studentProfile->user->id,
                    'name' => $studentProfile->user->name,
                    'age' => $studentProfile->age,
                    'grade_level' => $studentProfile->grade_level,
                    'school_name' => $studentProfile->school_name,
                    'learning_goals' => $studentProfile->learning_goals,
                    'subjects_of_interest' => $studentProfile->subjects_of_interest ?? [],
                    'preferred_learning_times' => $studentProfile->preferred_learning_times ?? [],
                    'teaching_mode' => $studentProfile->teaching_mode,
                    'additional_notes' => $studentProfile->additional_notes,
                    'age_group' => $studentProfile->age_group,
                ];
            });

            // Calculate aggregated stats from all children
            $studentIds = $guardianProfile->students->pluck('user_id');
            $aggregatedStats = [
                'completed_sessions' => 0,
                'total_sessions' => 0,
                'attendance_percentage' => 0,
                'missed_sessions' => 0,
                'average_engagement' => 0,
            ];
            
            try {
                $completedSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('status', 'completed')
                    ->count();
                
                $totalSessions = TeachingSession::whereIn('student_id', $studentIds)->count();
                
                $missedSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('status', 'missed')
                    ->count();
                
                $attendancePercentage = $totalSessions > 0 ? 
                    round((($totalSessions - $missedSessions) / $totalSessions) * 100) : 0;
                
                $averageEngagement = TeachingSession::whereIn('student_id', $studentIds)
                    ->whereNotNull('engagement_score')
                    ->avg('engagement_score') ?? 0;
                
                $aggregatedStats = [
                    'completed_sessions' => $completedSessions,
                    'total_sessions' => $totalSessions,
                    'attendance_percentage' => $attendancePercentage,
                    'missed_sessions' => $missedSessions,
                    'average_engagement' => round($averageEngagement),
                ];
            } catch (\Exception $e) {
                \Log::error('Error calculating guardian aggregated stats: ' . $e->getMessage());
            }
            
            $userData = array_merge($userData, [
                'status' => $user->account_status,
                'registration_date' => $guardianProfile->formatted_registration_date,
                'guardian' => null, // Guardians don't have guardians
                'profile' => [
                    'date_of_birth' => null,
                    'gender' => null,
                    'grade_level' => null,
                    'school_name' => null,
                    'learning_goals' => null,
                    'subjects_of_interest' => [],
                    'preferred_learning_times' => [],
                    'teaching_mode' => null,
                    'additional_notes' => $guardianProfile->additional_notes ?? null,
                    'age_group' => null,
                ],
                'stats' => $aggregatedStats,
                'upcoming_sessions' => $upcomingSessions, // Already calculated above
                'rescheduled_sessions' => $rescheduledSessionsCount, // Already calculated above
                'children' => $children,
                'is_guardian' => true, // Flag to identify this is a guardian view
            ]);
        }

        // Get available subjects for learning preferences (from templates to avoid duplicates)
        $availableSubjects = SubjectTemplates::where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->pluck('name')
            ->toArray();

        // Define age groups and time slots (these could be moved to database tables later)
        $ageGroups = [
            '3-6 Years',
            '7-9 Years', 
            '10-12 Years',
            '13-15 Years',
            '16-18 Years',
            '19+ Years'
        ];

        $timeSlots = [
            '6:00 AM', '6:30 AM', '7:00 AM', '7:30 AM', '8:00 AM', '8:30 AM',
            '9:00 AM', '9:30 AM', '10:00 AM', '10:30 AM', '11:00 AM', '11:30 AM',
            '12:00 PM', '12:30 PM', '1:00 PM', '1:30 PM', '2:00 PM', '2:30 PM',
            '3:00 PM', '3:30 PM', '4:00 PM', '4:30 PM', '5:00 PM', '5:30 PM',
            '6:00 PM', '6:30 PM', '7:00 PM', '7:30 PM', '8:00 PM', '8:30 PM',
            '9:00 PM', '9:30 PM', '10:00 PM'
        ];

        return Inertia::render('admin/students/show', [
            'student' => $userData,
            'subscription' => $subscriptionInfo,
            'classHistory' => $classHistory,
            'bookingActivity' => $bookingActivity,
            'learningProgress' => $learningProgress,
            'learningPreferencesOptions' => [
                'subjects' => $availableSubjects,
                'ageGroups' => $ageGroups,
                'timeSlots' => $timeSlots,
            ],
        ]);
    }

    /**
     * Approve a student or guardian account.
     */
    public function approve(User $user)
    {
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404, 'User not found');
        }

        if ($user->role === 'student') {
            $this->studentService->approveStudent($user);
            return redirect()->back()->with('success', 'Student approved successfully.');
        } else {
            $this->approveGuardian($user);
            return redirect()->back()->with('success', 'Parent approved successfully.');
        }
    }

    /**
     * Suspend a student or guardian account.
     */
    public function suspend(User $user)
    {
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404, 'User not found');
        }

        if ($user->role === 'student') {
            $this->studentService->suspendStudent($user);
            return redirect()->back()->with('success', 'Student suspended successfully.');
        } else {
            $this->suspendGuardian($user);
            return redirect()->back()->with('success', 'Parent suspended successfully.');
        }
    }

    /**
     * Update student contact information.
     */
    public function updateContactInfo(Request $request, User $user)
    {
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404, 'User not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
            'role' => 'required|in:student,guardian',
            'account_status' => 'required|in:active,suspended,pending',
        ]);

        // Debug: Log what we received and what we're sending
        \Log::info('Controller received data:', [
            'request_data' => $request->all(),
            'validated_data' => $validated,
            'user_id' => $user->id,
            'current_user_location' => $user->location,
        ]);

        $this->studentService->updateContactInfo($user, $validated);

        return redirect()->back()->with('success', 'Contact information updated successfully.');
    }

    /**
     * Update student learning preferences.
     */
    public function updatePreferences(Request $request, User $user)
    {
        if ($user->role !== 'student') {
            abort(404, 'Student not found');
        }

        $validated = $request->validate([
            'subjects_of_interest' => 'nullable|array',
            'preferred_learning_times' => 'nullable|array',
            'learning_goals' => 'nullable|string',
            'teaching_mode' => 'nullable|in:full-time,part-time',
            'additional_notes' => 'nullable|string',
        ]);

        $this->studentService->updatePreferences($user, $validated);

        return redirect()->back()->with('success', 'Learning preferences updated successfully.');
    }

    /**
     * Update student learning preferences (new comprehensive method).
     */
    public function updateLearningPreferences(Request $request, User $user)
    {
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404, 'User not found');
        }

        $validated = $request->validate([
            'subjects_of_interest' => 'nullable|array',
            'teaching_mode' => 'nullable|in:full-time,part-time',
            'student_age_group' => 'nullable|string',
            'preferred_learning_times' => 'nullable|array',
            'additional_notes' => 'nullable|string',
        ]);

        // Update the profile with new preferences
        if ($user->role === 'student' && $user->studentProfile) {
            $user->studentProfile->update([
                'subjects_of_interest' => $validated['subjects_of_interest'] ?? [],
                'teaching_mode' => $validated['teaching_mode'],
                'age_group' => $validated['student_age_group'],
                'preferred_learning_times' => $validated['preferred_learning_times'] ?? [],
                'additional_notes' => $validated['additional_notes'],
            ]);
        } elseif ($user->role === 'guardian' && $user->guardianProfile) {
            // For guardians, we might want to store preferences differently
            // For now, let's update the guardian profile if it has these fields
            $user->guardianProfile->update([
                'additional_notes' => $validated['additional_notes'],
            ]);
        }

        return redirect()->back()->with('success', 'Learning preferences updated successfully.');
    }

    /**
     * Approve a guardian account.
     */
    private function approveGuardian(User $guardian)
    {
        $guardian->guardianProfile()->update([
            'status' => 'active'
        ]);

        // Update user email verification if not verified
        if (!$guardian->email_verified_at) {
            $guardian->update([
                'email_verified_at' => now()
            ]);
        }
    }

    /**
     * Suspend a guardian account.
     */
    private function suspendGuardian(User $guardian)
    {
        $guardian->guardianProfile()->update([
            'status' => 'suspended'
        ]);
    }

    /**
     * Show learning progress for a student or guardian.
     */
    public function learningProgress(User $user)
    {
        // Check if user is a student or guardian
        if (!in_array($user->role, ['student', 'guardian'])) {
            abort(404);
        }

        // Get learning sessions data
        $learningSessions = [];
        
        try {
            if ($user->role === 'student') {
                // Get sessions for student
                $learningSessions = TeachingSession::where('student_id', $user->id)
                    ->with(['teacher', 'subject'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'date' => $session->created_at->format('Y-m-d'),
                            'subject' => $session->subject->name ?? 'Unknown Subject',
                            'teacher_name' => $session->teacher->name ?? 'Unknown Teacher',
                            'duration' => $session->duration ?? 60,
                            'status' => $session->status ?? 'completed',
                            'attendance_score' => $session->attendance_score ?? null,
                            'engagement_score' => $session->engagement_score ?? null,
                            'notes' => $session->notes ?? null,
                        ];
                    });
            } else {
                // Get sessions for guardian's children
                $studentIds = $user->guardianProfile->students->pluck('user_id');
                $learningSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->with(['teacher', 'subject'])
                    ->orderBy('created_at', 'desc')
                    ->limit(20)
                    ->get()
                    ->map(function ($session) {
                        return [
                            'id' => $session->id,
                            'date' => $session->created_at->format('Y-m-d'),
                            'subject' => $session->subject->name ?? 'Unknown Subject',
                            'teacher_name' => $session->teacher->name ?? 'Unknown Teacher',
                            'duration' => $session->duration ?? 60,
                            'status' => $session->status ?? 'completed',
                            'attendance_score' => $session->attendance_score ?? null,
                            'engagement_score' => $session->engagement_score ?? null,
                            'notes' => $session->notes ?? null,
                        ];
                    });
            }
        } catch (\Exception $e) {
            // Log error and return empty sessions array
            \Log::error('Error fetching learning sessions: ' . $e->getMessage());
            $learningSessions = [];
        }

        return Inertia::render('admin/students/learning-progress', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'stats' => $this->getStudentStats($user),
            ],
            'learningSessions' => $learningSessions,
        ]);
    }

    /**
     * Get student statistics for learning progress.
     */
    private function getStudentStats(User $user)
    {
        try {
            if ($user->role === 'student') {
                $completedSessions = TeachingSession::where('student_id', $user->id)
                    ->where('status', 'completed')
                    ->count();
                
                $totalSessions = TeachingSession::where('student_id', $user->id)->count();
                
                $missedSessions = TeachingSession::where('student_id', $user->id)
                    ->where('status', 'missed')
                    ->count();
                
                $attendancePercentage = $totalSessions > 0 ? 
                    round((($totalSessions - $missedSessions) / $totalSessions) * 100) : 0;
                
                $averageEngagement = TeachingSession::where('student_id', $user->id)
                    ->whereNotNull('engagement_score')
                    ->avg('engagement_score') ?? 0;
                
                return [
                    'completed_sessions' => $completedSessions,
                    'total_sessions' => $totalSessions,
                    'attendance_percentage' => $attendancePercentage,
                    'missed_sessions' => $missedSessions,
                    'average_engagement' => round($averageEngagement),
                ];
            } else {
                // For guardians, aggregate stats from all children
                $studentIds = $user->guardianProfile->students->pluck('user_id');
                
                $completedSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('status', 'completed')
                    ->count();
                
                $totalSessions = TeachingSession::whereIn('student_id', $studentIds)->count();
                
                $missedSessions = TeachingSession::whereIn('student_id', $studentIds)
                    ->where('status', 'missed')
                    ->count();
                
                $attendancePercentage = $totalSessions > 0 ? 
                    round((($totalSessions - $missedSessions) / $totalSessions) * 100) : 0;
                
                $averageEngagement = TeachingSession::whereIn('student_id', $studentIds)
                    ->whereNotNull('engagement_score')
                    ->avg('engagement_score') ?? 0;
                
                return [
                    'completed_sessions' => $completedSessions,
                    'total_sessions' => $totalSessions,
                    'attendance_percentage' => $attendancePercentage,
                    'missed_sessions' => $missedSessions,
                    'average_engagement' => round($averageEngagement),
                ];
            }
        } catch (\Exception $e) {
            \Log::error('Error calculating student stats: ' . $e->getMessage());
            return [
                'completed_sessions' => 0,
                'total_sessions' => 0,
                'attendance_percentage' => 0,
                'missed_sessions' => 0,
                'average_engagement' => 0,
            ];
        }
    }


}

