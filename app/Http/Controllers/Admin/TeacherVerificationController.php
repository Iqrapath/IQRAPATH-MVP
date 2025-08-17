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
        
        return Inertia::render('admin/verification/show', [
            'verificationRequest' => $verificationRequest,
            'teacher' => $verificationRequest->teacherProfile->user,
            'documents' => $verificationRequest->teacherProfile->documents->map(function ($document) {
                return [
                    'id' => $document->id,
                    'type' => $document->type,
                    'name' => $document->name,
                    'status' => $document->status,
                    'url' => Storage::url($document->path),
                    'verified_at' => $document->verified_at,
                    'verified_by' => $document->verified_by,
                ];
            }),
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
                'meeting_link' => $validated['meeting_link'],
                'notes' => $validated['notes'],
            ]);
            
            // Create a verification call
            $verificationRequest->calls()->create([
                'scheduled_at' => $validated['scheduled_call_at'],
                'platform' => $validated['video_platform'],
                'meeting_link' => $validated['meeting_link'],
                'notes' => $validated['notes'],
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
        
        return redirect()->route('admin.verification.show', $verificationRequest)
            ->with('success', 'Live video verification scheduled successfully.');
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