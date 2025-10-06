<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\TeacherProfile;
use App\Models\VerificationRequest;
use App\Events\TeacherStatusUpdated;
use Illuminate\Support\Facades\Cache;

class TeacherStatusService
{
    public function __construct(
        private SettingsService $settingsService
    ) {}

    /**
     * Get teacher status with caching for performance
     */
    public function getTeacherStatus(User $teacher, bool $useCache = true): array
    {
        $cacheKey = "teacher_status_{$teacher->id}";
        
        if ($useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $status = $this->calculateTeacherStatus($teacher);
        
        // Cache for 5 minutes
        Cache::put($cacheKey, $status, 300);
        
        return $status;
    }

    /**
     * Calculate teacher status from single source of truth
     */
    private function calculateTeacherStatus(User $teacher): array
    {
        // Default status
        $status = [
            'status' => 'Inactive',
            'can_approve' => false,
            'approval_block_reason' => 'Teacher profile not found.',
            'verification_request_id' => null,
            'last_updated' => now()->toISOString(),
        ];

        if (!$teacher->teacherProfile) {
            return $status;
        }

        // Get latest verification request (single source of truth)
        $verificationRequest = $teacher->teacherProfile->verificationRequests()
            ->latest()
            ->first();

        if (!$verificationRequest) {
            $status['approval_block_reason'] = 'No verification request submitted.';
            return $status;
        }

        $status['verification_request_id'] = $verificationRequest->id;

        // Determine status based on verification request and profile
        if ($teacher->teacherProfile->verified) {
            $status['status'] = 'Approved';
            $status['can_approve'] = false;
            $status['approval_block_reason'] = 'Teacher already approved.';
        } else {
            // Use configurable verification settings
            $config = $this->settingsService->getTeacherVerificationSettings();
            
            switch ($verificationRequest->status) {
                case 'verified':
                    $status['status'] = 'Approved';
                    $status['can_approve'] = false;
                    $status['approval_block_reason'] = 'Teacher already verified.';
                    break;
                    
                case 'rejected':
                    $status['status'] = 'Inactive';
                    $status['can_approve'] = false;
                    $status['approval_block_reason'] = 'Teacher application has been rejected.';
                    break;
                    
                case 'pending':
                case 'live_video':
                    $status['status'] = 'Pending';
                    $status['can_approve'] = $this->canApproveTeacher($verificationRequest, $config);
                    $status['approval_block_reason'] = $status['can_approve'] 
                        ? null 
                        : $this->getApprovalBlockReason($verificationRequest, $config);
                    break;
                    
                default:
                    $status['status'] = 'Inactive';
                    $status['can_approve'] = false;
                    $status['approval_block_reason'] = 'Unknown verification status.';
            }
        }

        return $status;
    }

    /**
     * Check if teacher can be approved using configurable settings
     */
    private function canApproveTeacher(VerificationRequest $verificationRequest, array $config): bool
    {
        // If rejected, cannot approve
        if ($verificationRequest->status === 'rejected') {
            return false;
        }

        // Check document verification if required
        if ($config['require_documents']) {
            $totalDocuments = $verificationRequest->teacherProfile->documents()->count();
            $verifiedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'verified')
                ->count();
            $rejectedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'rejected')
                ->count();

            // If no documents submitted, cannot approve
            if ($totalDocuments === 0) {
                return false;
            }

            // If any documents are rejected, cannot approve
            if ($rejectedDocuments > 0) {
                return false;
            }

            // If not all documents are verified, cannot approve
            if ($verifiedDocuments < $totalDocuments) {
                return false;
            }
        }

        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();

            if (!$completedVideoCall) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get reason why teacher cannot be approved
     */
    private function getApprovalBlockReason(VerificationRequest $verificationRequest, array $config): string
    {
        if ($verificationRequest->status === 'rejected') {
            return 'Teacher application has been rejected.';
        }

        // Check document verification if required
        if ($config['require_documents']) {
            $totalDocuments = $verificationRequest->teacherProfile->documents()->count();
            $verifiedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'verified')
                ->count();
            $rejectedDocuments = $verificationRequest->teacherProfile->documents()
                ->where('status', 'rejected')
                ->count();

            if ($totalDocuments === 0) {
                return 'No documents submitted for verification.';
            }

            if ($rejectedDocuments > 0) {
                return 'Some documents have been rejected.';
            }

            if ($verifiedDocuments < $totalDocuments) {
                return 'Not all documents have been verified yet.';
            }
        }

        // Check video verification if required
        if ($config['require_video']) {
            $completedVideoCall = $verificationRequest->calls()
                ->where('status', 'completed')
                ->where('verification_result', 'passed')
                ->first();

            if (!$completedVideoCall) {
                return 'Video verification must be completed and passed.';
            }
        }

        return 'Unknown approval block reason.';
    }

    /**
     * Clear status cache for a teacher and broadcast update
     */
    public function clearTeacherStatusCache(User $teacher, string $action = 'updated'): void
    {
        Cache::forget("teacher_status_{$teacher->id}");
        
        // Get fresh status and broadcast
        $statusData = $this->getTeacherStatus($teacher, false);
        event(new TeacherStatusUpdated($teacher, $statusData, $action));
    }

    /**
     * Clear all teacher status caches
     */
    public function clearAllStatusCache(): void
    {
        Cache::flush(); // In production, use more specific cache tags
    }

    /**
     * Bulk get statuses for multiple teachers
     */
    public function getBulkTeacherStatuses(array $teacherIds): array
    {
        $statuses = [];
        
        foreach ($teacherIds as $teacherId) {
            $teacher = User::find($teacherId);
            if ($teacher) {
                $statuses[$teacherId] = $this->getTeacherStatus($teacher);
            }
        }
        
        return $statuses;
    }
}
