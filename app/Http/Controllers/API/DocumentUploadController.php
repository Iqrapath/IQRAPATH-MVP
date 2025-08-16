<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\JsonResponse;

class DocumentUploadController extends Controller
{
    /**
     * Upload a document for a teacher
     */
    public function upload(Request $request): JsonResponse
    {
        \Log::info('Document upload request received', [
            'user_id' => auth()->id(),
            'teacher_id' => $request->teacher_id,
            'type' => $request->type,
            'has_file' => $request->hasFile('document')
        ]);
        
        // Validate the request
        $validator = Validator::make($request->all(), [
            'document' => 'required|file|mimes:jpeg,jpg,png,pdf|max:5120', // 5MB max
            'type' => 'required|in:id_verification,certificate,resume',
            'teacher_id' => 'required|exists:users,id',
            'side' => 'nullable|in:front,back',
            'certificate_type' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Check if user is a teacher
            $teacher = User::where('id', $request->teacher_id)
                ->where('role', 'teacher')
                ->first();

            if (!$teacher) {
                return response()->json([
                    'success' => false,
                    'message' => 'Teacher not found'
                ], 404);
            }

            // Get or create teacher profile
            $teacherProfile = TeacherProfile::firstOrCreate(
                ['user_id' => $teacher->id],
                [
                    'verified' => false,
                    'languages' => [],
                    'teaching_type' => null,
                    'teaching_mode' => null,
                ]
            );

            // Handle file upload
            $file = $request->file('document');
            $fileName = time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('documents/' . $teacher->id, $fileName, 'public');

            // Determine document type and metadata
            $documentType = $request->type;
            $metadata = [];

            switch ($documentType) {
                case 'id_verification':
                    $metadata['side'] = $request->side;
                    $metadata['document_type'] = 'id_verification';
                    break;
                    
                case 'certificate':
                    $metadata['certificate_type'] = $request->certificate_type;
                    $metadata['document_type'] = 'certificate';
                    break;
                    
                case 'resume':
                    $metadata['document_type'] = 'resume';
                    break;
            }

            // Create document record
            $document = Document::create([
                'teacher_profile_id' => $teacherProfile->id,
                'name' => $file->getClientOriginalName(),
                'path' => $filePath,
                'type' => $documentType,
                'status' => 'pending',
                'metadata' => $metadata,
                'uploaded_by' => auth()->id(),
                'uploaded_at' => now(),
            ]);

            \Log::info('Document uploaded successfully', [
                'document_id' => $document->id,
                'teacher_id' => $teacher->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully',
                'document' => [
                    'id' => $document->id,
                    'name' => $document->name,
                    'type' => $document->type,
                    'status' => $document->status,
                    'metadata' => $document->metadata,
                ]
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Document upload error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to upload document. Please try again.'
            ], 500);
        }
    }

    /**
     * Delete a document
     */
    public function delete(Request $request, Document $document): JsonResponse
    {
        try {
            // Check if user has permission to delete this document
            if ($document->teacherProfile->user_id !== $request->teacher_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 403);
            }

            // Delete file from storage
            if (Storage::disk('public')->exists($document->path)) {
                Storage::disk('public')->delete($document->path);
            }

            // Delete document record
            $document->delete();

            return response()->json([
                'success' => true,
                'message' => 'Document deleted successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Document deletion error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete document'
            ], 500);
        }
    }

    /**
     * Get document download URL
     */
    public function download(Document $document): JsonResponse
    {
        try {
            if (!Storage::disk('public')->exists($document->path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document not found'
                ], 404);
            }

            $url = Storage::disk('public')->url($document->path);

            return response()->json([
                'success' => true,
                'download_url' => $url,
                'filename' => $document->name
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate download URL'
            ], 500);
        }
    }
}
