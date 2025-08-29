<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\GuardianProfile;
use App\Models\StudentLearningSchedule;
use App\Models\SubjectTemplates;
use App\Models\TeacherAvailability;
use App\Models\TeacherProfile;
use App\Models\TeacherEarning;
use App\Models\Subject;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    /**
     * Show the role selection onboarding page.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        
        // If user already has a role, redirect to appropriate dashboard
        if ($user->role !== null) {
            return $this->redirectToDashboard($user->role);
        }

        return Inertia::render('onboarding/role-selection', [
            'user' => $user,
        ]);
    }

    /**
     * Handle role selection for student/guardian users.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => 'required|in:student,guardian',
        ]);

        $user = $request->user();
        $user->update(['role' => $request->role]);

        // Redirect to dashboard with onboarding modal flag
        return match ($request->role) {
            'student' => redirect()->route('student.dashboard')->with('showOnboarding', true),
            'guardian' => redirect()->route('guardian.dashboard')->with('showOnboarding', true),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Show teacher onboarding page.
     */
    public function teacher(Request $request): Response
    {
        $user = $request->user();
        
        // Ensure user is a teacher
        if ($user->role !== 'teacher') {
            return redirect()->route('onboarding');
        }

        // Fetch available subjects from database
        $subjects = SubjectTemplates::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('onboarding/teacher', [
            'user' => $user,
            'subjects' => $subjects,
        ]);
    }

    /**
     * Handle teacher onboarding step saving.
     */
    public function saveTeacherStep(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $step = $request->input('step', 1);
            $user = $request->user();
            
            \Log::info('Saving teacher step: ' . $step, [
                'user_id' => $user->id,
                'request_data' => $request->except(['profile_photo'])
            ]);

            switch ($step) {
                case 1:
                    return $this->saveStep1($request, $user);
                case 2:
                    return $this->saveStep2($request, $user);
                case 3:
                    return $this->saveStep3($request, $user);
                case 4:
                    return $this->saveStep4($request, $user);
                default:
                    return response()->json(['error' => 'Invalid step'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Error in saveTeacherStep: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['success' => false, 'message' => 'Server error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Handle teacher onboarding completion.
     */
    public function storeTeacher(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        // Mark onboarding as complete - all data should already be saved via step saving
        $teacherProfile = $user->teacherProfile()->first();
        if ($teacherProfile) {
            // Create verification request for admin review
            $teacherProfile->verificationRequests()->create([
                'status' => 'pending',
                'docs_status' => 'pending',
                'video_status' => 'not_scheduled',
                'submitted_at' => now(),
            ]);
            
            // TODO: Send notification to admin about new teacher registration
            // TODO: Schedule verification video call
        }

        // Stay on onboarding page - success screen will show until verification
        return redirect()->route('onboarding.teacher')->with('onboarding_completed', true);
    }

    /**
     * Show student onboarding page.
     */
    public function student(Request $request): Response
    {
        $user = $request->user();
        
        // Ensure user is a student
        if ($user->role !== 'student') {
            return redirect()->route('onboarding');
        }

        return Inertia::render('onboarding/student', [
            'user' => $user,
        ]);
    }

    /**
     * Handle student onboarding completion.
     */
    public function storeStudent(Request $request): RedirectResponse
    {
        $request->validate([
            'preferred_subjects' => 'required|array|min:1',
            'preferred_subjects.*' => 'string|exists:subject_templates,name',
            'preferred_learning_times' => 'required|array',
            'preferred_learning_times.*.enabled' => 'boolean',
            'preferred_learning_times.*.from' => 'nullable|string',
            'preferred_learning_times.*.to' => 'nullable|string',
            'current_level' => 'nullable|string|max:100',
            'learning_goals' => 'nullable|string|max:500',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            // Create or update student profile
            $user->studentProfile()->updateOrCreate([
                'user_id' => $user->id
            ], [
                'subjects_of_interest' => $request->preferred_subjects,
                'grade_level' => $request->current_level,
                'learning_goals' => $request->learning_goals,
                'status' => 'active',
                'registration_date' => now(),
            ]);

            // Create learning schedule entries
            $this->createLearningSchedules($user->id, $request->preferred_learning_times);
        });

        return redirect()->route('student.dashboard')->with('success', 'Onboarding completed successfully!');
    }

    /**
     * Show guardian onboarding page.
     */
    public function guardian(Request $request): Response
    {
        $user = $request->user();
        
        // Ensure user is a guardian
        if ($user->role !== 'guardian') {
            return redirect()->route('onboarding');
        }

        return Inertia::render('onboarding/guardian', [
            'user' => $user,
        ]);
    }

    /**
     * Handle guardian onboarding completion.
     */
    public function storeGuardian(Request $request): RedirectResponse
    {
        $request->validate([
            'children' => 'required|array|min:1',
            'children.*.name' => 'required|string|max:100',
            'children.*.age' => 'required|string|max:20',
            'children.*.gender' => 'required|string|in:male,female',
            'children.*.preferred_subjects' => 'required|array|min:1',
            'children.*.preferred_subjects.*' => 'string|exists:subject_templates,name',
            'children.*.preferred_learning_times' => 'required|array',
            'children.*.preferred_learning_times.*.enabled' => 'boolean',
            'children.*.preferred_learning_times.*.from' => 'nullable|string',
            'children.*.preferred_learning_times.*.to' => 'nullable|string',
            'relationship' => 'required|string|in:guardian',
        ]);

        $user = $request->user();

        DB::transaction(function () use ($request, $user) {
            // Create or update guardian profile
            $user->guardianProfile()->updateOrCreate([
                'user_id' => $user->id
            ], [
                'status' => 'active',
                'registration_date' => now(),
                'relationship' => $request->relationship,
                'children_count' => count($request->children), // Will be updated by triggers
            ]);

            // Clear existing children profiles and their user accounts for this guardian
            $existingChildren = StudentProfile::where('guardian_id', $user->id)
                                             ->whereHas('user', function($query) use ($user) {
                                                 $query->where('email', 'like', '%child.of.' . str_replace('@', '.', $user->email));
                                             })
                                             ->with('user')
                                             ->get();
            
            foreach ($existingChildren as $childProfile) {
                if ($childProfile->user) {
                    // Delete learning schedules first
                    StudentLearningSchedule::where('student_id', $childProfile->user->id)->delete();
                    // Delete the user account (this will cascade delete the profile)
                    $childProfile->user->delete();
                }
            }

            // Create user accounts and student profiles for each child
            foreach ($request->children as $child) {
                // Create a user account for the child
                $childUser = User::create([
                    'name' => $child['name'],
                    'email' => $this->generateChildEmail($child['name'], $user->email),
                    'password' => Hash::make(Str::random(12)), // Random password
                    'role' => 'student',
                    'email_verified_at' => now(), // Auto-verify child accounts
                ]);

                // Create student profile for the child
                StudentProfile::create([
                    'user_id' => $childUser->id,
                    'guardian_id' => $user->id,
                    'gender' => $child['gender'],
                    'subjects_of_interest' => $child['preferred_subjects'],
                    'status' => 'active',
                    'registration_date' => now(),
                    'age_group' => $child['age'],
                    'additional_notes' => 'Age: ' . $child['age'] . ' | Managed by guardian: ' . $user->name,
                ]);

                // Create learning schedule entries for the child
                $this->createLearningSchedules($childUser->id, $child['preferred_learning_times']);
            }
        });

        return redirect()->route('guardian.dashboard')->with('success', 'Children profiles created successfully!');
    }

    /**
     * Redirect user to appropriate dashboard based on role.
     */
    private function redirectToDashboard(string $role): RedirectResponse
    {
        return match ($role) {
            'super-admin' => redirect()->route('admin.dashboard'),
            'teacher' => redirect()->route('teacher.dashboard'),
            'student' => redirect()->route('student.dashboard'),
            'guardian' => redirect()->route('guardian.dashboard'),
            default => redirect()->route('dashboard'),
        };
    }

    /**
     * Generate a unique email for a child based on their name and guardian's email.
     */
    private function generateChildEmail(string $childName, string $guardianEmail): string
    {
        // Convert child name to slug format
        $childSlug = Str::slug(strtolower($childName));
        
        // Extract domain from guardian email
        $domain = substr(strrchr($guardianEmail, '@'), 1);
        $guardianLocal = substr($guardianEmail, 0, strpos($guardianEmail, '@'));
        
        // Generate child email: childname.child.of.guardian@domain.com
        $baseEmail = $childSlug . '.child.of.' . str_replace('@', '.', $guardianEmail);
        
        // Ensure uniqueness by adding a number if needed
        $counter = 1;
        $email = $baseEmail;
        
        while (User::where('email', $email)->exists()) {
            $email = $baseEmail . '.' . $counter;
            $counter++;
        }
        
        return $email;
    }

    /**
     * Create learning schedule entries for a student based on their preferred times.
     */
    private function createLearningSchedules(int $studentId, array $preferredTimes): void
    {
        // Clear existing schedules for this student
        StudentLearningSchedule::where('student_id', $studentId)->delete();

        // Map day names to numbers (0=Sunday, 1=Monday, etc.)
        $dayMapping = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        // Create schedule entries for each enabled day
        foreach ($preferredTimes as $dayName => $schedule) {
            if (isset($schedule['enabled']) && $schedule['enabled'] && 
                !empty($schedule['from']) && !empty($schedule['to']) &&
                isset($dayMapping[$dayName])) {
                
                StudentLearningSchedule::create([
                    'student_id' => $studentId,
                    'day_of_week' => $dayMapping[$dayName],
                    'start_time' => $schedule['from'] . ':00', // Add seconds
                    'end_time' => $schedule['to'] . ':00',     // Add seconds
                    'preference_level' => 'high',
                    'is_active' => true,
                ]);
            }
        }
    }

    /**
     * Create teacher availability entries based on their preferred times.
     */
    private function createTeacherAvailability(int $teacherId, array $availability, string $timezone, string $teachingMode = 'part-time'): void
    {
        // Clear existing availability for this teacher
        TeacherAvailability::where('teacher_id', $teacherId)->delete();

        // Map teaching mode to availability type
        $availabilityType = $teachingMode === 'full-time' ? 'Full-Time' : 'Part-Time';

        // Map day names to numbers (0=Sunday, 1=Monday, etc.)
        $dayMapping = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        // Create availability entries for each enabled day
        foreach ($availability as $dayName => $schedule) {
            if (isset($schedule['enabled']) && $schedule['enabled'] && 
                !empty($schedule['from']) && !empty($schedule['to']) &&
                isset($dayMapping[$dayName])) {
                
                TeacherAvailability::create([
                    'teacher_id' => $teacherId,
                    'day_of_week' => $dayMapping[$dayName],
                    'start_time' => $schedule['from'] . ':00', // Add seconds
                    'end_time' => $schedule['to'] . ':00',     // Add seconds
                    'is_active' => true,
                    'time_zone' => $timezone,
                    'availability_type' => $availabilityType, // Now correctly mapped
                ]);
            }
        }
    }

    /**
     * Save Step 1: Personal Information
     */
    private function saveStep1(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:100',
            'country_code' => 'nullable|string|max:3',
            'calling_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'profile_photo' => 'nullable|image|max:5120',
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                // Handle profile photo upload
                $profilePhotoPath = null;
                if ($request->hasFile('profile_photo')) {
                    $profilePhotoPath = $request->file('profile_photo')->store('teacher-profiles', 'public');
                }

                // Update user information
                $location = '';
                if ($request->city && $request->country) {
                    $location = $request->city . ', ' . $request->country;
                } elseif ($request->country) {
                    $location = $request->country;
                } elseif ($request->city) {
                    $location = $request->city;
                }
                
                $updateData = [
                    'name' => $request->name,
                    'phone' => $request->calling_code . ' ' . $request->phone,
                    'country' => $request->country,
                    'city' => $request->city,
                    'location' => $location, // Combined location field
                ];
                
                // Only update avatar if a new photo was uploaded
                if ($profilePhotoPath) {
                    $updateData['avatar'] = $profilePhotoPath;
                }
                
                $user->update($updateData);
            });

            return response()->json(['success' => true, 'message' => 'Step 1 saved successfully']);
        } catch (\Exception $e) {
            \Log::error('Error saving step 1: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving step 1: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Save Step 2: Teaching Details
     */
    private function saveStep2(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'subjects' => 'nullable|string', // JSON string
            'experience_years' => 'nullable|string|max:50',
            'qualification' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                // Decode subjects JSON
                $subjects = json_decode($request->subjects, true) ?: [];
                
                // Create or update teacher profile
                $teacherProfile = $user->teacherProfile()->updateOrCreate([
                    'user_id' => $user->id
                ], [
                    'bio' => $request->bio,
                    'experience_years' => $request->experience_years,
                    'qualification' => $request->qualification,
                    'languages' => json_encode(['English']),
                    'teaching_type' => 'Online',
                    'verified' => false,
                    'join_date' => now(),
                ]);

                // Clear existing subjects and create new ones
                $teacherProfile->subjects()->delete();
                foreach ($subjects as $subjectName) {
                    $subjectTemplate = SubjectTemplates::where('name', $subjectName)->first();
                    if ($subjectTemplate) {
                        Subject::create([
                            'teacher_profile_id' => $teacherProfile->id,
                            'subject_template_id' => $subjectTemplate->id,
                            'teacher_notes' => 'Teaching ' . $subjectName,
                            'is_active' => true,
                        ]);
                    }
                }
            });

            return response()->json(['success' => true, 'message' => 'Step 2 saved successfully']);
        } catch (\Exception $e) {
            \Log::error('Error saving step 2: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving step 2: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Save Step 3: Availability & Schedule
     */
    private function saveStep3(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'timezone' => 'nullable|string|max:50',
            'teaching_mode' => 'nullable|string|in:full-time,part-time',
            'availability' => 'nullable|string', // JSON string
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                // Decode availability JSON
                $availability = json_decode($request->availability, true) ?: [];
                
                // Update teacher profile with teaching mode
                $teacherProfile = $user->teacherProfile()->first();
                if ($teacherProfile) {
                    $teacherProfile->update([
                        'teaching_mode' => $request->teaching_mode === 'full-time' ? 'Full-Time' : 'Part-Time',
                    ]);
                }

                // Create teacher availability entries
                $this->createTeacherAvailability($user->id, $availability, $request->timezone, $request->teaching_mode);
            });

            return response()->json(['success' => true, 'message' => 'Step 3 saved successfully']);
        } catch (\Exception $e) {
            \Log::error('Error saving step 3: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving step 3: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Save Step 4: Payment & Earnings
     */
    private function saveStep4(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'currency' => 'nullable|string|in:naira,dollar',
            'hourly_rate' => 'nullable|numeric|min:1',
            'payment_method' => 'nullable|string|in:bank_transfer,paypal,stripe,flutterwave',
        ]);

        try {
            DB::transaction(function () use ($request, $user) {
                // Update teacher profile with payment information
                $teacherProfile = $user->teacherProfile()->first();
                if ($teacherProfile) {
                    $teacherProfile->update([
                        'hourly_rate_usd' => $request->currency === 'dollar' ? $request->hourly_rate : null,
                        'hourly_rate_ngn' => $request->currency === 'naira' ? $request->hourly_rate : null,
                    ]);
                }

                // Create or update teacher earnings record
                TeacherEarning::updateOrCreate(
                    ['teacher_id' => $user->id],
                    [
                        'wallet_balance' => 0,
                        'total_earned' => 0,
                        'total_withdrawn' => 0,
                        'pending_payouts' => 0,
                    ]
                );

                // Store payment method preference in user's session or cache for later use
                // When teacher makes their first payout request, we'll use this preference
                if ($request->payment_method) {
                    \Cache::put("teacher_payment_preference_{$user->id}", [
                        'payment_method' => $request->payment_method,
                        'currency' => $request->currency,
                        'hourly_rate' => $request->hourly_rate,
                    ], now()->addDays(30)); // Store for 30 days
                }
            });

            return response()->json(['success' => true, 'message' => 'Step 4 saved successfully']);
        } catch (\Exception $e) {
            \Log::error('Error saving step 4: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error saving step 4: ' . $e->getMessage()], 500);
        }
    }
}
