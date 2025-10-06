<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\TeacherStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TeacherStatusController extends Controller
{
    public function __construct(
        private TeacherStatusService $teacherStatusService
    ) {}

    /**
     * Get teacher status
     */
    public function show(User $teacher): JsonResponse
    {
        $statusData = $this->teacherStatusService->getTeacherStatus($teacher);
        
        return response()->json([
            'success' => true,
            'data' => $statusData,
        ]);
    }

    /**
     * Refresh teacher status (clear cache and get fresh data)
     */
    public function refresh(User $teacher): JsonResponse
    {
        $this->teacherStatusService->clearTeacherStatusCache($teacher, 'refreshed');
        $statusData = $this->teacherStatusService->getTeacherStatus($teacher, false);
        
        return response()->json([
            'success' => true,
            'data' => $statusData,
            'message' => 'Status refreshed successfully',
        ]);
    }

    /**
     * Get bulk teacher statuses
     */
    public function bulk(Request $request): JsonResponse
    {
        $request->validate([
            'teacher_ids' => 'required|array',
            'teacher_ids.*' => 'integer|exists:users,id',
        ]);

        $statuses = $this->teacherStatusService->getBulkTeacherStatuses($request->teacher_ids);
        
        return response()->json([
            'success' => true,
            'data' => $statuses,
        ]);
    }
}
