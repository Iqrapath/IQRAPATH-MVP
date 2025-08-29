<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\User;
use App\Models\TeacherProfile;
use App\Notifications\DocumentVerifiedNotification;
use App\Notifications\DocumentRejectedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class DocumentVerificationController extends Controller
{
    /**
     * Display a listing of documents pending verification.
     */
    public function index(Request $request): Response
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $status = $request->query('status', Document::STATUS_PENDING);
        $type = $request->query('type');
        
        $query = Document::query()
            ->with('teacherProfile.user')
            ->orderBy('created_at', 'desc');
            
        if ($status) {
            $query->where('status', $status);
        }
        
        if ($type) {
            $query->where('type', $type);
        }
        
        $documents = $query->paginate(20)->withQueryString();
        
        return Inertia::render('Admin/Documents/Verification/Index', [
            'documents' => $documents,
            'filters' => [
                'status' => $status,
                'type' => $type,
            ],
            'stats' => [
                'pending' => Document::where('status', Document::STATUS_PENDING)->count(),
                'verified' => Document::where('status', Document::STATUS_VERIFIED)->count(),
                'rejected' => Document::where('status', Document::STATUS_REJECTED)->count(),
            ],
        ]);
    }

    /**
     * Show document verification details.
     */
    public function show(Document $document): Response
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $document->load('teacherProfile.user');
        
        return Inertia::render('Admin/Documents/Verification/Show', [
            'document' => $document,
            'documentUrl' => Storage::url($document->path),
            'teacher' => $document->teacherProfile->user,
            'canBeResubmitted' => $document->canBeResubmitted(),
            'remainingResubmissions' => $document->getRemainingResubmissions(),
        ]);
    }

    /**
     * Verify a document.
     */
    public function verify(Request $request, Document $document)
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $document->markAsVerified($request->user());
        
        // Reset resubmission count when verified
        $document->resetResubmissionCount();
        
        // Check if all documents for this teacher are now verified
        $this->checkAllDocumentsVerified($document->teacherProfile);
        
        // Send notification to teacher
        try {
            $teacher = $document->teacherProfile->user;
            $teacher->notify(new DocumentVerifiedNotification($document));
        } catch (\Throwable $e) {
            // Log error but don't block the verification
            \Log::error('Failed to send document verified notification', [
                'document_id' => $document->id,
                'teacher_id' => $teacher->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
        
        // Return JSON response for API calls
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document verified successfully.',
                'document' => [
                    'id' => $document->id,
                    'status' => $document->status,
                    'verified_at' => $document->verified_at,
                ]
            ]);
        }
        
        return redirect()->route('admin.documents.index')
            ->with('success', 'Document verified successfully.');
    }

    /**
     * Reject a document.
     */
    public function reject(Request $request, Document $document)
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
            'resubmission_instructions' => 'nullable|string|max:1000',
        ]);
        
        $document->markAsRejected($request->user(), $validated['rejection_reason']);
        
        // Check verification status after rejection
        $this->checkAllDocumentsVerified($document->teacherProfile);
        
        // Send notification to teacher about rejection
        try {
            $teacher = $document->teacherProfile->user;
            $teacher->notify(new DocumentRejectedNotification(
                $document,
                $validated['rejection_reason'],
                $validated['resubmission_instructions'] ?? null
            ));
        } catch (\Throwable $e) {
            // Log error but don't block the rejection
            \Log::error('Failed to send document rejected notification', [
                'document_id' => $document->id,
                'teacher_id' => $teacher->id ?? null,
                'error' => $e->getMessage()
            ]);
        }
        
        // Return JSON response for API calls
        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Document rejected successfully.',
                'document' => [
                    'id' => $document->id,
                    'status' => $document->status,
                    'rejection_reason' => $document->rejection_reason,
                    'rejected_at' => $document->rejected_at,
                ]
            ]);
        }
        
        return redirect()->route('admin.documents.index')
            ->with('success', 'Document rejected successfully.');
    }
    
    /**
     * Batch verify multiple documents.
     */
    public function batchVerify(Request $request): RedirectResponse
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $validated = $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'exists:documents,id',
        ]);
        
        $documents = Document::whereIn('id', $validated['document_ids'])->get();
        
        foreach ($documents as $document) {
            $document->markAsVerified($request->user());
            $document->resetResubmissionCount();
            
            // Send notification to teacher for each verified document
            try {
                $teacher = $document->teacherProfile->user;
                $teacher->notify(new DocumentVerifiedNotification($document));
            } catch (\Throwable $e) {
                // Log error but don't block the verification
                \Log::error('Failed to send document verified notification', [
                    'document_id' => $document->id,
                    'teacher_id' => $teacher->id ?? null,
                    'error' => $e->getMessage()
                ]);
            }
        }
        
        // Check verification status for each teacher profile
        $teacherProfiles = $documents->pluck('teacherProfile')->unique();
        foreach ($teacherProfiles as $profile) {
            $this->checkAllDocumentsVerified($profile);
        }
        
        return redirect()->route('admin.documents.index')
            ->with('success', count($documents) . ' documents verified successfully.');
    }
    
    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        if (!Storage::disk('public')->exists($document->path)) {
            abort(404, 'Document not found.');
        }
        
        $extension = pathinfo($document->path, PATHINFO_EXTENSION);
        $filename = Str::slug($document->name) . '.' . $extension;
        
        return response()->download(Storage::disk('public')->path($document->path), $filename);
    }

    /**
     * Check if all documents for a teacher profile are verified.
     */
    private function checkAllDocumentsVerified(TeacherProfile $profile): void
    {
        $pendingDocuments = $profile->documents()
            ->where('status', Document::STATUS_PENDING)
            ->count();
            
        $rejectedDocuments = $profile->documents()
            ->where('status', Document::STATUS_REJECTED)
            ->count();
            
        $verifiedDocuments = $profile->documents()
            ->where('status', Document::STATUS_VERIFIED)
            ->count();
            
        $totalDocuments = $profile->documents()->count();
        
        // Get the verification request
        $verificationRequest = $profile->verificationRequests()
            ->where('status', 'pending')
            ->first();
            
        if (!$verificationRequest) {
            return;
        }
        
        // Update docs_status based on document statuses
        if ($rejectedDocuments > 0) {
            // If any document is rejected, set docs_status to rejected
            $verificationRequest->update(['docs_status' => 'rejected']);
        } elseif ($pendingDocuments === 0 && $verifiedDocuments > 0) {
            // If no pending documents and at least one verified, set to verified
            $verificationRequest->update(['docs_status' => 'verified']);
        } elseif ($pendingDocuments > 0) {
            // If there are pending documents, set to pending
            $verificationRequest->update(['docs_status' => 'pending']);
        }
    }


}
