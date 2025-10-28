<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\TeachingSession;
use App\Services\SessionAccessControlService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SessionAccessController extends Controller
{
    public function __construct(
        private SessionAccessControlService $accessControlService
    ) {}

    /**
     * Check if user can access a session (API endpoint for real-time checking)
     */
    public function checkAccess(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();
        
        $session = TeachingSession::with(['teacher', 'student', 'subject'])
            ->where('id', $sessionId)
            ->orWhere('session_uuid', $sessionId)
            ->firstOrFail();

        $accessResult = $this->accessControlService->canAccessSession($session, $user);

        return response()->json($accessResult);
    }

    /**
     * Get meeting link for a session (with access control)
     */
    public function getMeetingLink(Request $request, string $sessionId): JsonResponse
    {
        $user = $request->user();
        
        $session = TeachingSession::with(['teacher', 'student', 'subject'])
            ->where('id', $sessionId)
            ->orWhere('session_uuid', $sessionId)
            ->firstOrFail();

        $result = $this->accessControlService->getMeetingLink($session, $user);

        if (!$result['can_access']) {
            return response()->json($result, 403);
        }

        return response()->json($result);
    }

    /**
     * Redirect to meeting link (only if access is granted)
     */
    public function joinSession(Request $request, string $sessionId)
    {
        $user = $request->user();
        
        $session = TeachingSession::with(['teacher', 'student', 'subject'])
            ->where('id', $sessionId)
            ->orWhere('session_uuid', $sessionId)
            ->firstOrFail();

        $result = $this->accessControlService->getMeetingLink($session, $user);

        if (!$result['can_access']) {
            // Return to previous page with error message
            return redirect()->back()->with('error', $result['message']);
        }

        // Redirect to meeting link
        return redirect()->away($result['meeting_link']);
    }

    /**
     * Show session waiting room (when it's too early to join)
     */
    public function waitingRoom(Request $request, string $sessionId): Response
    {
        $user = $request->user();
        
        $session = TeachingSession::with(['teacher', 'student', 'subject'])
            ->where('id', $sessionId)
            ->orWhere('session_uuid', $sessionId)
            ->firstOrFail();

        $accessResult = $this->accessControlService->canAccessSession($session, $user);

        return Inertia::render('sessions/waiting-room', [
            'session' => [
                'id' => $session->id,
                'session_uuid' => $session->session_uuid,
                'subject_name' => $session->subject->name,
                'teacher_name' => $session->teacher->name,
                'student_name' => $session->student->name,
                'session_date' => $session->session_date->format('Y-m-d'),
                'start_time' => $session->start_time->format('H:i:s'),
                'end_time' => $session->end_time->format('H:i:s'),
                'status' => $session->status,
            ],
            'access' => $accessResult,
        ]);
    }

    /**
     * Get all active sessions for admin monitoring
     */
    public function getActiveSessionsForMonitoring(Request $request): JsonResponse
    {
        $user = $request->user();

        // Only admins can access monitoring
        if (!in_array($user->role, ['admin', 'super-admin'])) {
            return response()->json([
                'error' => 'Unauthorized. Admin access required for monitoring.',
            ], 403);
        }

        $sessions = $this->accessControlService->getActiveSessionsForMonitoring();

        return response()->json([
            'sessions' => $sessions,
            'total_count' => count($sessions),
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Admin monitoring dashboard
     */
    public function monitoringDashboard(Request $request): Response
    {
        $user = $request->user();

        // Only admins can access monitoring
        if (!in_array($user->role, ['admin', 'super-admin'])) {
            abort(403, 'Unauthorized. Admin access required for monitoring.');
        }

        $sessions = $this->accessControlService->getActiveSessionsForMonitoring();

        return Inertia::render('admin/monitoring/sessions', [
            'sessions' => $sessions,
            'total_count' => count($sessions),
        ]);
    }
}

