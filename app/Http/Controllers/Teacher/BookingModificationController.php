<?php

declare(strict_types=1);

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\BookingModificationService;
use App\Http\Requests\Teacher\ApproveModificationRequest;
use App\Http\Requests\Teacher\RejectModificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class BookingModificationController extends Controller
{
    public function __construct(
        private BookingModificationService $modificationService
    ) {}

    /**
     * Display modification requests for teacher
     */
    public function index(Request $request): Response
    {
        $teacher = $request->user();
        
        $modifications = $this->modificationService->getModificationsForUser(
            $teacher->id,
            'teacher',
            $request->only(['type', 'status', 'per_page'])
        );

        return Inertia::render('teacher/modifications/index', [
            'modifications' => $modifications,
            'filters' => $request->only(['type', 'status']),
        ]);
    }

    /**
     * Show a specific modification request
     */
    public function show(Request $request, int $id): Response
    {
        $teacher = $request->user();
        
        $modification = \App\Models\BookingModification::with([
            'booking.subject',
            'student.studentProfile',
            'newTeacher.teacherProfile',
            'newSubject'
        ])->where('teacher_id', $teacher->id)->findOrFail($id);

        return Inertia::render('teacher/modifications/show', [
            'modification' => $modification,
        ]);
    }

    /**
     * Approve a modification request
     */
    public function approve(ApproveModificationRequest $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            $modification = $this->modificationService->approveModification(
                $id,
                $teacher->id,
                $request->teacher_notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Modification request approved successfully.',
                'modification' => $modification->load(['booking', 'student']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Reject a modification request
     */
    public function reject(RejectModificationRequest $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            $modification = $this->modificationService->rejectModification(
                $id,
                $teacher->id,
                $request->teacher_notes
            );

            return response()->json([
                'success' => true,
                'message' => 'Modification request rejected successfully.',
                'modification' => $modification->load(['booking', 'student']),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get modification statistics for teacher
     */
    public function statistics(Request $request): JsonResponse
    {
        $teacher = $request->user();
        
        $stats = [
            'pending_requests' => \App\Models\BookingModification::forTeacher($teacher->id)
                ->pending()
                ->count(),
            'approved_this_month' => \App\Models\BookingModification::forTeacher($teacher->id)
                ->approved()
                ->whereMonth('responded_at', now()->month)
                ->count(),
            'rejected_this_month' => \App\Models\BookingModification::forTeacher($teacher->id)
                ->rejected()
                ->whereMonth('responded_at', now()->month)
                ->count(),
            'urgent_requests' => \App\Models\BookingModification::forTeacher($teacher->id)
                ->pending()
                ->urgent()
                ->count(),
        ];

        return response()->json($stats);
    }
}