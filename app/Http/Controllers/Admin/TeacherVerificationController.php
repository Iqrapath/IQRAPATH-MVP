<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PayoutRequest;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\VerificationRequest;
use App\Services\UnifiedWalletService;
use App\Services\NotificationService;
use App\Services\SettingsService;
use App\Services\TeacherStatusService;
use App\Notifications\VerificationCallScheduledNotification;
use App\Notifications\VerificationApprovedNotification;
use App\Notifications\VerificationRejectedNotification;
use App\Notifications\VerificationCallStartedNotification;
use App\Notifications\VerificationCallCompletedNotification;
use App\Notifications\DocumentUploadedNotification;
use App\Notifications\DocumentVerifiedNotification;
use App\Notifications\DocumentRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class TeacherVerificationController extends Controller
{
    public function __construct(
        private SettingsService $settingsService,
        private TeacherStatusService $teacherStatusService
    ) {}
    /**
     * Display a listing of verification requests.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', VerificationRequest::class);
        
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $date = $request->query('date');
        
            // Get only the latest verification request per teacher to avoid duplicates
        $latestRequestIds = VerificationRequest::selectRaw('MAX(id) as id')
            ->groupBy('teacher_profile_id')
            ->pluck('id');
            
        $verificationRequests = VerificationRequest::query()
            ->with(['teacherProfile.user', 'teacherProfile.documents', 'calls'])
            ->whereIn('id', $latestRequestIds);
            
        if ($status && $status !== 'all') {
            $verificationRequests->where('status', $status);
        }
        
        if ($search) {
            $verificationRequests->whereHas('teacherProfile.user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }
        
        if ($date) {
            $verificationRequests->whereDate('created_at', $date);
        }
        
        $verificationRequests = $verificationRequests
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        // Add approval status to each verification request
        $verificationRequests->getCollection()->transform(function ($request) {
            // Get the teacher user for unified status calculation
            $teacher = $request->teacherProfile->user;
            
            // Use unified status service for consistent status calculation
            $statusData = $this->teacherStatusService->getTeacherStatus($teacher);
            
            // Map unified status to verification request display format
            $displayStatus = match($statusData['status']) {
                'Approved' => 'verified',
                'Pending' => 'pending', 
                'Inactive' => 'rejected',
                default => 'pending'
            };
            
            // Override the raw status with unified calculation
            $request->status = $displayStatus;
            $request->can_approve = $statusData['can_approve'];
            $request->approval_block_reason = $statusData['approval_block_reason'];
            
            // Add calculated docs status based on actual document statuses
            $request->calculated_docs_status = $this->calculateDocsStatus($request);
            
            // No need for status suggestion since we're using unified service
            // The unified service already handles all the logic correctly
            
            return $request;
        });

        // Calculate correct stats based on unified status logic
        $correctStats = [
            'pending' => 0,
            'verified' => 0,
            'rejected' => 0,
            'live_video' => 0,
        ];

        foreach ($verificationRequests->getCollection() as $request) {
            // Use the unified status that was set above
            $correctStats[$request->status]++;
        }

        return Inertia::render('admin/verification/index', [
            'verificationRequests' => $verificationRequests,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'date' => $date,
            ],
            'stats' => $correctStats,
        ]);
    }

    /**
     * Display the specified verification request.
     */
    public function show(VerificationRequest $verificationRequest): Response
    {
        Gate::authorize('view', $verificationRequest);
        
        $verificationRequest->load([
            'teacherProfile.user',
            'teacherProfile.documents',
            'teacherProfile.subjects.template',
            'calls',
            'auditLogs.changer',
        ]);
        
        // Defensive check for required relationships
        if (!$verificationRequest->teacherProfile) {
            abort(404, 'Teacher profile not found for this verification request.');
        }
        
        if (!$verificationRequest->teacherProfile->user) {
            abort(404, 'Teacher user not found for this verification request.');
        }
        
        $teacher = $verificationRequest->teacherProfile->user;
        
        // Get real-time earnings data from UnifiedWalletService
        $walletService = app(\App\Services\UnifiedWalletService::class);
        
        // Get teacher wallet data (new system) with error handling
        try {
            $teacherWallet = $walletService->getTeacherWallet($teacher);
        } catch (\Exception $e) {
            // Log error and fallback to legacy system
            \Log::warning('Failed to get teacher wallet during verification', [
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage()
            ]);
            
            // Fallback to legacy system immediately
            $financialService = app(\App\Services\FinancialService::class);
            $earningsData = $financialService->getTeacherEarningsRealTime($teacher);
            
            // Ensure we have the required fields
            $earningsData['pending_payout_requests'] = PayoutRequest::where('teacher_id', $teacher->id)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Skip to session stats since we have earnings data
            goto sessionStats;
        }
        
        // Get payout requests for this teacher
        $pendingPayoutRequests = PayoutRequest::where('teacher_id', $teacher->id)
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();
        
        $earningsData = [
            'wallet_balance' => $teacherWallet->balance,
            'total_earned' => $teacherWallet->total_earned,
            'total_withdrawn' => $teacherWallet->total_withdrawn,
            'pending_payouts' => $teacherWallet->pending_payouts,
            'recent_transactions' => $teacherWallet->unifiedTransactions()
                ->orderBy('transaction_date', 'desc')
                ->take(10)
                ->get(),
            'pending_payout_requests' => $pendingPayoutRequests,
            'calculated_at' => now()->toDateTimeString(),
        ];
        
        // Fallback to legacy FinancialService if wallet doesn't exist or is empty
        if (!$teacherWallet->wasRecentlyCreated && $teacherWallet->total_earned == 0) {
            $financialService = app(\App\Services\FinancialService::class);
            $legacyData = $financialService->getTeacherEarningsRealTime($teacher);
            
            // Merge legacy data if available, but keep our payout requests
            if (isset($legacyData['total_earned']) && $legacyData['total_earned'] > 0) {
                $earningsData = array_merge($legacyData, [
                    'pending_payout_requests' => $pendingPayoutRequests,
                    'calculated_at' => now()->toDateTimeString(),
                ]);
            }
        }
        
        sessionStats:
        // Get teaching sessions stats from teaching_sessions table
        $sessionsStats = [
            'total' => \App\Models\TeachingSession::where('teacher_id', $teacher->id)->count(),
            'completed' => \App\Models\TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'completed')
                ->count(),
            'upcoming' => \App\Models\TeachingSession::where('teacher_id', $teacher->id)
                ->whereIn('status', ['scheduled'])
                ->where('session_date', '>=', now()->format('Y-m-d'))
                ->count(),
            'cancelled' => \App\Models\TeachingSession::where('teacher_id', $teacher->id)
                ->where('status', 'cancelled')
                ->count(),
        ];
        
        // Get upcoming sessions
        $upcomingSessions = \App\Models\TeachingSession::where('teacher_id', $teacher->id)
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
        
        // Format document data
        $documents = $verificationRequest->teacherProfile->documents->map(function ($document) {
            return [
                'id' => $document->id,
                'type' => $document->type,
                'name' => $document->name,
                'status' => $document->status,
                'url' => Storage::url($document->path),
                'verified_at' => $document->verified_at,
                'verified_by' => $document->verified_by,
            ];
        });

        // Group documents to match teacher documents section component API
        $allDocuments = $verificationRequest->teacherProfile->documents;

        $idVerifications = $allDocuments
            ->where('type', 'id_verification')
            ->values()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'status' => $doc->status,
                    'metadata' => $doc->metadata,
                    'documentUrl' => Storage::url($doc->path),
                ];
            });

        $certificates = $allDocuments
            ->where('type', 'certificate')
            ->values()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'name' => $doc->name,
                    'status' => $doc->status,
                    'metadata' => $doc->metadata,
                    'documentUrl' => Storage::url($doc->path),
                ];
            });

        $resumeDoc = $allDocuments->where('type', 'resume')->first();
        $resume = $resumeDoc ? [
            'id' => $resumeDoc->id,
            'name' => $resumeDoc->name,
            'status' => $resumeDoc->status,
            'metadata' => $resumeDoc->metadata,
            'documentUrl' => Storage::url($resumeDoc->path),
        ] : null;
        
        return Inertia::render('admin/verification/show', [
            'verificationRequest' => $verificationRequest,
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
            ],
            'profile' => $verificationRequest->teacherProfile ? [
                'id' => $verificationRequest->teacherProfile->id,
                'bio' => $verificationRequest->teacherProfile->bio,
                'experience_years' => $verificationRequest->teacherProfile->experience_years,
                'verified' => $verificationRequest->teacherProfile->verified,
                'languages' => $verificationRequest->teacherProfile->languages,
                'teaching_type' => $verificationRequest->teacherProfile->teaching_type,
                'teaching_mode' => $verificationRequest->teacherProfile->teaching_mode,
                'subjects' => $verificationRequest->teacherProfile->subjects,
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
            'availabilities' => $teacher->availabilities ? $teacher->availabilities->map(function($availability) {
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
            }) : [],
            'documents' => $documents,
            'documents_grouped' => [
                'id_verifications' => $idVerifications,
                'certificates' => $certificates,
                'resume' => $resume,
            ],
            'sessions_stats' => $sessionsStats,
            'upcoming_sessions' => $upcomingSessions,
            'verification_status' => [
                'docs_status' => $this->calculateDocsStatus($verificationRequest),
                'video_status' => $verificationRequest->video_status,
            ],
            'latest_call' => $verificationRequest->calls()->latest()->first()?->only([
                'id', 'scheduled_at', 'platform', 'meeting_link', 'notes', 'status'
            ]),
        ]);
    }

    /**
     * Approve a verification request based on current requirements.
     */
    public function approve(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('approve', $verificationRequest);
        
        $config = $this->settingsService->getTeacherVerificationSettings();
        
        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();
                
            if (!$completedVideoCall) {
                return back()->with('error', 'Video verification call must be completed and passed before approving the teacher.');
            }
        }
        
        // Check document verification if required
        if ($config['require_documents']) {
            $documents = $verificationRequest->teacherProfile->documents;
            $pendingDocuments = $documents->where('status', 'pending')->count();
            $rejectedDocuments = $documents->where('status', 'rejected')->count();
            
            if ($documents->count() === 0) {
                return back()->with('error', 'Teacher must submit at least one document before approval.');
            }
            
            if ($pendingDocuments > 0) {
                return back()->with('error', "Cannot approve: {$pendingDocuments} document(s) still pending review.");
            }
            
            if ($rejectedDocuments > 0) {
                return back()->with('error', "Cannot approve: {$rejectedDocuments} document(s) have been rejected and need resubmission.");
            }
        }
        
        DB::transaction(function () use ($verificationRequest, $request) {
            // Update verification request
            $verificationRequest->update([
                'status' => 'verified',
                'video_status' => 'passed',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);
            
            // Mark teacher as verified
            $verificationRequest->teacherProfile->update([
                'verified' => true,
            ]);
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => 'verified',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Teacher verification approved after ' . $this->getVerificationMethodDescription(),
            ]);
        });

        // Send notification to teacher
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $teacher->notify(new VerificationApprovedNotification($verificationRequest));
        } catch (\Throwable $e) {
            // Log error but don't block the approval
            \Log::error('Failed to send verification approved notification', [
                'verification_request_id' => $verificationRequest->id,
                'teacher_id' => $teacher->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
        
        // Clear status cache for real-time updates
        $this->teacherStatusService->clearTeacherStatusCache($teacher, 'approved');
        
        // Send admin notification
        try {
            $admin = $request->user();
            $admin->notify(new VerificationApprovedNotification($verificationRequest));
        } catch (\Throwable $e) {
            \Log::error('Failed to send admin verification approved notification', [
                'verification_request_id' => $verificationRequest->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('admin.verification.index')
            ->with('success', 'Teacher verification approved successfully after ' . $this->getVerificationMethodDescription() . '.');
    }

    /**
     * Get a description of the current verification requirements.
     */
    private function getVerificationMethodDescription(): string
    {
        $config = $this->settingsService->getTeacherVerificationSettings();
        $methods = [];
        
        if ($config['require_video']) {
            $methods[] = 'video verification';
        }
        
        if ($config['require_documents']) {
            $methods[] = 'document verification';
        }
        
        if (empty($methods)) {
            return 'manual review';
        }
        
        return implode(' and ', $methods);
    }

    /**
     * Reject a verification request.
     */
    public function reject(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('reject', $verificationRequest);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);
        
        DB::transaction(function () use ($verificationRequest, $request, $validated) {
            // Update verification request
            $verificationRequest->update([
                'status' => 'rejected',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
                'rejection_reason' => $validated['rejection_reason'],
            ]);
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => 'rejected',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Teacher verification rejected: ' . $validated['rejection_reason'],
            ]);
        });

        // Send notification to teacher
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $teacher->notify(new VerificationRejectedNotification(
                $verificationRequest,
                $validated['rejection_reason']
            ));
        } catch (\Throwable $e) {
            // Log error but don't block the rejection
            \Log::error('Failed to send verification rejected notification', [
                'verification_request_id' => $verificationRequest->id,
                'teacher_id' => $teacher->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
        
        // Clear status cache for real-time updates
        $this->teacherStatusService->clearTeacherStatusCache($teacher, 'rejected');
        
        // Send admin notification
        try {
            $admin = $request->user();
            $admin->notify(new VerificationRejectedNotification(
                $verificationRequest,
                $validated['rejection_reason']
            ));
        } catch (\Throwable $e) {
            \Log::error('Failed to send admin verification rejected notification', [
                'verification_request_id' => $verificationRequest->id,
                'admin_id' => $request->user()->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('admin.verification.index')
            ->with('success', 'Teacher verification rejected successfully.');
    }

    /**
     * Check if teacher can be approved based on current verification requirements.
     */
    private function canApproveTeacher($verificationRequest): bool
    {
        $config = $this->settingsService->getTeacherVerificationSettings();
        
        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();
                
            if (!$completedVideoCall) {
                return false;
            }
        }
        
        // Check document verification if required
        if ($config['require_documents']) {
            $documents = $verificationRequest->teacherProfile->documents;
            $pendingDocuments = $documents->where('status', 'pending')->count();
            $rejectedDocuments = $documents->where('status', 'rejected')->count();
            
            if ($documents->count() === 0) {
                return false; // Must have at least one document
            }
            
            if ($pendingDocuments > 0) {
                return false; // All documents must be verified
            }
            
            if ($rejectedDocuments > 0) {
                return false; // No rejected documents allowed
            }
        }
        
        return true;
    }

    /**
     * Get reason why teacher cannot be approved based on current requirements.
     */
    private function getApprovalBlockReason($verificationRequest): string
    {
        $config = $this->settingsService->getTeacherVerificationSettings();
        
        // Check if verification request is rejected
        if ($verificationRequest->status === 'rejected') {
            return 'Teacher application has been rejected.';
        }
        
        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();
                
            if (!$completedVideoCall) {
                return 'Video verification call must be completed and passed.';
            }
        }
        
        // Check document verification if required
        if ($config['require_documents']) {
            $documents = $verificationRequest->teacherProfile->documents;
            $pendingDocuments = $documents->where('status', 'pending')->count();
            $rejectedDocuments = $documents->where('status', 'rejected')->count();
            
            if ($documents->count() === 0) {
                return 'Teacher must submit at least one document before approval.';
            }
            
            if ($pendingDocuments > 0) {
                return "Cannot approve: {$pendingDocuments} document(s) still pending review.";
            }
            
            if ($rejectedDocuments > 0) {
                return "Cannot approve: {$rejectedDocuments} document(s) have been rejected and need resubmission.";
            }
        }
        
        return 'Unknown approval block reason.';
    }

    /**
     * Get the correct status based on verification data and current requirements.
     */
    private function getCorrectStatus($verificationRequest): string
    {
        $config = $this->settingsService->getTeacherVerificationSettings();
        
        // If rejected, stay rejected
        if ($verificationRequest->status === 'rejected') {
            return 'rejected';
        }
        
        // If currently in live video session, preserve that status
        if ($verificationRequest->status === 'live_video') {
            return 'live_video';
        }
        
        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();
                
            if (!$completedVideoCall) {
                return 'pending';
            }
        }
        
        // Check document verification if required
        if ($config['require_documents']) {
            $totalDocuments = $verificationRequest->teacherProfile->documents()->count();
            $verifiedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'verified')
                ->count();
            $rejectedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'rejected')
                ->count();
                
            // If no documents submitted, status is pending
            if ($totalDocuments === 0) {
                return 'pending';
            }
            
            // If any documents are rejected, status is rejected
            if ($rejectedDocuments > 0) {
                return 'rejected';
            }
            
            // If not all documents are verified, status is pending
            if ($verifiedDocuments < $totalDocuments) {
                return 'pending';
            }
        }
        
        // If all required verifications are complete, status can be verified
        return 'verified';
    }

    /**
     * Request a live video verification.
     */
    public function requestVideoVerification(Request $request, VerificationRequest $verificationRequest): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        Gate::authorize('requestVideoVerification', $verificationRequest);
        
        $validated = $request->validate([
            'scheduled_call_at' => 'required|date|after:now',
            'video_platform' => 'required|string|in:zoom,google_meet,other',
            'meeting_link' => 'nullable|string|url',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        DB::transaction(function () use ($verificationRequest, $request, $validated) {
            // Update verification request - keep status as 'pending' until live verification starts
            $verificationRequest->update([
                'status' => 'pending',
                'video_status' => 'scheduled',
                'scheduled_call_at' => $validated['scheduled_call_at'],
                'video_platform' => $validated['video_platform'],
                'meeting_link' => $validated['meeting_link'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            
            // Create a verification call
            $verificationRequest->calls()->create([
                'scheduled_at' => $validated['scheduled_call_at'],
                'platform' => $validated['video_platform'],
                'meeting_link' => $validated['meeting_link'] ?? '',
                'notes' => $validated['notes'] ?? null,
                'status' => 'scheduled',
                'created_by' => $request->user()->id,
            ]);
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => 'pending',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Live video verification scheduled',
            ]);
        });

        // Send notifications using NotificationService
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $admin = $request->user();
            $scheduledAt = $request->input('scheduled_call_at');
            $platform = $request->input('video_platform');
            $meetingLink = $request->input('meeting_link');
            $notes = $request->input('notes');
            
            $scheduledDate = \Carbon\Carbon::parse($scheduledAt);
            $platformLabel = match ($platform) {
                'zoom' => 'Zoom',
                'google_meet' => 'Google Meet',
                default => ucfirst($platform),
            };

            // Send notification to teacher
            $teacher->notify(new VerificationCallScheduledNotification(
                $verificationRequest,
                $scheduledAt,
                $platform,
                $meetingLink,
                $notes,
                true // isForTeacher
            ));

            // Send notification to admin
            $admin->notify(new VerificationCallScheduledNotification(
                $verificationRequest,
                $scheduledAt,
                $platform,
                $meetingLink,
                $notes,
                false // isForTeacher
            ));

            // Email notifications are handled by the VerificationCallScheduledNotification class

        } catch (\Throwable $e) {
            // Log error but don't block scheduling
            \Log::error('Failed to send verification call scheduled notifications', [
                'verification_request_id' => $verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
        
        // Check if this is an AJAX/Inertia request from the modal
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Live video verification scheduled successfully.',
                'verification_request_id' => $verificationRequest->id
            ]);
        }
        
        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Live video verification scheduled successfully.');
    }

    /**
     * Mark the scheduled verification call as live (in progress).
     */
    public function startVideoVerification(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('requestVideoVerification', $verificationRequest);

        DB::transaction(function () use ($verificationRequest, $request) {
            // Update request to live - keep video_status as 'scheduled' since we're just starting
            $verificationRequest->update([
                'status' => 'live_video',
                // Don't change video_status - it should remain 'scheduled' until actually completed
            ]);

            // Audit log
            $verificationRequest->auditLogs()->create([
                'status' => 'live_video',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Verification call started (live)',
            ]);
        });

        // Send notifications using new notification classes
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $admin = $request->user();

            // Send notification to teacher
            $teacher->notify(new VerificationCallStartedNotification($verificationRequest));

            // Send notification to admin
            $admin->notify(new VerificationCallStartedNotification($verificationRequest));

        } catch (\Throwable $e) {
            // Log error but don't block the process
            \Log::error('Failed to send verification call started notifications', [
                'verification_request_id' => $verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }

        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Verification call marked as live.');
    }

    /**
     * Generate provider meeting link (admin-only JSON).
     */
    public function generateMeetingLink(Request $request, VerificationRequest $verificationRequest)
    {
        Gate::authorize('requestVideoVerification', $verificationRequest);
        $validated = $request->validate([
            'scheduled_call_at' => 'required|date|after:now',
            'video_platform' => 'required|string|in:zoom,google_meet,other',
            'duration_minutes' => 'nullable|integer|min:15|max:180',
        ]);

        try {
            $startAt = \Carbon\Carbon::parse($validated['scheduled_call_at']);
            $topic = 'Teacher Verification Call: ' . ($verificationRequest->teacherProfile->user->name ?? 'Teacher');
            $durationMinutes = $validated['duration_minutes'] ?? 30;
            
            if ($validated['video_platform'] === 'zoom') {
                $zoom = app(\App\Services\ZoomService::class);
                $meeting = $zoom->createAdhocMeeting($topic, $startAt, $durationMinutes);
                return response()->json([
                    'success' => true,
                    'meeting_link' => $meeting['join_url'] ?? '',
                    'provider' => 'zoom',
                    'meta' => $meeting,
                ]);
            }
            
            if ($validated['video_platform'] === 'google_meet') {
                // Check if Google Meet credentials are configured
                if (!config('services.google_meet.client_id') || !config('services.google_meet.client_secret') || !config('services.google_meet.refresh_token')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Google Meet integration is not configured. Please contact the administrator or use a different platform.',
                    ], 422);
                }
                
                $googleMeet = app(\App\Services\GoogleMeetService::class);
                $organizerEmail = $verificationRequest->teacherProfile->user->email ?? null;
                $meeting = $googleMeet->createAdhocMeeting($topic, $startAt, $durationMinutes, $organizerEmail);
                return response()->json([
                    'success' => true,
                    'meeting_link' => $meeting['meet_link'] ?? '',
                    'provider' => 'google_meet',
                    'meta' => $meeting,
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Automatic generation not available for this platform. Enter link manually.'
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build a simple ICS invite string.
     */
    private function buildIcsInvite(string $summary, \Carbon\Carbon $start, int $durationMinutes, string $description = '', ?string $url = null): string
    {
        $dtStart = $start->copy()->utc()->format('Ymd\THis\Z');
        $dtEnd = $start->copy()->addMinutes($durationMinutes)->utc()->format('Ymd\THis\Z');
        $uid = \Illuminate\Support\Str::uuid()->toString() . '@iqraquest';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//IqraQuest//Verification Call//EN',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:' . $uid,
            'DTSTAMP:' . now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:' . $dtStart,
            'DTEND:' . $dtEnd,
            'SUMMARY:' . addcslashes($summary, ",;\\"),
            'DESCRIPTION:' . addcslashes($description ?? '', ",;\\"),
        ];
        if ($url) {
            $lines[] = 'URL:' . $url;
        }
        $lines[] = 'END:VEVENT';
        $lines[] = 'END:VCALENDAR';
        return implode("\r\n", $lines);
    }

    /**
     * Complete a video verification.
     */
    public function completeVideoVerification(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('completeVideoVerification', $verificationRequest);
        
        $validated = $request->validate([
            'verification_result' => 'required|string|in:passed,failed',
            'verification_notes' => 'nullable|string|max:1000',
        ]);
        
        DB::transaction(function () use ($verificationRequest, $request, $validated) {
            // Update the verification call
            $call = $verificationRequest->calls()->latest()->first();
            if ($call) {
                $call->update([
                    'status' => 'completed',
                    'verification_result' => $validated['verification_result'],
                    'verification_notes' => $validated['verification_notes'] ?? null,
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);
            }
            
            // Update verification request video status and status
            $videoPassed = $validated['verification_result'] === 'passed';
            $verificationRequest->update([
                'video_status' => $videoPassed ? 'passed' : 'failed',
                'status' => 'pending', // Reset status back to pending after live video
            ]);

            // Auto-approve if video verification passed and auto-approval is enabled
            if ($videoPassed && $this->settingsService->getTeacherVerificationSettings()['auto_approve_after_video']) {
                // Check if all other requirements are met
                $canApprove = $this->canApproveTeacher($verificationRequest);
                
                if ($canApprove) {
                    $verificationRequest->update([
                        'status' => 'verified',
                        'reviewed_by' => $request->user()->id,
                        'reviewed_at' => now(),
                    ]);
                    $verificationRequest->teacherProfile->update(['verified' => true]);
                }
            }
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => $verificationRequest->status,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Video verification ' . $validated['verification_result'] . ': ' . ($validated['verification_notes'] ?? 'No notes'),
            ]);
        });

        // Send notifications using new notification classes
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $result = $validated['verification_result'];
            $notes = $validated['verification_notes'] ?? null;

            // Send notification to teacher
            $teacher->notify(new VerificationCallCompletedNotification(
                $verificationRequest,
                $result,
                $notes
            ));

        } catch (\Throwable $e) {
            // Log error but don't block the process
            \Log::error('Failed to send verification call completed notifications', [
                'verification_request_id' => $verificationRequest->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('admin.verification.index')
            ->with('success', 'Video verification marked as ' . $validated['verification_result'] . ' successfully.');
    }

    /**
     * Get verification summary for a teacher.
     */
    public function getVerificationSummary(VerificationRequest $verificationRequest): array
    {
        $documents = $verificationRequest->teacherProfile->documents;
        $videoCalls = $verificationRequest->calls;
        
        return [
            'documents' => [
                'total' => $documents->count(),
                'verified' => $documents->where('status', 'verified')->count(),
                'pending' => $documents->where('status', 'pending')->count(),
                'rejected' => $documents->where('status', 'rejected')->count(),
            ],
            'video_verification' => [
                'scheduled' => $videoCalls->where('status', 'scheduled')->count(),
                'completed' => $videoCalls->where('status', 'completed')->count(),
                'passed' => $videoCalls->where('status', 'completed')->where('verification_result', 'passed')->count(),
                'failed' => $videoCalls->where('status', 'completed')->where('verification_result', 'failed')->count(),
            ],
            'can_approve' => $this->canApproveTeacher($verificationRequest),
            'approval_block_reason' => $this->getApprovalBlockReason($verificationRequest),
        ];
    }

    /**
     * Calculate the actual docs status based on individual document statuses
     */
    private function calculateDocsStatus(VerificationRequest $verificationRequest): string
    {
        $documents = $verificationRequest->teacherProfile->documents;
        
        // If no documents submitted, status is pending
        if ($documents->count() === 0) {
            return 'pending';
        }
        
        $totalDocs = $documents->count();
        $verifiedDocs = $documents->where('status', 'verified')->count();
        $rejectedDocs = $documents->where('status', 'rejected')->count();
        
        // If any document is rejected, overall status is rejected
        if ($rejectedDocs > 0) {
            return 'rejected';
        }
        
        // If all documents are verified, status is verified
        if ($verifiedDocs === $totalDocs) {
            return 'verified';
        }
        
        // Otherwise, some documents are still pending
        return 'pending';
    }

    /**
     * Verify a document.
     */
    public function verifyDocument(Request $request, $documentId): RedirectResponse
    {
        $document = \App\Models\TeacherDocument::findOrFail($documentId);
        $verificationRequest = $document->teacherProfile->verificationRequest;
        
        Gate::authorize('verifyDocument', $verificationRequest);
        
        $validated = $request->validate([
            'verification_notes' => 'nullable|string|max:1000',
        ]);
        
        DB::transaction(function () use ($document, $request, $validated) {
            $document->update([
                'status' => 'verified',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_notes' => $validated['verification_notes'] ?? null,
            ]);
            
            // Create audit log
            $verificationRequest = $document->teacherProfile->verificationRequest;
            $verificationRequest->auditLogs()->create([
                'status' => $verificationRequest->status,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Document verified: ' . $document->name,
            ]);
        });

        // Send notifications
        try {
            $teacher = $document->teacherProfile->user;
            $admin = $request->user();
            
            // Notify teacher
            $teacher->notify(new DocumentVerifiedNotification($document));
            
            // Notify admin
            $admin->notify(new DocumentVerifiedNotification($document));
            
        } catch (\Throwable $e) {
            \Log::error('Failed to send document verified notifications', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Document verified successfully.');
    }

    /**
     * Reject a document.
     */
    public function rejectDocument(Request $request, $documentId): RedirectResponse
    {
        $document = \App\Models\TeacherDocument::findOrFail($documentId);
        $verificationRequest = $document->teacherProfile->verificationRequest;
        
        Gate::authorize('rejectDocument', $verificationRequest);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);
        
        DB::transaction(function () use ($document, $request, $validated) {
            $document->update([
                'status' => 'rejected',
                'verified_by' => $request->user()->id,
                'verified_at' => now(),
                'verification_notes' => $validated['rejection_reason'],
            ]);
            
            // Create audit log
            $verificationRequest = $document->teacherProfile->verificationRequest;
            $verificationRequest->auditLogs()->create([
                'status' => $verificationRequest->status,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Document rejected: ' . $document->name . ' - ' . $validated['rejection_reason'],
            ]);
        });

        // Send notifications
        try {
            $teacher = $document->teacherProfile->user;
            $admin = $request->user();
            
            // Notify teacher
            $teacher->notify(new DocumentRejectedNotification($document, $validated['rejection_reason']));
            
            // Notify admin
            $admin->notify(new DocumentRejectedNotification($document, $validated['rejection_reason']));
            
        } catch (\Throwable $e) {
            \Log::error('Failed to send document rejected notifications', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
        
        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Document rejected successfully.');
    }

    /**
     * Handle document upload (when teacher uploads a document).
     */
    public function handleDocumentUpload(\App\Models\TeacherDocument $document): void
    {
        try {
            $teacher = $document->teacherProfile->user;
            $verificationRequest = $document->teacherProfile->verificationRequest;
            
            // Notify teacher
            $teacher->notify(new DocumentUploadedNotification($document));
            
            // Notify all admins
            $admins = User::whereIn('role', ['admin', 'super-admin'])->get();
            foreach ($admins as $admin) {
                $admin->notify(new DocumentUploadedNotification($document));
            }
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => $verificationRequest->status,
                'changed_by' => $teacher->id,
                'changed_at' => now(),
                'notes' => 'Document uploaded: ' . $document->name,
            ]);
            
        } catch (\Throwable $e) {
            \Log::error('Failed to send document uploaded notifications', [
                'document_id' => $document->id,
                'error' => $e->getMessage()
            ]);
        }
    }
} 