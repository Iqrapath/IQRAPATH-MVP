<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherProfile;
use App\Models\User;
use App\Models\VerificationRequest;
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
    /**
     * Display a listing of verification requests.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', VerificationRequest::class);
        
        $status = $request->query('status', 'all');
        $search = $request->query('search');
        $date = $request->query('date');
        
                $verificationRequests = VerificationRequest::query()
            ->with(['teacherProfile.user', 'teacherProfile.documents']);
            
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
        
        // Add approval status to each verification request and auto-correct status
        $verificationRequests->getCollection()->transform(function ($request) {
            // Auto-correct status if it doesn't match verification data
            $correctedStatus = $this->getCorrectStatus($request);
            if ($correctedStatus !== $request->status) {
                // Actually update the database with the corrected status
                $request->update(['status' => $correctedStatus]);
                
                // Also update teacher profile verification status if needed
                if ($correctedStatus !== 'verified' && $request->teacherProfile->verified) {
                    $request->teacherProfile->update(['verified' => false]);
                }
                
                $request->status = $correctedStatus;
                $request->status_corrected = true; // Flag to show this was auto-corrected
            }
            
            $request->can_approve = $this->canApproveTeacher($request);
            $request->approval_block_reason = $request->can_approve ? null : $this->getApprovalBlockReason($request);
            return $request;
        });

        return Inertia::render('admin/verification/index', [
            'verificationRequests' => $verificationRequests,
            'filters' => [
                'status' => $status,
                'search' => $search,
                'date' => $date,
            ],
            'stats' => [
                'pending' => VerificationRequest::where('status', 'pending')->count(),
                'verified' => VerificationRequest::where('status', 'verified')->count(),
                'rejected' => VerificationRequest::where('status', 'rejected')->count(),
                'live_video' => VerificationRequest::where('status', 'live_video')->count(),
            ],
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
            'teacherProfile.subjects',
            'calls',
            'auditLogs.changer',
        ]);
        
        $teacher = $verificationRequest->teacherProfile->user;
        
        // Get real-time earnings data from FinancialService
        $financialService = app(\App\Services\FinancialService::class);
        $earningsData = $financialService->getTeacherEarningsRealTime($teacher);
        
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
                'docs_status' => $verificationRequest->docs_status,
                'video_status' => $verificationRequest->video_status,
            ],
            'latest_call' => $verificationRequest->calls()->latest()->first()?->only([
                'id', 'scheduled_at', 'platform', 'meeting_link', 'notes', 'status'
            ]),
        ]);
    }

    /**
     * Approve a verification request.
     */
    public function approve(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('approve', $verificationRequest);
        
        // Check if documents are submitted and verified
        $pendingDocuments = $verificationRequest->teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingDocuments > 0) {
            return back()->with('error', 'All documents must be verified before approving the teacher.');
        }
        
        // Check if video call is completed and passed
        $completedVideoCall = $verificationRequest->calls()
            ->where('status', 'completed')
            ->where('verification_result', 'passed')
            ->first();
            
        if (!$completedVideoCall) {
            return back()->with('error', 'Video verification call must be completed and passed before approving the teacher.');
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
                'notes' => 'Teacher verification approved after document and video verification',
            ]);
        });
        
        return redirect()->route('admin.verification.index')
            ->with('success', 'Teacher verification approved successfully after complete verification workflow.');
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
        
        return redirect()->route('admin.verification.index')
            ->with('success', 'Teacher verification rejected successfully.');
    }

    /**
     * Check if teacher can be approved.
     */
    private function canApproveTeacher($verificationRequest): bool
    {
        // Check if documents are submitted and verified
        $pendingDocuments = $verificationRequest->teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingDocuments > 0) {
            return false;
        }
        
        // Check if video call is completed and passed
        $completedVideoCall = $verificationRequest->calls()
            ->where('status', 'completed')
            ->where('verification_result', 'passed')
            ->first();
            
        if (!$completedVideoCall) {
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
        
        // Check document verification
        $pendingDocuments = $verificationRequest->teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingDocuments > 0) {
            return 'All documents must be verified first.';
        }
        
        // Check video verification
        $completedVideoCall = $verificationRequest->calls()
            ->where('status', 'completed')
            ->where('verification_result', 'passed')
            ->first();
            
        if (!$completedVideoCall) {
            return 'Video verification call must be completed and passed.';
        }
        
        return 'Unknown approval block reason.';
    }

    /**
     * Get the correct status based on verification data.
     */
    private function getCorrectStatus($verificationRequest): string
    {
        // If rejected, stay rejected
        if ($verificationRequest->status === 'rejected') {
            return 'rejected';
        }
        
        // Check if documents are verified
        $pendingDocuments = $verificationRequest->teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingDocuments > 0) {
            return 'pending';
        }
        
        // Check if video call is completed and passed
        $completedVideoCall = $verificationRequest->calls()
            ->where('status', 'completed')
            ->where('verification_result', 'passed')
            ->first();
            
        if (!$completedVideoCall) {
            return 'pending';
        }
        
        // If everything is verified, status can be verified
        return 'verified';
    }

    /**
     * Request a live video verification.
     */
    public function requestVideoVerification(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('requestVideoVerification', $verificationRequest);
        
        $validated = $request->validate([
            'scheduled_call_at' => 'required|date|after:now',
            'video_platform' => 'required|string|in:zoom,google_meet,other',
            'meeting_link' => 'nullable|string|url',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        DB::transaction(function () use ($verificationRequest, $request, $validated) {
            // Update verification request
            $verificationRequest->update([
                'status' => 'live_video',
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
                'status' => 'live_video',
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Live video verification requested',
            ]);
        });

        // Send notifications and email with ICS
        try {
            $teacher = $verificationRequest->teacherProfile->user;
            $scheduledAt = \Carbon\Carbon::parse($request->input('scheduled_call_at'));
            $platform = $request->input('video_platform');
            $meetingLink = $request->input('meeting_link');
            $duration = 30; // default

            // In-app notification (teacher)
            if (method_exists($teacher, 'receivedNotifications')) {
                $teacher->receivedNotifications()->create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'verification_call_scheduled',
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $teacher->id,
                    'channel' => 'database',
                    'level' => 'info',
                    'data' => [
                        'title' => 'Verification Call Scheduled',
                        'message' => 'Your verification call has been scheduled.',
                        'scheduled_at' => $scheduledAt->toIso8601String(),
                        'scheduled_at_human' => $scheduledAt->format('M d, Y g:i A'),
                        'platform' => $platform,
                        'platform_label' => $platform === 'zoom' ? 'Zoom' : ($platform === 'google_meet' ? 'Google Meet' : 'Other'),
                        'meeting_link' => $meetingLink,
                        'action_text' => $meetingLink ? 'Open meeting link' : null,
                        'action_url' => $meetingLink ?: null,
                    ],
                ]);
            }

            // In-app notification (admin who scheduled)
            $admin = $request->user();
            if ($admin && method_exists($admin, 'receivedNotifications')) {
                $admin->receivedNotifications()->create([
                    'id' => \Illuminate\Support\Str::uuid()->toString(),
                    'type' => 'verification_call_scheduled',
                    'notifiable_type' => \App\Models\User::class,
                    'notifiable_id' => $admin->id,
                    'channel' => 'database',
                    'level' => 'info',
                    'data' => [
                        'title' => 'Verification Call Scheduled',
                        'message' => 'You scheduled a verification call for ' . ($teacher->name ?? 'teacher') . '.',
                        'scheduled_at' => $scheduledAt->toIso8601String(),
                        'scheduled_at_human' => $scheduledAt->format('M d, Y g:i A'),
                        'platform' => $platform,
                        'platform_label' => $platform === 'zoom' ? 'Zoom' : ($platform === 'google_meet' ? 'Google Meet' : 'Other'),
                        'meeting_link' => $meetingLink,
                        'action_text' => 'View request',
                        'action_url' => route('admin.verification.show', $verificationRequest->id),
                    ],
                ]);
            }

            // Email with ICS to teacher and admin
            $ics = $this->buildIcsInvite('Verification Call', $scheduledAt, $duration, 'Platform: ' . $platform . ( $meetingLink ? "\nLink: $meetingLink" : ''), $meetingLink);
            foreach (array_filter([$teacher->email, $request->user()->email]) as $to) {
                Mail::send([], [], function ($message) use ($to, $ics) {
                    $message->to($to)
                        ->subject('Verification Call Scheduled')
                        ->setBody('Your verification call has been scheduled. See attached calendar invite.', 'text/plain')
                        ->attachData($ics, 'verification-call.ics', ['mime' => 'text/calendar; charset=utf-8']);
                });
            }
        } catch (\Throwable $e) {
            // swallow notification errors to not block scheduling
        }
        
        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Live video verification scheduled successfully.');
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
            if ($validated['video_platform'] === 'zoom') {
                $zoom = app(\App\Services\ZoomService::class);
                $startAt = \Carbon\Carbon::parse($validated['scheduled_call_at']);
                $topic = 'Teacher Verification Call: ' . ($verificationRequest->teacherProfile->user->name ?? 'Teacher');
                $meeting = $zoom->createAdhocMeeting($topic, $startAt, $validated['duration_minutes'] ?? 30);
                return response()->json([
                    'success' => true,
                    'meeting_link' => $meeting['join_url'] ?? '',
                    'provider' => 'zoom',
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
        $uid = \Illuminate\Support\Str::uuid()->toString() . '@iqrapath';
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//IqraPath//Verification Call//EN',
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
                    'verification_notes' => $validated['verification_notes'],
                    'verified_by' => $request->user()->id,
                    'verified_at' => now(),
                ]);
            }
            
            // Update verification request video status
            $verificationRequest->update([
                'video_status' => $validated['verification_result'] === 'passed' ? 'passed' : 'failed',
            ]);
            
            // Create audit log
            $verificationRequest->auditLogs()->create([
                'status' => $verificationRequest->status,
                'changed_by' => $request->user()->id,
                'changed_at' => now(),
                'notes' => 'Video verification ' . $validated['verification_result'] . ': ' . ($validated['verification_notes'] ?? 'No notes'),
            ]);
        });
        
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
} 