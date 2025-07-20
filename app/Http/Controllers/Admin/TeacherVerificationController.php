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
        
        $status = $request->query('status', 'pending');
        
        $verificationRequests = VerificationRequest::query()
            ->with(['teacherProfile.user', 'teacherProfile.documents'])
            ->when($status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();
        
        return Inertia::render('Admin/Teachers/Verification/Index', [
            'verificationRequests' => $verificationRequests,
            'filters' => [
                'status' => $status,
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
        
        return Inertia::render('Admin/Teachers/Verification/Show', [
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
        
        // Validate that all documents are verified
        $pendingDocuments = $verificationRequest->teacherProfile->documents()
            ->where('status', 'pending')
            ->count();
            
        if ($pendingDocuments > 0) {
            return back()->with('error', 'All documents must be verified before approving the teacher.');
        }
        
        DB::transaction(function () use ($verificationRequest, $request) {
            // Update verification request
            $verificationRequest->update([
                'status' => 'verified',
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
                'notes' => 'Teacher verification approved',
            ]);
        });
        
        return redirect()->route('admin.teacher-verifications.index')
            ->with('success', 'Teacher verification approved successfully.');
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
        
        return redirect()->route('admin.teacher-verifications.index')
            ->with('success', 'Teacher verification rejected successfully.');
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
        
        return redirect()->route('admin.teacher-verifications.show', $verificationRequest)
            ->with('success', 'Live video verification scheduled successfully.');
    }

    /**
     * Complete a video verification.
     */
    public function completeVideoVerification(Request $request, VerificationRequest $verificationRequest): RedirectResponse
    {
        Gate::authorize('completeVideoVerification', $verificationRequest);
        
        $validated = $request->validate([
            'verification_result' => 'required|string|in:approve,reject',
            'notes' => 'nullable|string|max:1000',
            'rejection_reason' => 'required_if:verification_result,reject|nullable|string|max:1000',
        ]);
        
        DB::transaction(function () use ($verificationRequest, $request, $validated) {
            // Update the verification call
            $call = $verificationRequest->calls()->latest()->first();
            if ($call) {
                $call->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'notes' => $validated['notes'],
                ]);
            }
            
            // Update verification request based on result
            if ($validated['verification_result'] === 'approve') {
                $verificationRequest->update([
                    'status' => 'verified',
                    'video_status' => 'completed',
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
                    'notes' => 'Teacher verification approved after video call',
                ]);
            } else {
                $verificationRequest->update([
                    'status' => 'rejected',
                    'video_status' => 'completed',
                    'reviewed_by' => $request->user()->id,
                    'reviewed_at' => now(),
                    'rejection_reason' => $validated['rejection_reason'],
                ]);
                
                // Create audit log
                $verificationRequest->auditLogs()->create([
                    'status' => 'rejected',
                    'changed_by' => $request->user()->id,
                    'changed_at' => now(),
                    'notes' => 'Teacher verification rejected after video call: ' . $validated['rejection_reason'],
                ]);
            }
        });
        
        return redirect()->route('admin.teacher-verifications.index')
            ->with('success', 'Video verification completed successfully.');
    }
} 