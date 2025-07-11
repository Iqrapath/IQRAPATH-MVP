<?php

namespace App\Http\Controllers;

use App\Models\TeachingSession;
use App\Services\ZoomService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class SessionAttendanceController extends Controller
{
    use AuthorizesRequests;
    
    /**
     * Mark teacher as present in a session.
     */
    public function teacherJoin(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('joinAsTeacher', $session);
        
        $session->teacher_joined_at = now();
        $session->teacher_marked_present = true;
        
        if ($session->status === 'scheduled') {
            $session->status = 'in_progress';
        }
        
        $session->save();
        
        // If this is a Zoom session, redirect to the start URL
        if ($session->meeting_platform === 'zoom' && $session->zoom_start_url) {
            return redirect()->away($session->zoom_start_url);
        }
        
        return redirect()->back()->with('success', 'You have joined the session as a teacher.');
    }
    
    /**
     * Mark student as present in a session.
     */
    public function studentJoin(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('joinAsStudent', $session);
        
        $session->student_joined_at = now();
        $session->student_marked_present = true;
        $session->save();
        
        // If this is a Zoom session, redirect to the join URL
        if ($session->meeting_platform === 'zoom' && $session->zoom_join_url) {
            return redirect()->away($session->zoom_join_url);
        }
        
        return redirect()->back()->with('success', 'You have joined the session as a student.');
    }
    
    /**
     * Mark teacher as left the session.
     */
    public function teacherLeave(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('joinAsTeacher', $session);
        
        $session->teacher_left_at = now();
        
        // If both teacher and student have joined and left, mark as completed
        if ($session->student_marked_present && $session->student_left_at) {
            $session->status = 'completed';
            
            // Calculate actual duration
            if ($session->teacher_joined_at && $session->teacher_left_at) {
                $session->actual_duration_minutes = $session->calculateDuration();
            }
        }
        
        $session->save();
        
        return redirect()->back()->with('success', 'You have left the session.');
    }
    
    /**
     * Mark student as left the session.
     */
    public function studentLeave(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('joinAsStudent', $session);
        
        $session->student_left_at = now();
        
        // If both teacher and student have joined and left, mark as completed
        if ($session->teacher_marked_present && $session->teacher_left_at) {
            $session->status = 'completed';
            
            // Calculate actual duration if not already set
            if (!$session->actual_duration_minutes && $session->teacher_joined_at && $session->teacher_left_at) {
                $session->actual_duration_minutes = $session->calculateDuration();
            }
        }
        
        $session->save();
        
        return redirect()->back()->with('success', 'You have left the session.');
    }
    
    /**
     * Manually update attendance data from Zoom.
     */
    public function updateZoomAttendance(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('manageSession', $session);
        
        try {
            $zoomService = app(ZoomService::class);
            $zoomService->updateAttendanceData($session);
            
            return redirect()->back()->with('success', 'Attendance data updated from Zoom.');
        } catch (\Exception $e) {
            Log::error('Failed to update Zoom attendance: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Failed to update attendance data: ' . $e->getMessage());
        }
    }
    
    /**
     * Mark a session as completed manually.
     */
    public function markCompleted(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('manageSession', $session);
        
        $validated = $request->validate([
            'actual_duration_minutes' => 'required|integer|min:1',
            'teacher_notes' => 'nullable|string',
        ]);
        
        $session->status = 'completed';
        $session->actual_duration_minutes = $validated['actual_duration_minutes'];
        
        if (isset($validated['teacher_notes'])) {
            $session->teacher_notes = $validated['teacher_notes'];
        }
        
        $session->save();
        
        return redirect()->back()->with('success', 'Session marked as completed.');
    }
    
    /**
     * Mark a session as cancelled.
     */
    public function markCancelled(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('manageSession', $session);
        
        $validated = $request->validate([
            'cancellation_reason' => 'nullable|string',
        ]);
        
        $session->status = 'cancelled';
        
        if (isset($validated['cancellation_reason'])) {
            $session->teacher_notes = 'Cancellation reason: ' . $validated['cancellation_reason'];
        }
        
        $session->save();
        
        return redirect()->back()->with('success', 'Session marked as cancelled.');
    }
    
    /**
     * Mark a session as no-show.
     */
    public function markNoShow(Request $request, TeachingSession $session): RedirectResponse
    {
        $this->authorize('manageSession', $session);
        
        $validated = $request->validate([
            'no_show_notes' => 'nullable|string',
            'no_show_party' => 'required|in:teacher,student,both',
        ]);
        
        $session->status = 'no_show';
        
        $notes = 'No-show: ' . $validated['no_show_party'];
        if (isset($validated['no_show_notes'])) {
            $notes .= ' - ' . $validated['no_show_notes'];
        }
        
        $session->teacher_notes = $notes;
        $session->save();
        
        return redirect()->back()->with('success', 'Session marked as no-show.');
    }
} 