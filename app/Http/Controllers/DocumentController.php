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
        
        return Inertia::render('Teacher/Documents/Index', [
            'idVerifications' => $idVerifications,
            'certificates' => $certificates,
            'resume' => $resume,
            'hasIdVerification' => $idVerifications->count() > 0,
            'hasResume' => $resume !== null,
        ]);
    }

    /**
     * Show the form for creating a new document.
     */
    public function create(Request $request): Response
    {
        $type = $request->query('type', Document::TYPE_CERTIFICATE);
        
        return Inertia::render('Teacher/Documents/Create', [
            'documentType' => $type,
            'allowedTypes' => $this->getAllowedFileTypes($type),
            'maxFileSize' => $this->getMaxFileSize($type),
        ]);
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
    public function show(Document $document): Response
    {
        Gate::authorize('view', $document);
        
        return Inertia::render('Teacher/Documents/Show', [
            'document' => $document,
            'documentUrl' => Storage::url($document->path),
        ]);
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Document $document): RedirectResponse
    {
        Gate::authorize('delete', $document);
        
        // Delete the file
        if (Storage::disk('public')->exists($document->path)) {
            Storage::disk('public')->delete($document->path);
        }
        
        $document->delete();
        
        return redirect()->route('teacher.documents.index')
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
    private function getAllowedFileTypes(string $type): string
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
    private function getMaxFileSize(string $type): int
    {
        return match($type) {
            Document::TYPE_ID_VERIFICATION => 5120, // 5MB
            Document::TYPE_CERTIFICATE => 5120, // 5MB
            Document::TYPE_RESUME => 10240, // 10MB
            default => 5120, // 5MB
        };
    }
}
