<?php

declare(strict_types=1);

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentController extends Controller
{
    public function __construct(
        private AttachmentService $attachmentService
    ) {}

    /**
     * Upload attachment to a message
     * 
     * **Property: Server-side validation and error handling**
     * **Validates: Requirements 4.1, 4.2**
     */
    public function upload(Request $request, int $messageId): JsonResponse
    {
        // Validate request
        try {
            $validated = $request->validate([
                'file' => 'required|file|max:10240', // 10MB max
                'attachment_type' => 'required|in:voice,image,file',
                'duration' => 'nullable|integer|min:0|max:300', // Max 5 minutes
                'metadata' => 'nullable|array'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        }

        // Get the message
        try {
            $message = Message::findOrFail($messageId);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Message not found'
            ], 404);
        }

        // Check authorization - user must be the sender or a conversation participant
        $conversation = $message->conversation;
        $isParticipant = $conversation->participants()
            ->where('user_id', auth()->id())
            ->exists();

        if (!$isParticipant) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to upload attachments to this message',
                'error_code' => 'UNAUTHORIZED_UPLOAD'
            ], 403);
        }

        try {
            $file = $request->file('file');
            $attachmentType = $request->input('attachment_type');
            $metadata = $request->input('metadata', []);

            if ($request->has('duration')) {
                $metadata['duration'] = $request->input('duration');
            }

            // Additional validation for voice messages
            if ($attachmentType === 'voice') {
                $duration = $metadata['duration'] ?? 0;
                if ($duration < 1) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voice message is too short (minimum 1 second)',
                        'error_code' => 'VOICE_TOO_SHORT'
                    ], 422);
                }
                if ($duration > 300) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Voice message is too long (maximum 5 minutes)',
                        'error_code' => 'VOICE_TOO_LONG'
                    ], 422);
                }
            }

            // Store the attachment (this will perform additional validation)
            $attachment = $this->attachmentService->storeAttachment(
                $file,
                $messageId,
                $attachmentType,
                $metadata
            );

            return response()->json([
                'success' => true,
                'message' => 'Attachment uploaded successfully',
                'data' => [
                    'id' => $attachment->id,
                    'filename' => $attachment->filename,
                    'original_filename' => $attachment->original_filename,
                    'file_size' => $attachment->file_size,
                    'mime_type' => $attachment->mime_type,
                    'attachment_type' => $attachment->attachment_type,
                    'duration' => $attachment->duration,
                    'formatted_size' => AttachmentService::formatFileSize($attachment->file_size),
                    'created_at' => $attachment->created_at->toISOString()
                ]
            ], 201);
        } catch (\InvalidArgumentException $e) {
            // Validation errors from AttachmentService
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);
        } catch (\Exception $e) {
            // Log the error for debugging
            \Log::error('Attachment upload failed', [
                'message_id' => $messageId,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload attachment. Please try again.',
                'error_code' => 'UPLOAD_FAILED'
            ], 500);
        }
    }

    /**
     * Download attachment file
     */
    public function download(int $attachmentId): StreamedResponse|JsonResponse
    {
        $attachment = MessageAttachment::findOrFail($attachmentId);

        // Check authorization using policy
        if (!Gate::allows('download', $attachment)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to download this attachment'
            ], 403);
        }

        $fileContent = $this->attachmentService->getFile($attachment);

        if (!$fileContent) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        return response()->streamDownload(
            function () use ($fileContent) {
                echo $fileContent;
            },
            $attachment->original_filename,
            [
                'Content-Type' => $attachment->mime_type,
                'Content-Length' => $attachment->file_size
            ]
        );
    }

    /**
     * Generate signed URL for attachment
     */
    public function getSignedUrl(int $attachmentId): JsonResponse
    {
        $attachment = MessageAttachment::findOrFail($attachmentId);

        // Check authorization using policy
        if (!Gate::allows('view', $attachment)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to access this attachment'
            ], 403);
        }

        try {
            $signedUrl = $this->attachmentService->generateSignedUrl($attachment, 60);

            return response()->json([
                'success' => true,
                'data' => [
                    'url' => $signedUrl,
                    'expires_in' => 60 // minutes
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate signed URL'
            ], 500);
        }
    }

    /**
     * Delete attachment
     */
    public function destroy(int $attachmentId): JsonResponse
    {
        $attachment = MessageAttachment::findOrFail($attachmentId);

        // Check authorization using policy
        if (!Gate::allows('delete', $attachment)) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this attachment'
            ], 403);
        }

        try {
            $this->attachmentService->deleteAttachment($attachment);

            return response()->json([
                'success' => true,
                'message' => 'Attachment deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete attachment'
            ], 500);
        }
    }
}
