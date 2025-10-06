<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Subject;
use App\Models\SubjectTemplates;
use App\Models\TeacherAvailability;
use App\Models\TeacherEarning;
use App\Models\TeacherProfile;
use App\Models\TeachingSession;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Models\VerificationAuditLog;
use App\Models\Transaction;
use App\Models\PayoutRequest;
use App\Services\FinancialService;
use App\Services\TeacherStatusService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class TeacherManagementController extends Controller
{
    protected $financialService;
    protected $teacherStatusService;

    public function __construct(
        FinancialService $financialService,
        TeacherStatusService $teacherStatusService
    ) {
        $this->financialService = $financialService;
        $this->teacherStatusService = $teacherStatusService;
    }

    /**
     * Display a listing of teachers.
     */
    public function index(Request $request): Response
    {
        // Apply filters
        $query = User::where('role', 'teacher')
            ->with(['teacherProfile']);
            
        // Filter by status if provided
        if ($request->has('status') && $request->status && $request->status !== 'all') {
            $status = $request->status;
            if ($status === 'verified') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('verified', true);
                });
            } elseif ($status === 'pending') {
                $query->whereHas('teacherProfile', function ($q) {
                    $q->where('verified', false)
                      ->whereHas('verificationRequests', function($vq) {
                          // Only check the latest verification request per teacher
                          $vq->where('status', 'pending')
                             ->whereIn('id', function($subQuery) {
                                 $subQuery->selectRaw('MAX(id)')
                                          ->from('verification_requests')
                                          ->groupBy('teacher_profile_id');
                             });
                      });
                });
            } elseif ($status === 'inactive') {
                $query->where(function($q) {
                    // Teachers without profiles
                    $q->whereDoesntHave('teacherProfile')
                      // OR teachers with profiles but not verified and no pending requests
                      ->orWhereHas('teacherProfile', function($profileQuery) {
                          $profileQuery->where('verified', false)
                            ->whereDoesntHave('verificationRequests', function($vq) {
                                // Only check the latest verification request per teacher
                                $vq->where('status', 'pending')
                                   ->whereIn('id', function($subQuery) {
                                       $subQuery->selectRaw('MAX(id)')
                                                ->from('verification_requests')
                                                ->groupBy('teacher_profile_id');
                                   });
                            });
                      });
                });
            }
        }
        
        // Filter by subject if provided
        if ($request->has('subject') && $request->subject && $request->subject !== 'all') {
            $subject = $request->subject;
            $query->whereHas('teacherProfile.subjects.template', function ($q) use ($subject) {
                $q->where('name', 'like', "%{$subject}%");
            });
        }
        
        // Filter by rating if provided
        if ($request->has('rating') && $request->rating && $request->rating !== 'all') {
            $rating = $request->rating;
            $query->whereHas('teacherProfile', function ($q) use ($rating) {
                $q->where('rating', '>=', $rating);
            });
        }
        
        // Search by name or email
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        // Get teachers with pagination
        $teachers = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->through(function ($teacher) {
                // Auto-correct inconsistent verification state and ensure verification request
                if ($teacher->teacherProfile) {
                    $this->autoCorrectTeacherVerificationState($teacher);
                }
                
                // Get classes held count
                $classesHeld = TeachingSession::where('teacher_id', $teacher->id)
                    ->where('status', 'completed')
                    ->count();
                    
                // Get subjects
                $subjects = $teacher->teacherProfile ? 
                    $teacher->teacherProfile->subjects->pluck('template.name')->join(', ') : '';
                    
                // Get teacher status using optimized service
                $statusData = $this->teacherStatusService->getTeacherStatus($teacher);
                
                // Get average rating from teacher profile
                $rating = null;
                if ($teacher->teacherProfile && $teacher->teacherProfile->rating !== null) {
                    $rating = is_numeric($teacher->teacherProfile->rating) ? (float) $teacher->teacherProfile->rating : null;
                }
                
                return [
                    'id' => $teacher->id,
                    'name' => $teacher->name,
                    'email' => $teacher->email,
                    'avatar' => $teacher->avatar,
                    'subjects' => $subjects,
                    'rating' => $rating,
                    'classes_held' => $classesHeld,
                    'status' => $statusData['status'],
                    'can_approve' => $statusData['can_approve'],
                    'approval_block_reason' => $statusData['approval_block_reason'],
                    'verification_request_id' => $statusData['verification_request_id'],
                    'last_updated' => $statusData['last_updated'],
                    // Account management fields
                    'account_status' => $teacher->account_status,
                    'account_status_display' => $teacher->account_status_display,
                    'account_status_color' => $teacher->account_status_color,
                    'suspended_at' => $teacher->suspended_at,
                    'suspension_reason' => $teacher->suspension_reason,
                    'is_deleted' => $teacher->trashed(),
                ];
            });
            
        // Get all subjects for filter dropdown (from templates to avoid duplicates)
        $allSubjects = SubjectTemplates::where('is_active', true)->pluck('name');
        
        // Get rating statistics
        $averageRating = TeacherProfile::avg('rating');
        
        $ratingStats = [
            'average' => $averageRating !== null ? (float) $averageRating : null,
            'total_teachers' => TeacherProfile::count(),
            'verified_teachers' => TeacherProfile::where('verified', true)->count(),
            'unverified_teachers' => TeacherProfile::where('verified', false)->count(),
        ];
        
        return Inertia::render('admin/teachers/index', [
            'teachers' => $teachers,
            'filters' => [
                'search' => $request->search ?? '',
                'status' => $request->status ?? '',
                'subject' => $request->subject ?? '',
                'rating' => $request->rating ?? '',
            ],
            'subjects' => $allSubjects,
            'ratingStats' => $ratingStats,
        ]);
    }

    /**
     * Show the form for creating a new teacher.
     */
    public function create(): Response
    {
        // Get available subjects for selection
        $subjects = SubjectTemplates::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/teachers/create', [
            'subjects' => $subjects,
        ]);
    }

    /**
     * Store a newly created teacher in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'bio' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|string|max:20',
            'languages' => 'nullable|array',
            'teaching_type' => 'nullable|string|max:50',
            'teaching_mode' => 'nullable|string|max:50',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Create the user
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'role' => 'teacher',
                'password' => bcrypt($validated['password']),
                'email_verified_at' => now(),
            ]);

            // Create the teacher profile
            $teacherProfile = TeacherProfile::create([
                'user_id' => $user->id,
                'bio' => $validated['bio'] ?? null,
                'experience_years' => $validated['experience_years'] ?? null,
                'verified' => false,
                'languages' => $validated['languages'] ?? null,
                'teaching_type' => $validated['teaching_type'] ?? null,
                'teaching_mode' => $validated['teaching_mode'] ?? null,
            ]);

            // Initialize teacher earnings record
            TeacherEarning::create([
                'teacher_id' => $user->id,
                'wallet_balance' => 0,
                'total_earned' => 0,
                'total_withdrawn' => 0,
                'pending_payouts' => 0,
            ]);

            DB::commit();

            return redirect()->route('admin.teachers.index')
                ->with('success', 'Teacher created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to create teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified teacher.
     */
    public function show(User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        // Load teacher profile with related data
        $teacher->load([
            'teacherProfile',
            'teacherProfile.subjects.template',
            'teacherProfile.documents',
            'availabilities',
        ]);
        
        // Get real-time earnings data from FinancialService
        $earningsData = $this->financialService->getTeacherEarningsRealTime($teacher);
        
        // Get teaching sessions stats from teaching_sessions table
        $sessionsStats = [
            'total' => TeachingSession::where('teacher_id', $teacher->id)->count(),
            'completed' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->count(),
            'upcoming' => TeachingSession::where('teacher_id', $teacher->id)
                ->whereIn('status', ['scheduled'])
                ->where('session_date', '>=', now()->format('Y-m-d'))
                ->count(),
            'cancelled' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'cancelled')
                ->count(),
        ];
        
        // Get total sessions count for contact details
        $totalSessions = TeachingSession::where('teacher_id', $teacher->id)->count();
        
        // Get upcoming sessions
        $upcomingSessions = TeachingSession::where('teacher_id', $teacher->id)
            ->with(['student', 'subject'])
            ->whereIn('status', ['scheduled'])
            ->where('session_date', '>=', now()->format('Y-m-d'))
            ->orderBy('session_date')
            ->orderBy('start_time')
            ->take(5)
            ->get();
            
        // Get teacher rating and reviews from teacher_reviews table
        $teacherReviews = DB::table('teacher_reviews')
            ->where('teacher_id', $teacher->id)
            ->get();
            
        $averageRating = $teacherReviews->avg('rating');
        $reviewsCount = $teacherReviews->count();
        
        // Format document data using DocumentController methods
        $documents = $teacher->teacherProfile ? 
            \App\Http\Controllers\DocumentController::getAllTeacherDocuments($teacher->teacherProfile->id) : 
            ['id_verifications' => [], 'certificates' => [], 'resume' => null];
        
        // Add document URLs for frontend display
        if (!empty($documents['id_verifications'])) {
            $documents['id_verifications'] = array_map(function($doc) {
                $doc['documentUrl'] = \Illuminate\Support\Facades\Storage::url($doc['path'] ?? '');
                return $doc;
            }, $documents['id_verifications']);
        }
        
        if (!empty($documents['certificates'])) {
            $documents['certificates'] = array_map(function($doc) {
                $doc['documentUrl'] = \Illuminate\Support\Facades\Storage::url($doc['path'] ?? '');
                return $doc;
            }, $documents['certificates']);
        }
        
        if ($documents['resume'] && isset($documents['resume']['path'])) {
            $documents['resume']['documentUrl'] = \Illuminate\Support\Facades\Storage::url($documents['resume']['path']);
        }

        // Auto-correct inconsistent verification state and ensure verification request
        if ($teacher->teacherProfile) {
            $this->autoCorrectTeacherVerificationState($teacher);
        }

        // Get verification request status (latest)
        $verificationRequest = $teacher->teacherProfile ? 
            $teacher->teacherProfile->verificationRequests()->latest()->first() : 
            null;

        // Determine verification status based on actual documents and verification request
        $verificationStatus = $this->determineVerificationStatus($teacher->teacherProfile, $verificationRequest);

        return Inertia::render('admin/teachers/show', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'phone' => $teacher->phone,
                'avatar' => $teacher->avatar,
                'location' => $teacher->location,
                'created_at' => $teacher->created_at,
                'status' => $teacher->status_type,
                'last_active' => $teacher->last_active_at,
                // Account management fields
                'account_status' => $teacher->account_status,
                'account_status_display' => $teacher->account_status_display,
                'account_status_color' => $teacher->account_status_color,
                'suspended_at' => $teacher->suspended_at,
                'suspension_reason' => $teacher->suspension_reason,
                'is_deleted' => $teacher->trashed(),
            ],
            'profile' => $teacher->teacherProfile ? [
                'id' => $teacher->teacherProfile->id,
                'bio' => $teacher->teacherProfile->bio,
                'experience_years' => $teacher->teacherProfile->experience_years,
                'verified' => $teacher->teacherProfile->verified,
                'languages' => $teacher->teacherProfile->languages,
                'teaching_type' => $teacher->teacherProfile->teaching_type,
                'teaching_mode' => $teacher->teacherProfile->teaching_mode,
                'qualification' => $teacher->teacherProfile->qualification,
                'subjects' => $teacher->teacherProfile->subjects,
                'rating' => $averageRating,
                'reviews_count' => $reviewsCount,
            ] : null,
            'earnings' => [
                'wallet_balance' => $earningsData['wallet_balance'],
                'total_earned' => $earningsData['total_earned'],
                'total_withdrawn' => $earningsData['total_withdrawn'],
                'pending_payouts' => $earningsData['pending_payouts'],
                'recent_transactions' => $earningsData['recent_transactions'],
                'pending_payout_requests' => $earningsData['pending_payout_requests'],
                'calculated_at' => $earningsData['calculated_at'],
            ],
            'availabilities' => $teacher->availabilities->map(function($availability) {
                // Convert day_of_week to day name
                $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                $dayName = $dayNames[$availability->day_of_week] ?? 'Unknown';
                
                // Format time range
                $timeRange = $availability->start_time . ' - ' . $availability->end_time;
                
                return [
                    'id' => $availability->id,
                    'day_name' => $dayName,
                    'time_range' => $timeRange,
                    'is_active' => $availability->is_active,
                ];
            }),
            'documents' => $documents,
            'sessions_stats' => $sessionsStats,
            'upcoming_sessions' => $upcomingSessions,
            'verification_status' => $verificationStatus,
        ]);
    }

    /**
     * Determine verification status based on documents and verification request.
     */
    private function determineVerificationStatus($teacherProfile, $verificationRequest): array
    {
        if (!$teacherProfile) {
            return [
                'docs_status' => 'pending',
                'video_status' => 'not_scheduled',
            ];
        }

        // If verification request is rejected, override document status
        if ($verificationRequest && $verificationRequest->status === 'rejected') {
            return [
                'docs_status' => 'rejected',
                'video_status' => $verificationRequest->video_status ?? 'not_scheduled',
            ];
        }

        // Get document counts
        $pendingDocuments = $teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        $rejectedDocuments = $teacherProfile->documents()
            ->where('status', 'rejected')
            ->count();
            
        $verifiedDocuments = $teacherProfile->documents()
            ->where('status', 'verified')
            ->count();
            
        $totalDocuments = $teacherProfile->documents()->count();

        // Determine docs_status based on actual document statuses
        $docsStatus = 'pending';
        if ($rejectedDocuments > 0) {
            $docsStatus = 'rejected';
        } elseif ($pendingDocuments === 0 && $verifiedDocuments > 0) {
            $docsStatus = 'verified';
        } elseif ($totalDocuments === 0) {
            $docsStatus = 'pending';
        }

        // Get video status from verification request
        $videoStatus = $verificationRequest ? $verificationRequest->video_status : 'not_scheduled';

        return [
            'docs_status' => $docsStatus,
            'video_status' => $videoStatus,
        ];
    }

    /**
     * Auto-correct teacher verification state and ensure a verification request exists when needed.
     */
    private function autoCorrectTeacherVerificationState(User $teacher): void
    {
        try {
            DB::transaction(function () use ($teacher) {
                $profile = $teacher->teacherProfile;
                if (!$profile) return;

                // Latest verification request
                $vReq = $profile->verificationRequests()->latest()->first();

                // Determine docs status from actual documents
                $pendingDocuments = $profile->documents()->where('status', 'pending')->count();
                $rejectedDocuments = $profile->documents()->where('status', 'rejected')->count();
                $verifiedDocuments = $profile->documents()->where('status', 'verified')->count();
                $totalDocuments = $profile->documents()->count();
                $docsStatus = 'pending';
                if ($rejectedDocuments > 0) {
                    $docsStatus = 'rejected';
                } elseif ($pendingDocuments === 0 && $verifiedDocuments > 0) {
                    $docsStatus = 'verified';
                } elseif ($totalDocuments === 0) {
                    $docsStatus = 'pending';
                }

                $videoStatus = $vReq ? ($vReq->video_status ?? 'not_scheduled') : 'not_scheduled';

                // If verified flag is true but prerequisites not met, flip and ensure verification request
                $needsVerification = !($docsStatus === 'verified' && $videoStatus === 'passed');
                if ($profile->verified && $needsVerification) {
                    $profile->update(['verified' => false]);
                }

                if ($needsVerification) {
                    if (!$vReq) {
                        $vReq = $profile->verificationRequests()->create([
                            'status' => 'pending',
                            'docs_status' => $docsStatus,
                            'video_status' => $videoStatus,
                            'submitted_at' => now(),
                        ]);
                    } else {
                        // Keep request status consistent
                        $correctStatus = $docsStatus === 'verified' && $videoStatus === 'passed' ? 'verified' : ($vReq->status === 'rejected' ? 'rejected' : 'pending');
                        $vReq->update([
                            'status' => $correctStatus,
                            'docs_status' => $docsStatus,
                            'video_status' => $videoStatus,
                        ]);
                    }
                }
            });
        } catch (\Throwable $e) {
            \Log::warning('autoCorrectTeacherVerificationState failed', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Show the form for editing the specified teacher.
     */
    public function edit(User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        // Load teacher profile with related data
        $teacher->load([
            'teacherProfile',
            'teacherProfile.subjects.template',
            'teacherProfile.documents',
            'availabilities',
        ]);
        
        // Get real-time earnings data from FinancialService
        $earningsData = $this->financialService->getTeacherEarningsRealTime($teacher);
        
        // Get teaching sessions stats from teaching_sessions table
        $sessionsStats = [
            'total' => TeachingSession::where('teacher_id', $teacher->id)->count(),
            'completed' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->count(),
            'upcoming' => TeachingSession::where('teacher_id', $teacher->id)
                ->whereIn('status', ['scheduled'])
                ->where('session_date', '>=', now()->format('Y-m-d'))
                ->count(),
            'cancelled' => TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'cancelled')
                ->count(),
        ];
        
        // Get upcoming sessions
        $upcomingSessions = TeachingSession::where('teacher_id', $teacher->id)
            ->whereIn('status', ['scheduled'])
            ->where('session_date', '>=', now()->format('Y-m-d'))
            ->with(['student', 'subject'])
            ->orderBy('session_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->limit(5)
            ->get()
            ->map(function ($session) {
                return [
                    'id' => $session->id,
                    'session_date' => $session->session_date,
                    'start_time' => $session->start_time,
                    'end_time' => $session->end_time,
                    'status' => $session->status,
                    'student' => [
                        'id' => $session->student->id,
                        'name' => $session->student->name,
                    ],
                    'subject' => [
                        'id' => $session->subject->id,
                        'name' => $session->subject->name,
                    ],
                ];
            });

        // Get documents
        $documents = $teacher->teacherProfile?->documents ?? collect();

        // Get verification status
        $verificationStatus = null;
        if ($teacher->teacherProfile) {
            $verificationRequest = $teacher->teacherProfile->verificationRequests()->latest()->first();
            if ($verificationRequest) {
                $verificationStatus = [
                    'docs_status' => $verificationRequest->docs_status,
                    'video_status' => $verificationRequest->video_status,
                ];
            }
        }

        return Inertia::render('admin/teachers/edit', [
            'teacher' => $teacher,
            'profile' => $teacher->teacherProfile,
            'earnings' => $earningsData,
            'availabilities' => $teacher->availabilities ?? [],
            'documents' => $documents,
            'sessions_stats' => $sessionsStats,
            'upcoming_sessions' => $upcomingSessions,
            'verification_status' => $verificationStatus,
        ]);
    }

    /**
     * Update the specified teacher in storage.
     */
    public function update(Request $request, User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:20',
            'bio' => 'nullable|string|max:1000',
            'experience_years' => 'nullable|string|max:20',
            'languages' => 'nullable|array',
            'teaching_type' => 'nullable|string|max:50',
            'teaching_mode' => 'nullable|string|max:50',
        ]);

        // Start a database transaction
        DB::beginTransaction();

        try {
            // Update the user
            $teacher->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
            ]);

            // Update or create the teacher profile
            if ($teacher->teacherProfile) {
                $teacher->teacherProfile->update([
                    'bio' => $validated['bio'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'languages' => $validated['languages'] ?? null,
                    'teaching_type' => $validated['teaching_type'] ?? null,
                    'teaching_mode' => $validated['teaching_mode'] ?? null,
                ]);
            } else {
                TeacherProfile::create([
                    'user_id' => $teacher->id,
                    'bio' => $validated['bio'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'verified' => false,
                    'languages' => $validated['languages'] ?? null,
                    'teaching_type' => $validated['teaching_type'] ?? null,
                    'teaching_mode' => $validated['teaching_mode'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()->route('admin.teachers.show', $teacher->id)
                ->with('success', 'Teacher updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to update teacher: ' . $e->getMessage()]);
        }
    }

    /**
     * Approve a teacher.
     */
    public function approve(User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        if (!$teacher->teacherProfile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }

        // Ensure verification prerequisites
        $verificationRequest = $teacher->teacherProfile->verificationRequests()->latest()->first();
        if (!$verificationRequest) {
            // Create a new verification request and block approval
            $verificationRequest = $teacher->teacherProfile->verificationRequests()->create([
                'status' => 'pending',
                'docs_status' => 'pending',
                'video_status' => 'not_scheduled',
                'submitted_at' => now(),
            ]);
            $teacher->teacherProfile->update(['verified' => false]);
            return back()->withErrors(['error' => 'Teacher moved to verification. All documents and video verification must be completed.']);
        }

        // Block approval if cannot approve yet
        if (!$this->canApproveTeacher($verificationRequest)) {
            // Ensure teacher is marked unverified
            if ($teacher->teacherProfile->verified) {
                $teacher->teacherProfile->update(['verified' => false]);
            }
            return back()->withErrors(['error' => $this->getApprovalBlockReason($verificationRequest)]);
        }

        // Get current admin user ID
        $adminId = request()->user()->id;

        // Update verification request
        $verificationRequest->update([
            'status' => 'verified',
            'docs_status' => 'verified',
            'video_status' => 'passed',
            'reviewed_by' => $adminId,
            'reviewed_at' => now(),
        ]);
        
        // Update teacher profile
        $teacher->teacherProfile->update(['verified' => true]);
        
        // Log in audit trail
        $verificationRequest->auditLogs()->create([
            'status' => 'verified',
            'changed_by' => $adminId,
            'changed_at' => now(),
            'notes' => 'Teacher approved after complete verification workflow',
        ]);

        // Clear status cache
        $this->teacherStatusService->clearTeacherStatusCache($teacher);

        return back()->with('success', 'Teacher approved successfully after complete verification.');
    }

    /**
     * Reject a teacher.
     */
    public function reject(Request $request, User $teacher): RedirectResponse
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        if (!$teacher->teacherProfile) {
            return back()->withErrors(['error' => 'Teacher profile not found.']);
        }

        // Get current admin user ID
        $adminId = $request->user()->id;

        // Get or create verification request
        $verificationRequest = $teacher->teacherProfile->verificationRequests()
            ->where('status', 'pending')
            ->first();
        
        if (!$verificationRequest) {
            $verificationRequest = $teacher->teacherProfile->verificationRequests()->create([
                'status' => 'rejected',
                'docs_status' => 'rejected',
                'video_status' => 'not_scheduled',
                'submitted_at' => now(),
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        } else {
            $verificationRequest->update([
                'status' => 'rejected',
                'docs_status' => 'rejected',
                'video_status' => 'not_scheduled',
                'reviewed_by' => $adminId,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
        }
        
        // Keep teacher profile as unverified
        $teacher->teacherProfile->update(['verified' => false]);
        
        // Log in audit trail
        $verificationRequest->auditLogs()->create([
            'status' => 'rejected',
            'changed_by' => $adminId,
            'changed_at' => now(),
            'notes' => 'Teacher rejected: ' . $validated['rejection_reason'],
        ]);

        // Send notification to teacher about rejection
        $this->notifyTeacherRejected($teacher, $validated['rejection_reason']);

        // Clear status cache
        $this->teacherStatusService->clearTeacherStatusCache($teacher);

        return back()->with('success', 'Teacher rejected successfully.');
    }

    /**
     * Check if teacher can be approved.
     */
    private function canApproveTeacher($verificationRequest): bool
    {
        // Check document verification
        if ($verificationRequest->docs_status !== 'verified') {
            return false;
        }
        
        // Check video verification
        if ($verificationRequest->video_status !== 'passed') {
            return false;
        }
        
        return true;
    }

    /**
     * Get reason why teacher cannot be approved.
     */
    private function getApprovalBlockReason($verificationRequest): string
    {
        // Check if verification request is rejected
        if ($verificationRequest->status === 'rejected') {
            return 'Teacher application has been rejected.';
        }
        
        if ($verificationRequest->docs_status === 'rejected') {
            return 'Documents have been rejected.';
        }
        
        if ($verificationRequest->docs_status !== 'verified') {
            return 'All documents must be verified first.';
        }
        
        if ($verificationRequest->video_status === 'failed') {
            return 'Video verification failed.';
        }
        
        if ($verificationRequest->video_status !== 'passed') {
            return 'Video verification must be completed and passed.';
        }
        
        return 'Unknown approval block reason.';
    }

    /**
     * Send notification to teacher about rejection.
     */
    private function notifyTeacherRejected(User $teacher, string $rejectionReason): void
    {
        // Send email notification (which also creates database notification)
        try {
            $verificationRequest = $teacher->teacherProfile->verificationRequests()->latest()->first();
            if ($verificationRequest) {
                $teacher->notify(new \App\Notifications\VerificationRejectedNotification(
                    $verificationRequest,
                    $rejectionReason
                ));
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send teacher rejection email', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Download a teacher document.
     */
    public function downloadDocument(Document $document)
    {
        // Use public disk where uploads are stored
        if (!Storage::disk('public')->exists($document->path)) {
            abort(404, 'Document not found');
        }

        return Storage::disk('public')->download($document->path, $document->name);
    }

    /**
     * Upload a document for a teacher.
     */
    public function uploadDocument(Request $request)
    {
        try {
            $validated = $request->validate([
                'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB max
                'type' => 'required|string|in:id_verification,certificate,resume',
                'teacher_id' => 'required|integer|exists:users,id',
                'side' => 'nullable|string|in:front,back',
                'certificate_type' => 'nullable|string',
                'document_id' => 'nullable|integer|exists:documents,id',
            ]);

            $teacher = User::findOrFail($validated['teacher_id']);
            
            if ($teacher->role !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not a teacher'
                ], 400);
            }

            // If replacing an existing document (re-upload), delete old one first
            $existing = null;
            if (!empty($validated['document_id'])) {
                $existing = Document::find($validated['document_id']);
                if ($existing && $existing->teacher_profile_id === $teacher->teacherProfile->id) {
                    if (\Storage::disk('public')->exists($existing->path)) {
                        \Storage::disk('public')->delete($existing->path);
                    }
                    $existing->delete();
                }
            }

            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('documents/teachers/' . $teacher->id, $fileName, 'public');

            // Create document record
            $document = Document::create([
                'name' => $file->getClientOriginalName(),
                'path' => $path,
                'type' => $validated['type'],
                'teacher_profile_id' => $teacher->teacherProfile->id,
                'status' => 'pending',
                'metadata' => [
                    'side' => $validated['side'] ?? ($existing->metadata['side'] ?? null),
                    'certificate_type' => $validated['certificate_type'] ?? ($existing->metadata['certificate_type'] ?? null),
                ],
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);

            \Log::info('Document uploaded successfully', [
                'document_id' => $document->id,
                'teacher_id' => $teacher->id,
                'type' => $validated['type'],
                'uploaded_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => $document
            ]);

        } catch (\Exception $e) {
            \Log::error('Document upload failed', [
                'error' => $e->getMessage(),
                'teacher_id' => $request->teacher_id ?? null,
                'type' => $request->type ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a document.
     */
    public function deleteDocument(Document $document)
    {
        try {
            // Check if user has permission to delete this document
            if (!auth()->user()->hasRole(['admin', 'super-admin'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete the file from public disk
            if (Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->delete($document->path);
            }

            // Delete the document record
            $document->delete();

            \Log::info('Document deleted successfully', [
                'document_id' => $document->id,
                'deleted_by' => auth()->id()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Document deletion failed', [
                'error' => $e->getMessage(),
                'document_id' => $document->id
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update teacher contact details.
     */
    public function updateContact(Request $request, User $teacher)
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $teacher->id,
            'phone' => 'nullable|string|max:20',
            'location' => 'nullable|string|max:255',
        ]);

        try {
            // Update the user
            $teacher->update([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'location' => $validated['location'] ?? null,
            ]);

            return back()->with('success', 'Contact details updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Teacher contact update failed', [
                'error' => $e->getMessage(),
                'teacher_id' => $teacher->id
            ]);

            return back()->withErrors(['error' => 'Failed to update contact details: ' . $e->getMessage()]);
        }
    }

    /**
     * Update teacher about section.
     */
    public function updateAbout(Request $request, TeacherProfile $teacherProfile)
    {
        $validated = $request->validate([
            'bio' => 'nullable|string|max:1000',
        ]);

        try {
            // Update the teacher profile bio
            $teacherProfile->update([
                'bio' => $validated['bio'] ?? null,
            ]);

            return back()->with('success', 'About section updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Teacher about update failed', [
                'error' => $e->getMessage(),
                'teacher_profile_id' => $teacherProfile->id
            ]);

            return back()->withErrors(['error' => 'Failed to update about section: ' . $e->getMessage()]);
        }
    }

    /**
     * Update teacher subjects and specializations.
     */
    public function updateSubjectsSpecializations(Request $request, User $teacher)
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        $validated = $request->validate([
            'subjects' => 'nullable|string', // JSON string from FormData
            'languages' => 'nullable|string', // JSON string from FormData
            'teaching_mode' => 'nullable|string|in:full-time,part-time',
            'teaching_type' => 'nullable|string|in:online,in-person,both',
            'experience_years' => 'nullable|string|max:20',
            'qualification' => 'nullable|string|max:255',
            'availability' => 'nullable|string', // JSON string from FormData
        ]);

        // Parse JSON strings back to arrays
        $subjects = $validated['subjects'] ? json_decode($validated['subjects'], true) : [];
        $languages = $validated['languages'] ? json_decode($validated['languages'], true) : [];
        $availability = $validated['availability'] ? json_decode($validated['availability'], true) : [];

        try {
            DB::transaction(function () use ($teacher, $validated, $request, $subjects, $languages, $availability) {
                $teacherProfile = $teacher->teacherProfile;
                
                if (!$teacherProfile) {
                    throw new \Exception('Teacher profile not found');
                }

                // Update teacher profile basic info
                $teacherProfile->update([
                    'teaching_mode' => $validated['teaching_mode'] ?? null,
                    'teaching_type' => $validated['teaching_type'] ?? null,
                    'experience_years' => $validated['experience_years'] ?? null,
                    'languages' => !empty($languages) ? $languages : null, // Store as JSON array
                ]);

                // Update subjects using the existing subjects table structure
                if (!empty($subjects)) {
                    // Delete existing subjects for this teacher
                    \App\Models\Subject::where('teacher_profile_id', $teacherProfile->id)->delete();
                    
                    // Create new subjects
                    foreach ($subjects as $subjectName) {
                        \App\Models\Subject::create([
                            'teacher_profile_id' => $teacherProfile->id,
                            'name' => $subjectName,
                            'is_active' => true
                        ]);
                    }
                }

                // Update teacher availability schedule using teacher_availabilities table
                if (!empty($availability)) {
                    // Delete existing availabilities
                    \App\Models\TeacherAvailability::where('teacher_id', $teacher->id)->delete();
                    
                    // Map day names to numbers (0 = Sunday, 1 = Monday, etc.)
                    $dayMapping = [
                        'sunday' => 0,
                        'monday' => 1,
                        'tuesday' => 2,
                        'wednesday' => 3,
                        'thursday' => 4,
                        'friday' => 5,
                        'saturday' => 6,
                    ];
                    
                    foreach ($availability as $dayName => $schedule) {
                        if (isset($schedule['enabled']) && $schedule['enabled'] && 
                            !empty($schedule['from']) && !empty($schedule['to'])) {
                            
                            // Convert 12-hour format to 24-hour format for database storage
                            $startTime = $this->convertTo24HourFormat($schedule['from']);
                            $endTime = $this->convertTo24HourFormat($schedule['to']);
                            
                            \App\Models\TeacherAvailability::create([
                                'teacher_id' => $teacher->id,
                                'day_of_week' => $dayMapping[$dayName] ?? 1,
                                'start_time' => $startTime,
                                'end_time' => $endTime,
                                'is_active' => true,
                                'availability_type' => ucfirst($validated['teaching_mode'] ?? 'Part-Time'),
                            ]);
                        }
                    }
                }
            });

            return back()->with('success', 'Subjects & specializations updated successfully.');
        } catch (\Exception $e) {
            \Log::error('Teacher subjects specializations update failed', [
                'error' => $e->getMessage(),
                'teacher_id' => $teacher->id
            ]);

            return back()->withErrors(['error' => 'Failed to update subjects & specializations: ' . $e->getMessage()]);
        }
    }

    /**
     * Convert 12-hour time format to 24-hour format for database storage.
     */
    private function convertTo24HourFormat(string $time): string
    {
        try {
            // Handle formats like "09:00 AM", "05:00 PM", etc.
            $time = trim($time);
            
            // If already in 24-hour format or doesn't contain AM/PM, try to add seconds
            if (strpos($time, 'AM') === false && strpos($time, 'PM') === false) {
                if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
                    return $time . ':00';
                }
                return $time;
            }
            
            // Convert to 24-hour format using DateTime
            $dateTime = \DateTime::createFromFormat('h:i A', $time);
            if (!$dateTime) {
                $dateTime = \DateTime::createFromFormat('g:i A', $time); // Single digit hour
            }
            
            if ($dateTime) {
                return $dateTime->format('H:i:s');
            }
            
            // Fallback: try using strtotime
            $timestamp = strtotime($time);
            if ($timestamp !== false) {
                return date('H:i:s', $timestamp);
            }
            
            // If all fails, return original
            return $time;
        } catch (\Exception $e) {
            \Log::warning('Failed to convert time format', [
                'input' => $time,
                'error' => $e->getMessage()
            ]);
            return '00:00:00';
        }
    }

    /**
     * Display teacher earnings page with detailed financial information.
     */
    public function earnings(Request $request, User $teacher): Response
    {
        // Ensure the user is a teacher
        if ($teacher->role !== 'teacher') {
            abort(404, 'Teacher not found');
        }

        // Get comprehensive earnings data
        $earningsData = $this->financialService->getTeacherEarningsRealTime($teacher);

        // Get paginated transactions with filters
        $transactionsQuery = Transaction::where('teacher_id', $teacher->id)
            ->with(['session', 'createdBy']);

        // Apply filters
        if ($request->filled('type')) {
            $transactionsQuery->where('transaction_type', $request->get('type'));
        }

        if ($request->filled('status')) {
            $transactionsQuery->where('status', $request->get('status'));
        }

        if ($request->filled('date_from')) {
            $transactionsQuery->whereDate('transaction_date', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $transactionsQuery->whereDate('transaction_date', '<=', $request->get('date_to'));
        }

        $transactions = $transactionsQuery
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Get paginated payout requests with filters
        $payoutRequestsQuery = PayoutRequest::where('teacher_id', $teacher->id)
            ->with('processedBy');

        if ($request->filled('payout_status')) {
            $payoutRequestsQuery->where('status', $request->get('payout_status'));
        }

        if ($request->filled('payout_date_from')) {
            $payoutRequestsQuery->whereDate('request_date', '>=', $request->get('payout_date_from'));
        }

        if ($request->filled('payout_date_to')) {
            $payoutRequestsQuery->whereDate('request_date', '<=', $request->get('payout_date_to'));
        }

        $payoutRequests = $payoutRequestsQuery
            ->orderBy('request_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10, ['*'], 'payout_page')
            ->withQueryString();

        // Format transactions for frontend
        $formattedTransactions = $transactions->through(function ($transaction) {
            return [
                'id' => $transaction->id,
                'uuid' => $transaction->transaction_uuid,
                'date' => $transaction->transaction_date,
                'description' => $transaction->description,
                'amount' => $transaction->amount,
                'formatted_amount' => number_format($transaction->amount, 2),
                'type' => $transaction->transaction_type,
                'status' => $transaction->status,
                'session' => $transaction->session ? [
                    'id' => $transaction->session->id,
                    'subject' => $transaction->session->subject,
                    'student_name' => $transaction->session->student->name ?? null,
                ] : null,
                'created_by' => $transaction->createdBy ? [
                    'name' => $transaction->createdBy->name,
                    'role' => $transaction->createdBy->role,
                ] : null,
                'created_at' => $transaction->created_at,
            ];
        });

        // Format payout requests for frontend
        $formattedPayoutRequests = $payoutRequests->through(function ($payout) {
            return [
                'id' => $payout->id,
                'uuid' => $payout->request_uuid,
                'request_date' => $payout->request_date,
                'amount' => $payout->amount,
                'formatted_amount' => number_format($payout->amount, 2),
                'payment_method' => $payout->payment_method,
                'payment_method_display' => ucwords(str_replace('_', ' ', $payout->payment_method)),
                'status' => $payout->status,
                'status_display' => ucwords($payout->status),
                'processed_date' => $payout->processed_date,
                'processed_by' => $payout->processedBy ? [
                    'name' => $payout->processedBy->name,
                ] : null,
                'notes' => $payout->notes,
                'created_at' => $payout->created_at,
            ];
        });

        return Inertia::render('admin/teachers/earnings', [
            'teacher' => [
                'id' => $teacher->id,
                'name' => $teacher->name,
                'email' => $teacher->email,
                'avatar' => $teacher->avatar,
            ],
            'earnings' => $earningsData,
            'transactions' => $formattedTransactions,
            'payoutRequests' => $formattedPayoutRequests,
            'filters' => $request->only([
                'type', 'status', 'date_from', 'date_to',
                'payout_status', 'payout_date_from', 'payout_date_to'
            ]),
        ]);
    }
} 