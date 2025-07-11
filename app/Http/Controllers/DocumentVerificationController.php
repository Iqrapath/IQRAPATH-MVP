<?php

namespace App\Http\Controllers;

use App\Models\Document;
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
        ]);
    }

    /**
     * Verify a document.
     */
    public function verify(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $document->markAsVerified($request->user());
        
        return redirect()->route('admin.documents.index')
            ->with('success', 'Document verified successfully.');
    }

    /**
     * Reject a document.
     */
    public function reject(Request $request, Document $document): RedirectResponse
    {
        Gate::authorize('verifyDocuments', Document::class);
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);
        
        $document->markAsRejected($request->user(), $validated['rejection_reason']);
        
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
}
