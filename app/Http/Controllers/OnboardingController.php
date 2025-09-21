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
use App\Models\TeacherWallet;
use App\Models\Subject;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\UnifiedWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __construct(
        private UnifiedWalletService $walletService
    ) {}
    /**
     * Show the role selection onboarding page for OAuth users.
     */
    public function roleSelection(Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        
        // If user already has a role, redirect to appropriate dashboard
        if ($user->role !== null && $user->role !== 'unassigned') {
            return $this->redirectToDashboard($user->role);
        }

        return Inertia::render('onboarding/role-selection', [
            'user' => $user,
        ]);
    }

    /**
     * Handle role selection for OAuth users.
     */
    public function storeRoleSelection(Request $request): RedirectResponse
    {
        $request->validate([
            'role' => 'required|in:student,guardian',
        ]);

        $user = $request->user();
        $role = $request->input('role');

        return DB::transaction(function () use ($user, $role) {
            // Update user role
            $user->update(['role' => $role]);

            // Create appropriate profile and wallet
            $this->createUserProfileAndWallet($user, $role);

            // Redirect to appropriate onboarding
            return match ($role) {
                'student' => redirect()->route('onboarding.student'),
                'guardian' => redirect()->route('onboarding.guardian'),
                default => redirect()->route('onboarding.role-selection'),
            };
        });
    }

    /**
     * Show the role selection onboarding page.
     */
    public function index(Request $request): Response|RedirectResponse
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
    public function teacher(Request $request): Response|RedirectResponse
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

        // Get available currencies from CurrencyService
        $currencyService = app(\App\Services\CurrencyService::class);
        $availableCurrencies = $currencyService->getAvailableCurrencies();

        return Inertia::render('onboarding/teacher', [
            'user' => $user,
            'subjects' => $subjects,
            'availableCurrencies' => $availableCurrencies,
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
    public function student(Request $request): Response|RedirectResponse
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
            'preferred_subjects' => 'nullable|array',
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
                'subjects_of_interest' => $request->preferred_subjects ?? [],
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
    public function guardian(Request $request): Response|RedirectResponse
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

        // Map day names to proper format and numeric values
        $dayMapping = [
            'sunday' => ['name' => 'Sunday', 'number' => 0],
            'monday' => ['name' => 'Monday', 'number' => 1],
            'tuesday' => ['name' => 'Tuesday', 'number' => 2],
            'wednesday' => ['name' => 'Wednesday', 'number' => 3],
            'thursday' => ['name' => 'Thursday', 'number' => 4],
            'friday' => ['name' => 'Friday', 'number' => 5],
            'saturday' => ['name' => 'Saturday', 'number' => 6],
        ];

        $availableDays = [];
        $daySchedules = [];

        // Process availability for each day
        foreach ($availability as $dayName => $schedule) {
            if (isset($schedule['enabled']) && $schedule['enabled'] && 
                !empty($schedule['from']) && !empty($schedule['to']) &&
                isset($dayMapping[$dayName])) {
                
                $dayInfo = $dayMapping[$dayName];
                $dayNameFormatted = $dayInfo['name'];
                $dayNumber = $dayInfo['number'];
                
                $availableDays[] = $dayNameFormatted;
                
                $daySchedules[] = [
                    'day' => $dayNameFormatted,
                    'enabled' => true,
                    'fromTime' => $schedule['from'],
                    'toTime' => $schedule['to']
                ];

                // Create individual record for backward compatibility
                TeacherAvailability::create([
                    'teacher_id' => $teacherId,
                    'holiday_mode' => false,
                    'is_active' => true,
                    'day_of_week' => $dayNumber,
                    'start_time' => $schedule['from'] . ':00',
                    'end_time' => $schedule['to'] . ':00',
                    'time_zone' => $timezone,
                    'availability_type' => $availabilityType,
                ]);
            } else {
                // Add disabled day to schedules
                $dayInfo = $dayMapping[$dayName];
                $dayNameFormatted = $dayInfo['name'];
                
                $daySchedules[] = [
                    'day' => $dayNameFormatted,
                    'enabled' => false,
                    'fromTime' => '09:00',
                    'toTime' => '17:00'
                ];
            }
        }

        // Create main availability record with new format data
        TeacherAvailability::create([
            'teacher_id' => $teacherId,
            'holiday_mode' => false,
            'is_active' => true,
            'available_days' => $availableDays, // Will be automatically cast to JSON by the model
            'day_schedules' => $daySchedules,   // Will be automatically cast to JSON by the model
            'time_zone' => $timezone,
            'availability_type' => $availabilityType,
        ]);
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
            'timezone' => 'required|string|max:50',
            'teaching_mode' => 'required|string|in:full-time,part-time',
            'availability' => 'required|string', // JSON string
        ]);

        try {
            // Decode availability JSON
            $availability = json_decode($request->availability, true) ?: [];
            
            // Validate that at least one day is enabled
            $hasEnabledDay = false;
            foreach ($availability as $dayName => $schedule) {
                if (isset($schedule['enabled']) && $schedule['enabled'] && 
                    !empty($schedule['from']) && !empty($schedule['to'])) {
                    $hasEnabledDay = true;
                    break;
                }
            }
            
            if (!$hasEnabledDay) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Please select at least one day and set your available hours to continue.',
                    'errors' => ['availability' => ['You must select at least one day with available hours.']]
                ], 422);
            }

            DB::transaction(function () use ($request, $user, $availability) {
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
     * Save Step 4: Payment & Earnings Setup with Unified Wallet
     */
    private function saveStep4(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'preferred_currency' => 'required|string|in:NGN,USD,EUR,GBP',
            'hourly_rate_usd' => 'nullable|numeric|min:0|max:1000',
            'hourly_rate_ngn' => 'nullable|numeric|min:0|max:1000000',
            'withdrawal_method' => 'required|string|in:bank_transfer,paystack,stripe',
            
            // Bank transfer fields (conditional)
            'bank_name' => 'required_if:withdrawal_method,bank_transfer|nullable|string|max:255',
            'custom_bank_name' => 'required_if:bank_name,other|nullable|string|max:255',
            'account_number' => 'required_if:withdrawal_method,bank_transfer|nullable|string|max:50',
            'account_name' => 'required_if:withdrawal_method,bank_transfer|nullable|string|max:255',
        ]);

        // Custom validation: At least one hourly rate must be provided
        if (empty($request->hourly_rate_usd) && empty($request->hourly_rate_ngn)) {
            return response()->json([
                'success' => false, 
                'message' => 'Please provide at least one hourly rate (USD or NGN).'
            ], 422);
        }

        try {
            DB::transaction(function () use ($request, $user) {
                // Update teacher profile with payment information
                $teacherProfile = $user->teacherProfile()->first();
                if ($teacherProfile) {
                    $teacherProfile->update([
                        'preferred_currency' => $request->preferred_currency,
                        'hourly_rate_usd' => $request->hourly_rate_usd,
                        'hourly_rate_ngn' => $request->hourly_rate_ngn,
                    ]);
                }

                // Create or update teacher earnings record (for backward compatibility)
                TeacherEarning::updateOrCreate(
                    ['teacher_id' => $user->id],
                    [
                        'wallet_balance' => 0,
                        'total_earned' => 0,
                        'total_withdrawn' => 0,
                        'pending_payouts' => 0,
                    ]
                );

                // Create teacher wallet with payment methods and settings
                $paymentMethods = [];
                
                // Add withdrawal method to payment methods
                if ($request->withdrawal_method === 'bank_transfer') {
                    $bankName = $request->bank_name === 'other' ? $request->custom_bank_name : $request->bank_name;
                    
                    $paymentMethods[] = [
                        'id' => 'bank_' . uniqid(),
                        'type' => 'bank_transfer',
                        'bank_name' => $bankName,
                        'account_number' => $request->account_number,
                        'account_name' => $request->account_name,
                        'is_default' => true,
                        'created_at' => now()->toDateTimeString(),
                    ];
                } else {
                    // For paystack, stripe
                    $paymentMethods[] = [
                        'id' => $request->withdrawal_method . '_' . uniqid(),
                        'type' => $request->withdrawal_method,
                        'is_default' => true,
                        'created_at' => now()->toDateTimeString(),
                    ];
                }

                $withdrawalSettings = [
                    'preferred_method' => $request->withdrawal_method,
                    'preferred_currency' => $request->preferred_currency,
                ];

                // Create or update teacher wallet
                $wallet = TeacherWallet::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'balance' => 0,
                        'total_earned' => 0,
                        'total_withdrawn' => 0,
                        'pending_payouts' => 0,
                        'payment_methods' => $paymentMethods,
                        'default_payment_method' => $paymentMethods[0]['id'] ?? null,
                        'auto_withdrawal_enabled' => false,
                        'auto_withdrawal_threshold' => null,
                        'withdrawal_settings' => $withdrawalSettings,
                    ]
                );

                \Log::info('Teacher wallet created/updated during onboarding', [
                    'user_id' => $user->id,
                    'wallet_id' => $wallet->id,
                    'withdrawal_method' => $request->withdrawal_method,
                    'preferred_currency' => $request->preferred_currency,
                ]);
            });

            return response()->json([
                'success' => true, 
                'message' => 'Payment setup completed successfully! Your teacher wallet is ready.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error saving teacher payment setup: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Error setting up payment preferences: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create user profile and wallet based on role
     */
    public function createUserProfileAndWallet(User $user, string $role): void
    {
        match ($role) {
            'student' => $this->createStudentProfileAndWallet($user),
            'guardian' => $this->createGuardianProfileAndWallet($user),
            'teacher' => $this->createTeacherProfileAndWallet($user),
            default => null,
        };
    }

    /**
     * Create student profile and wallet
     */
    private function createStudentProfileAndWallet(User $user): void
    {
        // Create student profile
        StudentProfile::create([
            'user_id' => $user->id,
            'date_of_birth' => null,
            'grade_level' => null,
            'school_name' => null,
            'learning_goals' => null,
            'subjects_of_interest' => [],
            'preferred_learning_style' => null,
            'availability' => [],
            'parent_guardian_name' => null,
            'parent_guardian_phone' => null,
            'parent_guardian_email' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_relationship' => null,
            'medical_conditions' => null,
            'allergies' => null,
            'special_instructions' => null,
        ]);

        // Create student wallet
        $this->walletService->createStudentWallet($user);
    }

    /**
     * Create guardian profile and wallet
     */
    private function createGuardianProfileAndWallet(User $user): void
    {
        // Create guardian profile
        GuardianProfile::create([
            'user_id' => $user->id,
            'relationship_to_students' => null,
            'emergency_contact_name' => null,
            'emergency_contact_phone' => null,
            'emergency_contact_relationship' => null,
            'preferred_communication_method' => 'email',
            'communication_preferences' => [],
            'notification_preferences' => [],
        ]);

        // Create guardian wallet
        $this->walletService->createGuardianWallet($user);
    }

    /**
     * Create teacher profile and wallet
     */
    private function createTeacherProfileAndWallet(User $user): void
    {
        // Create teacher profile
        TeacherProfile::create([
            'user_id' => $user->id,
            'status' => 'pending_verification',
            'bio' => '',
            'specializations' => [],
            'languages' => [],
            'hourly_rate' => 0,
            'availability' => [],
            'teaching_experience' => null,
            'education_background' => null,
            'certifications' => [],
            'teaching_philosophy' => null,
            'video_introduction_url' => null,
            'sample_lesson_url' => null,
            'references' => [],
            'bank_account_details' => null,
            'tax_information' => null,
            'withdrawal_settings' => [],
            'payment_methods' => [],
        ]);

        // Create teacher wallet
        $this->walletService->createTeacherWallet($user);
    }
}
