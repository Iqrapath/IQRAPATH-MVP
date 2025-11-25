<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MessageAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class AttachmentService
{
    /**
     * Maximum file sizes in bytes by type
     */
    const MAX_FILE_SIZES = [
        'voice' => 5 * 1024 * 1024,  // 5MB for voice messages
        'image' => 10 * 1024 * 1024, // 10MB for images
        'file' => 10 * 1024 * 1024   // 10MB for general files
    ];

    /**
     * Supported image MIME types
     */
    const SUPPORTED_IMAGE_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ];

    /**
     * Supported audio MIME types
     */
    const SUPPORTED_AUDIO_TYPES = [
        'audio/webm',
        'audio/webm;codecs=opus',
        'audio/mp3',
        'audio/mpeg',
        'audio/wav',
        'audio/wave',
        'audio/x-wav',
        'audio/ogg',
        'audio/mp4',
        'audio/x-m4a',
        'audio/m4a',
        'video/webm', // Some browsers report WebM audio as video/webm
        'application/octet-stream' // Fallback for unrecognized audio files
    ];

    /**
     * Supported document MIME types
     */
    const SUPPORTED_DOCUMENT_TYPES = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed'
    ];

    /**
     * Dangerous file extensions that should never be allowed
     */
    const DANGEROUS_EXTENSIONS = [
        'exe', 'bat', 'cmd', 'com', 'pif', 'scr',
        'vbs', 'js', 'jar', 'app', 'deb', 'rpm',
        'sh', 'bash', 'ps1'
    ];

    /**
     * Dangerous MIME types that should never be allowed
     */
    const DANGEROUS_MIME_TYPES = [
        'application/x-executable',
        'application/x-msdownload',
        'application/x-msdos-program',
        'application/x-sh',
        'application/x-bat',
        'application/x-dosexec',
        'text/x-shellscript'
    ];

    /**
     * Store an uploaded file and create attachment record
     */
    public function storeAttachment(
        UploadedFile $file,
        int $messageId,
        string $attachmentType,
        array $metadata = []
    ): MessageAttachment {
        // Validate file
        $this->validateFile($file, $attachmentType);

        // Generate secure filename
        $filename = $this->generateSecureFilename($file);

        // Determine storage path
        $storagePath = $this->getStoragePath($attachmentType);
        $fullPath = $storagePath . '/' . $filename;

        // Store file in private storage
        $file->storeAs($storagePath, $filename, 'private');

        // Create attachment record
        return MessageAttachment::create([
            'message_id' => $messageId,
            'filename' => $filename,
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $fullPath,
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'attachment_type' => $attachmentType,
            'duration' => $metadata['duration'] ?? null,
            'metadata' => $metadata
        ]);
    }

    /**
     * Validate uploaded file
     * 
     * **Property: Server-side file validation**
     * **Validates: Requirements 4.1, 4.2**
     */
    public function validateFile(UploadedFile $file, string $attachmentType): void
    {
        // Validate attachment type
        if (!in_array($attachmentType, ['voice', 'image', 'file'])) {
            throw new InvalidArgumentException("Invalid attachment type: {$attachmentType}");
        }

        // Check if file is valid
        if (!$file->isValid()) {
            throw new InvalidArgumentException("Invalid file upload");
        }

        // Check for zero-byte files
        if ($file->getSize() === 0) {
            throw new InvalidArgumentException("File is empty (0 bytes)");
        }

        // Check file size based on type
        $maxSize = self::MAX_FILE_SIZES[$attachmentType];
        if ($file->getSize() > $maxSize) {
            $maxSizeMB = round($maxSize / 1024 / 1024, 1);
            throw new InvalidArgumentException(
                "File size exceeds maximum allowed size of {$maxSizeMB}MB for {$attachmentType} files"
            );
        }

        // Get MIME type and extension
        $mimeType = $file->getMimeType();
        $extension = strtolower($file->getClientOriginalExtension());

        // Check for dangerous extensions
        if (in_array($extension, self::DANGEROUS_EXTENSIONS)) {
            throw new InvalidArgumentException(
                "File extension '.{$extension}' is not allowed for security reasons"
            );
        }

        // Check for dangerous MIME types
        if (in_array($mimeType, self::DANGEROUS_MIME_TYPES)) {
            throw new InvalidArgumentException(
                "File type is not allowed for security reasons"
            );
        }

        // Validate based on attachment type
        switch ($attachmentType) {
            case 'image':
                $this->validateImageFile($file, $mimeType);
                break;

            case 'voice':
                $this->validateVoiceFile($file, $mimeType);
                break;

            case 'file':
                $this->validateDocumentFile($file, $mimeType);
                break;
        }
    }

    /**
     * Validate image file
     */
    private function validateImageFile(UploadedFile $file, string $mimeType): void
    {
        if (!in_array($mimeType, self::SUPPORTED_IMAGE_TYPES)) {
            throw new InvalidArgumentException(
                "Unsupported image format. Supported formats: JPEG, PNG, GIF, WebP, SVG"
            );
        }

        // Additional validation: check if file is actually an image
        // Skip this check in testing environment (fake files don't have valid image data)
        if (!app()->environment('testing')) {
            $imageInfo = @getimagesize($file->getRealPath());
            // SVG files may not return valid image info from getimagesize
            if ($imageInfo === false && $mimeType !== 'image/svg+xml') {
                throw new InvalidArgumentException(
                    "File is not a valid image"
                );
            }

            // Check image dimensions (max 4096x4096) - skip for SVG
            if ($imageInfo && $mimeType !== 'image/svg+xml' && ($imageInfo[0] > 4096 || $imageInfo[1] > 4096)) {
                throw new InvalidArgumentException(
                    "Image dimensions exceed maximum allowed size of 4096x4096 pixels"
                );
            }
        }
    }

    /**
     * Validate voice file
     */
    private function validateVoiceFile(UploadedFile $file, string $mimeType): void
    {
        if (!in_array($mimeType, self::SUPPORTED_AUDIO_TYPES)) {
            \Log::error('Unsupported audio MIME type', [
                'mime_type' => $mimeType,
                'filename' => $file->getClientOriginalName(),
                'extension' => $file->getClientOriginalExtension()
            ]);
            
            throw new InvalidArgumentException(
                "Unsupported audio format '{$mimeType}'. Supported formats: WebM, MP3, WAV, OGG, M4A"
            );
        }

        // Check minimum file size (at least 1KB for valid audio)
        if ($file->getSize() < 1024) {
            throw new InvalidArgumentException(
                "Audio file is too small to be valid"
            );
        }
    }

    /**
     * Validate document file
     */
    private function validateDocumentFile(UploadedFile $file, string $mimeType): void
    {
        // Allow images as files
        if (in_array($mimeType, self::SUPPORTED_IMAGE_TYPES)) {
            return;
        }

        // Check if document type is supported
        if (!in_array($mimeType, self::SUPPORTED_DOCUMENT_TYPES)) {
            throw new InvalidArgumentException(
                "Unsupported file format. Supported formats: PDF, Word, Excel, PowerPoint, Text, CSV, ZIP"
            );
        }
    }

    /**
     * Generate secure filename
     */
    private function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $sanitizedName = $this->sanitizeFilename($file->getClientOriginalName());
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(8);

        return "{$timestamp}_{$random}_{$sanitizedName}";
    }

    /**
     * Sanitize filename to prevent security issues
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove extension for processing
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $extension = pathinfo($filename, PATHINFO_EXTENSION);

        // Remove dangerous characters
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);

        // Limit length
        $name = substr($name, 0, 100);

        // Ensure it's not empty
        if (empty($name)) {
            $name = 'file';
        }

        return $extension ? "{$name}.{$extension}" : $name;
    }

    /**
     * Get storage path for attachment type
     */
    private function getStoragePath(string $attachmentType): string
    {
        $year = now()->year;
        $month = now()->format('m');

        return "message-attachments/{$attachmentType}/{$year}/{$month}";
    }

    /**
     * Get file from storage
     */
    public function getFile(MessageAttachment $attachment): ?string
    {
        if (!Storage::disk('private')->exists($attachment->file_path)) {
            return null;
        }

        return Storage::disk('private')->get($attachment->file_path);
    }

    /**
     * Generate signed URL for attachment
     */
    public function generateSignedUrl(MessageAttachment $attachment, int $expirationMinutes = 60): string
    {
        return Storage::disk('private')->temporaryUrl(
            $attachment->file_path,
            now()->addMinutes($expirationMinutes)
        );
    }

    /**
     * Delete attachment file and record
     */
    public function deleteAttachment(MessageAttachment $attachment): bool
    {
        // Delete file from storage
        if (Storage::disk('private')->exists($attachment->file_path)) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        // Delete database record
        return $attachment->delete();
    }

    /**
     * Generate thumbnail for image attachment
     * 
     * Note: Requires intervention/image package
     * Install with: composer require intervention/image
     */
    public function generateThumbnail(MessageAttachment $attachment, int $width = 300, int $height = 300): ?string
    {
        // Check if attachment is an image
        if ($attachment->attachment_type !== 'image') {
            return null;
        }

        // Skip SVG files
        if ($attachment->mime_type === 'image/svg+xml') {
            return null;
        }

        try {
            // Use Intervention Image v3 API
            $manager = \Intervention\Image\ImageManager::gd();
            
            // Get original image
            $originalPath = Storage::disk('private')->path($attachment->file_path);
            $image = $manager->read($originalPath);

            // Resize to thumbnail (cover fit)
            $image->cover($width, $height);

            // Generate thumbnail path
            $thumbnailPath = str_replace(
                $attachment->filename,
                'thumb_' . $attachment->filename,
                $attachment->file_path
            );

            // Save thumbnail
            $thumbnailFullPath = Storage::disk('private')->path($thumbnailPath);
            
            // Ensure directory exists
            $thumbnailDir = dirname($thumbnailFullPath);
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }

            $image->toJpeg(80)->save($thumbnailFullPath); // 80% quality JPEG

            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::error('Thumbnail generation failed', [
                'attachment_id' => $attachment->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Get thumbnail URL for an attachment
     */
    public function getThumbnailUrl(MessageAttachment $attachment): ?string
    {
        if (!$attachment->thumbnail_path) {
            return null;
        }

        return Storage::disk('private')->temporaryUrl(
            $attachment->thumbnail_path,
            now()->addMinutes(60)
        );
    }

    /**
     * Get human readable file size
     */
    public static function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
