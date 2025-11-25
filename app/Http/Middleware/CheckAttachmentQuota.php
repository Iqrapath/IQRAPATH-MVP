<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\MessageAttachment;

class CheckAttachmentQuota
{
    /**
     * Storage quota limits per user role (in bytes)
     */
    const STORAGE_QUOTAS = [
        'student' => 100 * 1024 * 1024,    // 100MB for students
        'teacher' => 500 * 1024 * 1024,    // 500MB for teachers
        'guardian' => 100 * 1024 * 1024,   // 100MB for guardians
        'admin' => 1024 * 1024 * 1024,     // 1GB for admins
        'super-admin' => 5 * 1024 * 1024 * 1024  // 5GB for super-admins
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        // Get user's storage quota based on role
        $quota = self::STORAGE_QUOTAS[$user->role] ?? self::STORAGE_QUOTAS['student'];

        // Calculate user's current storage usage
        $usedStorage = MessageAttachment::whereHas('message', function ($query) use ($user) {
            $query->where('sender_id', $user->id);
        })->sum('file_size');

        // Get the size of the file being uploaded
        $file = $request->file('file');
        $fileSize = $file ? $file->getSize() : 0;

        // Check if upload would exceed quota
        if (($usedStorage + $fileSize) > $quota) {
            $remainingQuota = max(0, $quota - $usedStorage);
            
            return response()->json([
                'success' => false,
                'message' => 'Storage quota exceeded',
                'error_code' => 'QUOTA_EXCEEDED',
                'data' => [
                    'quota' => $quota,
                    'used' => $usedStorage,
                    'remaining' => $remainingQuota,
                    'file_size' => $fileSize,
                    'formatted_quota' => $this->formatBytes($quota),
                    'formatted_used' => $this->formatBytes($usedStorage),
                    'formatted_remaining' => $this->formatBytes($remainingQuota)
                ]
            ], 413); // 413 Payload Too Large
        }

        return $next($request);
    }

    /**
     * Format bytes to human-readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}

