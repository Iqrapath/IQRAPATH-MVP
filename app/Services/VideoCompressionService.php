<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class VideoCompressionService
{
    /**
     * Maximum file size in bytes (8MB to match current PHP limits)
     */
    const MAX_FILE_SIZE = 8 * 1024 * 1024; // 8MB

    /**
     * Maximum file size for upload (7MB to leave buffer)
     */
    const MAX_UPLOAD_SIZE = 7 * 1024 * 1024; // 7MB

    /**
     * Validate video file
     */
    public static function validateVideo(UploadedFile $file): array
    {
        $errors = [];

        // Check file size - must be under 7MB
        if ($file->getSize() > self::MAX_UPLOAD_SIZE) {
            $errors[] = 'Video file size must be less than 7MB. Please compress your video before uploading.';
        }

        // Check file type
        $allowedTypes = ['video/mp4', 'video/mov', 'video/avi', 'video/quicktime'];
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = 'Only MP4, MOV, and AVI video formats are allowed.';
        }

        return $errors;
    }

    /**
     * Process video file (no compression, just validation)
     */
    public static function processVideo(UploadedFile $file): UploadedFile
    {
        // For now, just return the original file
        // In the future, this could implement server-side compression
        return $file;
    }

    /**
     * Get upload guidelines
     */
    public static function getUploadGuidelines(): array
    {
        return [
            'max_size' => '7MB',
            'formats' => ['MP4', 'MOV', 'AVI'],
            'duration' => '20-60 seconds',
            'resolution' => '1280x720 minimum',
            'aspect_ratio' => '16:9 (landscape)',
            'compression_note' => 'Please compress your video to under 7MB before uploading.'
        ];
    }
}
