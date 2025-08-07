<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\TeacherProfile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    /**
     * Display a listing of the teacher's documents.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;
        
        if (!$teacherProfile) {
            abort(403, 'Only teachers can access documents');
        }
        
        $documents = $teacherProfile->documents()
            ->orderBy('type')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');
            
        $idVerifications = $documents->get(Document::TYPE_ID_VERIFICATION, collect());
        $certificates = $documents->get(Document::TYPE_CERTIFICATE, collect());
        $resume = $documents->get(Document::TYPE_RESUME, collect())->first();
        
        // Return JSON response for API-like usage
        if ($request->expectsJson() || $request->has('api')) {
            return response()->json([
                'idVerifications' => self::formatDocumentsForDisplay($idVerifications),
                'certificates' => self::formatDocumentsForDisplay($certificates),
                'resume' => $resume ? self::formatDocumentsForDisplay(collect([$resume]))[0] : null,
                'hasIdVerification' => $idVerifications->count() > 0,
                'hasResume' => $resume !== null,
            ]);
        }
        
        // For web requests, redirect to profile page since we don't have a dedicated documents page
        return redirect()->route('teacher.profile.index')->with('active_tab', 'documents');
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Request $request): Response
    {
        $type = $request->query('type', Document::TYPE_CERTIFICATE);
        
        // For web requests, redirect to profile page since we don't have a dedicated documents page
        return redirect()->route('teacher.profile.index')->with('active_tab', 'documents');
    }

    /**
     * Store a newly created document in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $teacherProfile = $user->teacherProfile;
        
        if (!$teacherProfile) {
            abort(403, 'Only teachers can upload documents');
        }
        
        $type = $request->input('type');
        
        // Validate based on document type
        $validator = Validator::make($request->all(), [
            'type' => ['required', Rule::in([Document::TYPE_ID_VERIFICATION, Document::TYPE_CERTIFICATE, Document::TYPE_RESUME])],
            'name' => ['required', 'string', 'max:255'],
            'document' => ['required', 'file', 'max:' . $this->getMaxFileSize($type)],
        ]);
        
        if ($type === Document::TYPE_ID_VERIFICATION) {
            $validator->addRules([
                'document' => ['mimes:jpg,jpeg,png,pdf'],
                'side' => ['required', Rule::in(['front', 'back'])],
            ]);
            
            // Check if ID verification already exists for this side
            $existingId = $teacherProfile->idVerifications()
                ->whereJsonContains('metadata->side', $request->input('side'))
                ->first();
                
            if ($existingId) {
                return redirect()->back()->withErrors([
                    'document' => 'You have already uploaded the ' . $request->input('side') . ' side of your ID.'
                ]);
            }
        } elseif ($type === Document::TYPE_CERTIFICATE) {
            $validator->addRules([
                'document' => ['mimes:jpg,jpeg,png,pdf'],
                'issuer' => ['required', 'string', 'max:255'],
            ]);
        } elseif ($type === Document::TYPE_RESUME) {
            $validator->addRules([
                'document' => ['mimes:pdf,doc,docx'],
            ]);
            
            // Check if resume already exists
            $existingResume = $teacherProfile->resume();
            if ($existingResume) {
                return redirect()->back()->withErrors([
                    'document' => 'You have already uploaded a resume. Please delete it first before uploading a new one.'
                ]);
            }
        }
        
        $validated = $validator->validate();
        
        // Handle file upload
        $path = $request->file('document')->store('documents/' . $teacherProfile->id . '/' . $type, 'public');
        
        // Prepare metadata
        $metadata = [];
        if ($type === Document::TYPE_ID_VERIFICATION) {
            $metadata['side'] = $request->input('side');
        } elseif ($type === Document::TYPE_CERTIFICATE) {
            $metadata['issuer'] = $request->input('issuer');
            $metadata['issue_date'] = $request->input('issue_date');
        }
        
        // Create document
        $document = new Document([
            'teacher_profile_id' => $teacherProfile->id,
            'type' => $type,
            'name' => $validated['name'],
            'path' => $path,
            'status' => Document::STATUS_PENDING,
            'metadata' => $metadata,
        ]);
        
        $document->save();
        
        return redirect()->route('teacher.documents.index')
            ->with('success', 'Document uploaded successfully and is pending verification.');
    }

    /**
     * Display the specified document.
     */
    // public function show(Document $document): Response
    // {
    //     Gate::authorize('view', $document);
        
    //     return Inertia::render('Teacher/Documents/Show', [
    //         'document' => $document,
    //         'documentUrl' => Storage::url($document->path),
    //     ]);
    // }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        
        // Only allow deletion of pending documents
        if ($document->status !== Document::STATUS_PENDING) {
            return back()->withErrors(['error' => 'Only pending documents can be deleted.']);
        }
        
        // Delete the file
        if (Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
        
        $document->delete();
        
        // Determine redirect based on request
        $redirectRoute = request()->input('redirect_route', 'teacher.profile.index');
        $redirectParams = request()->input('redirect_params', []);
        
        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Document deleted successfully.');
    }
    
    /**
     * Download the specified document.
     */
    public function download(Document $document)
    {
        Gate::authorize('view', $document);
        
        if (!Storage::disk('public')->exists($document->path)) {
            abort(404, 'Document not found.');
        }
        
        $extension = pathinfo($document->path, PATHINFO_EXTENSION);
        $filename = Str::slug($document->name) . '.' . $extension;
        
        return response()->download(Storage::disk('public')->path($document->path), $filename);
    }
    
    /**
     * Get the allowed file types for a document type.
     */
    private static function getAllowedFileTypes(string $type): string
    {
        return match($type) {
            Document::TYPE_ID_VERIFICATION => 'image/jpeg, image/png, application/pdf',
            Document::TYPE_CERTIFICATE => 'image/jpeg, image/png, application/pdf',
            Document::TYPE_RESUME => 'application/pdf, application/msword, application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/pdf',
        };
    }
    
    /**
     * Get the maximum file size for a document type in kilobytes.
     */
    private static function getMaxFileSize(string $type): int
    {
        return match($type) {
            Document::TYPE_ID_VERIFICATION => 5120, // 5MB
            Document::TYPE_CERTIFICATE => 5120, // 5MB
            Document::TYPE_RESUME => 10240, // 10MB
            default => 5120, // 5MB
        };
    }

    /**
     * Get documents by type for a teacher profile.
     */
    public static function getDocumentsByType(TeacherProfile $teacherProfile, string $type): \Illuminate\Database\Eloquent\Collection
    {
        return $teacherProfile->documents()
            ->where('type', $type)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get document counts by type for a teacher profile.
     */
    public static function getDocumentCounts(TeacherProfile $teacherProfile): array
    {
        return [
            'id_verifications' => $teacherProfile->documents()->where('type', Document::TYPE_ID_VERIFICATION)->count(),
            'certificates' => $teacherProfile->documents()->where('type', Document::TYPE_CERTIFICATE)->count(),
            'resume' => $teacherProfile->documents()->where('type', Document::TYPE_RESUME)->count(),
        ];
    }

    /**
     * Format documents for frontend display.
     */
    public static function formatDocumentsForDisplay(\Illuminate\Database\Eloquent\Collection $documents): array
    {
        return $documents->map(function ($doc) {
            return [
                'id' => $doc->id,
                'name' => $doc->name,
                'status' => $doc->status,
                'metadata' => $doc->metadata,
                'created_at' => $doc->created_at,
                'verified_at' => $doc->verified_at,
                'rejection_reason' => $doc->rejection_reason,
            ];
        })->toArray();
    }

    /**
     * Get documents for a specific teacher profile by type.
     */
    public static function getTeacherDocumentsByType(int $teacherProfileId, string $type): array
    {
        $teacherProfile = TeacherProfile::find($teacherProfileId);
        if (!$teacherProfile) {
            return [];
        }

        $documents = self::getDocumentsByType($teacherProfile, $type);
        return self::formatDocumentsForDisplay($documents);
    }

    /**
     * Get all documents for a specific teacher profile.
     */
    public static function getAllTeacherDocuments(int $teacherProfileId): array
    {
        $teacherProfile = TeacherProfile::find($teacherProfileId);
        if (!$teacherProfile) {
            return [];
        }

        $documents = $teacherProfile->documents()
            ->orderBy('type')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('type');

        return [
            'id_verifications' => self::formatDocumentsForDisplay($documents->get(Document::TYPE_ID_VERIFICATION, collect())),
            'certificates' => self::formatDocumentsForDisplay($documents->get(Document::TYPE_CERTIFICATE, collect())),
            'resume' => $documents->get(Document::TYPE_RESUME, collect())->first() 
                ? self::formatDocumentsForDisplay(collect([$documents->get(Document::TYPE_RESUME, collect())->first()]))[0] 
                : null,
        ];
    }

    /**
     * Check if a teacher can upload a specific document type.
     */
    public static function canUploadDocumentType(int $teacherProfileId, string $type): bool
    {
        $teacherProfile = TeacherProfile::find($teacherProfileId);
        if (!$teacherProfile) {
            return false;
        }

        switch ($type) {
            case Document::TYPE_ID_VERIFICATION:
                // Can upload both sides
                return true;
            case Document::TYPE_CERTIFICATE:
                // Can upload multiple certificates
                return true;
            case Document::TYPE_RESUME:
                // Can only upload one resume
                return !$teacherProfile->resume();
            default:
                return false;
        }
    }

    /**
     * Get document upload limits and restrictions.
     */
    public static function getDocumentUploadInfo(string $type): array
    {
        return [
            'type' => $type,
            'max_file_size' => self::getMaxFileSize($type),
            'allowed_types' => self::getAllowedFileTypes($type),
            'can_upload_multiple' => $type !== Document::TYPE_RESUME,
            'max_uploads' => $type === Document::TYPE_RESUME ? 1 : null,
        ];
    }
}
