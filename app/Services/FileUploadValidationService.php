<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Http\UploadedFile;

class FileUploadValidationService
{
    /**
     * Default file size limits in KB
     */
    private const DEFAULT_LIMITS = [
        'profile_photo' => 5120,    // 5MB
        'document' => 5120,         // 5MB
        'video' => 7168,           // 7MB
        'attachment' => 10240,      // 10MB
    ];

    /**
     * Get maximum file size for a specific upload type
     */
    public static function getMaxFileSize(string $type): int
    {
        $settingKey = "file_upload_max_size_{$type}";
        $defaultSize = self::DEFAULT_LIMITS[$type] ?? 5120;
        
        return (int) SystemSetting::get($settingKey, $defaultSize);
    }

    /**
     * Get maximum file size in bytes for a specific upload type
     */
    public static function getMaxFileSizeBytes(string $type): int
    {
        return self::getMaxFileSize($type) * 1024;
    }

    /**
     * Get human-readable file size limit
     */
    public static function getMaxFileSizeHuman(string $type): string
    {
        $sizeKB = self::getMaxFileSize($type);
        
        if ($sizeKB >= 1024) {
            return round($sizeKB / 1024, 1) . 'MB';
        }
        
        return $sizeKB . 'KB';
    }

    /**
     * Validate file size for a specific upload type
     */
    public static function validateFileSize(UploadedFile $file, string $type): array
    {
        $errors = [];
        $maxSizeBytes = self::getMaxFileSizeBytes($type);
        $maxSizeHuman = self::getMaxFileSizeHuman($type);
        
        if ($file->getSize() > $maxSizeBytes) {
            $errors[] = "File size must be less than {$maxSizeHuman}. Current size: " . self::formatBytes($file->getSize());
        }
        
        return $errors;
    }

    /**
     * Validate file type for a specific upload type
     */
    public static function validateFileType(UploadedFile $file, string $type): array
    {
        $errors = [];
        $allowedTypes = self::getAllowedFileTypes($type);
        
        if (!in_array($file->getMimeType(), $allowedTypes)) {
            $errors[] = "File type not allowed. Allowed types: " . implode(', ', $allowedTypes);
        }
        
        return $errors;
    }

    /**
     * Get allowed file types for a specific upload type
     */
    public static function getAllowedFileTypes(string $type): array
    {
        return match($type) {
            'profile_photo' => ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'],
            'document' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
            'video' => ['video/mp4', 'video/mov', 'video/avi', 'video/quicktime'],
            'attachment' => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            default => ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'],
        };
    }

    /**
     * Comprehensive file validation
     */
    public static function validateFile(UploadedFile $file, string $type): array
    {
        $errors = [];
        
        // Validate file size
        $sizeErrors = self::validateFileSize($file, $type);
        $errors = array_merge($errors, $sizeErrors);
        
        // Validate file type
        $typeErrors = self::validateFileType($file, $type);
        $errors = array_merge($errors, $typeErrors);
        
        return $errors;
    }

    /**
     * Get upload guidelines for a specific type
     */
    public static function getUploadGuidelines(string $type): array
    {
        return [
            'max_size' => self::getMaxFileSizeHuman($type),
            'allowed_types' => self::getAllowedFileTypes($type),
            'max_size_bytes' => self::getMaxFileSizeBytes($type),
        ];
    }

    /**
     * Set file size limit for a specific type
     */
    public static function setMaxFileSize(string $type, int $sizeKB): bool
    {
        $settingKey = "file_upload_max_size_{$type}";
        return SystemSetting::set($settingKey, $sizeKB);
    }

    /**
     * Get all file upload limits
     */
    public static function getAllFileLimits(): array
    {
        $limits = [];
        
        foreach (array_keys(self::DEFAULT_LIMITS) as $type) {
            $limits[$type] = [
                'max_size_kb' => self::getMaxFileSize($type),
                'max_size_human' => self::getMaxFileSizeHuman($type),
                'max_size_bytes' => self::getMaxFileSizeBytes($type),
                'allowed_types' => self::getAllowedFileTypes($type),
            ];
        }
        
        return $limits;
    }

    /**
     * Format bytes to human readable format
     */
    private static function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
