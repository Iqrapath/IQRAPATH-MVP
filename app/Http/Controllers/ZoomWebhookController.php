<?php

namespace App\Http\Controllers;

use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ZoomWebhookController extends Controller
{
    /**
     * Handle Zoom webhook events.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        // Log the webhook for debugging
        Log::info('Zoom Webhook received', ['payload' => $payload]);
        
        // Verify the webhook is from Zoom
        if (!$this->verifyZoomWebhook($request)) {
            Log::warning('Invalid Zoom webhook signature');
            return response('Unauthorized', 401);
        }
        
        // Process the event based on type
        $event = $payload['event'] ?? null;
        
        if (!$event) {
            return response('No event specified', 400);
        }
        
        switch ($event) {
            case 'meeting.started':
                return $this->handleMeetingStarted($payload);
                
            case 'meeting.ended':
                return $this->handleMeetingEnded($payload);
                
            case 'meeting.participant_joined':
                return $this->handleParticipantJoined($payload);
                
            case 'meeting.participant_left':
                return $this->handleParticipantLeft($payload);
                
            default:
                // Ignore other event types
                return response('Event processed', 200);
        }
    }
    
    /**
     * Verify the webhook signature from Zoom.
     */
    protected function verifyZoomWebhook(Request $request): bool
    {
        // In production, implement proper signature verification
        // using the Zoom Webhook Secret Token
        
        // For now, just return true for development
        return true;
    }
    
    /**
     * Handle meeting started event.
     */
    protected function handleMeetingStarted(array $payload)
    {
        $meetingId = $payload['payload']['object']['id'] ?? null;
        
        if (!$meetingId) {
            return response('Invalid meeting ID', 400);
        }
        
        // Find the session with this Zoom meeting ID
        $session = TeachingSession::where('zoom_meeting_id', $meetingId)->first();
        
        if (!$session) {
            Log::warning('No session found for Zoom meeting ID: ' . $meetingId);
            return response('Session not found', 404);
        }
        
        // Update session status
        $session->status = 'in_progress';
        $session->save();
        
        return response('Meeting started event processed', 200);
    }
    
    /**
     * Handle meeting ended event.
     */
    protected function handleMeetingEnded(array $payload)
    {
        $meetingId = $payload['payload']['object']['id'] ?? null;
        
        if (!$meetingId) {
            return response('Invalid meeting ID', 400);
        }
        
        // Find the session with this Zoom meeting ID
        $session = TeachingSession::where('zoom_meeting_id', $meetingId)->first();
        
        if (!$session) {
            Log::warning('No session found for Zoom meeting ID: ' . $meetingId);
            return response('Session not found', 404);
        }
        
        // Update session status if both teacher and student were present
        if ($session->teacher_marked_present && $session->student_marked_present) {
            $session->status = 'completed';
            
            // Try to get the actual duration from Zoom
            $duration = $payload['payload']['object']['duration'] ?? null;
            if ($duration) {
                $session->actual_duration_minutes = $duration;
            } else {
                // Calculate based on our records
                $session->actual_duration_minutes = $session->calculateDuration();
            }
        } else {
            // If one of the participants didn't show up
            $session->status = 'no_show';
        }
        
        $session->save();
        
        return response('Meeting ended event processed', 200);
    }
    
    /**
     * Handle participant joined event.
     */
    protected function handleParticipantJoined(array $payload)
    {
        $meetingId = $payload['payload']['object']['id'] ?? null;
        $participant = $payload['payload']['object']['participant'] ?? null;
        
        if (!$meetingId || !$participant) {
            return response('Invalid meeting or participant data', 400);
        }
        
        // Find the session with this Zoom meeting ID
        $session = TeachingSession::where('zoom_meeting_id', $meetingId)->first();
        
        if (!$session) {
            Log::warning('No session found for Zoom meeting ID: ' . $meetingId);
            return response('Session not found', 404);
        }
        
        // Get participant email
        $email = $participant['email'] ?? null;
        
        if (!$email) {
            return response('No participant email provided', 400);
        }
        
        // Check if this is the teacher or student
        if ($email === $session->teacher->email) {
            $session->teacher_joined_at = now();
            $session->teacher_marked_present = true;
            $session->save();
        } elseif ($email === $session->student->email) {
            $session->student_joined_at = now();
            $session->student_marked_present = true;
            $session->save();
        }
        
        return response('Participant joined event processed', 200);
    }
    
    /**
     * Handle participant left event.
     */
    protected function handleParticipantLeft(array $payload)
    {
        $meetingId = $payload['payload']['object']['id'] ?? null;
        $participant = $payload['payload']['object']['participant'] ?? null;
        
        if (!$meetingId || !$participant) {
            return response('Invalid meeting or participant data', 400);
        }
        
        // Find the session with this Zoom meeting ID
        $session = TeachingSession::where('zoom_meeting_id', $meetingId)->first();
        
        if (!$session) {
            Log::warning('No session found for Zoom meeting ID: ' . $meetingId);
            return response('Session not found', 404);
        }
        
        // Get participant email
        $email = $participant['email'] ?? null;
        
        if (!$email) {
            return response('No participant email provided', 400);
        }
        
        // Check if this is the teacher or student
        if ($email === $session->teacher->email) {
            $session->teacher_left_at = now();
            $session->save();
        } elseif ($email === $session->student->email) {
            $session->student_left_at = now();
            $session->save();
        }
        
        return response('Participant left event processed', 200);
    }
} 