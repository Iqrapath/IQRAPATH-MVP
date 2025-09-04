<?php

namespace App\Http\Controllers;

use App\Models\TeachingSession;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class GoogleMeetWebhookController extends Controller
{
    /**
     * Handle Google Calendar webhook events.
     */
    public function handle(Request $request)
    {
        $payload = $request->all();
        
        // Log the webhook for debugging
        Log::info('Google Calendar Webhook received', ['payload' => $payload]);
        
        // Verify the webhook is from Google
        if (!$this->verifyGoogleWebhook($request)) {
            Log::warning('Invalid Google webhook signature');
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
     * Verify the webhook signature from Google.
     */
    protected function verifyGoogleWebhook(Request $request): bool
    {
        // In production, implement proper signature verification
        // using the Google Webhook Secret Token
        
        // For now, just return true for development
        return true;
    }
    
    /**
     * Handle meeting started event.
     */
    protected function handleMeetingStarted(array $payload)
    {
        $eventId = $payload['payload']['object']['id'] ?? null;
        
        if (!$eventId) {
            return response('Invalid event ID', 400);
        }
        
        // Find the session with this Google Calendar event ID
        $session = TeachingSession::where('google_calendar_event_id', $eventId)->first();
        
        if (!$session) {
            Log::warning('No session found for Google Calendar event ID: ' . $eventId);
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
        $eventId = $payload['payload']['object']['id'] ?? null;
        
        if (!$eventId) {
            return response('Invalid event ID', 400);
        }
        
        // Find the session with this Google Calendar event ID
        $session = TeachingSession::where('google_calendar_event_id', $eventId)->first();
        
        if (!$session) {
            Log::warning('No session found for Google Calendar event ID: ' . $eventId);
            return response('Session not found', 404);
        }
        
        // Update session status if both teacher and student were present
        if ($session->teacher_marked_present && $session->student_marked_present) {
            $session->status = 'completed';
            
            // Try to get the actual duration from the event
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
     * Note: Google Meet doesn't provide participant events via Calendar API
     */
    protected function handleParticipantJoined(array $payload)
    {
        // Google Meet doesn't provide participant join/leave events via Calendar API
        // This would require Google Meet API or manual tracking
        Log::info('Google Meet participant joined event not available via Calendar API');
        
        return response('Participant joined event processed', 200);
    }
    
    /**
     * Handle participant left event.
     * Note: Google Meet doesn't provide participant events via Calendar API
     */
    protected function handleParticipantLeft(array $payload)
    {
        // Google Meet doesn't provide participant join/leave events via Calendar API
        // This would require Google Meet API or manual tracking
        Log::info('Google Meet participant left event not available via Calendar API');
        
        return response('Participant left event processed', 200);
    }
    
    /**
     * Handle Google Calendar push notifications
     */
    public function handlePushNotification(Request $request)
    {
        $payload = $request->all();
        
        Log::info('Google Calendar Push Notification received', ['payload' => $payload]);
        
        // Verify the webhook
        if (!$this->verifyGoogleWebhook($request)) {
            Log::warning('Invalid Google push notification signature');
            return response('Unauthorized', 401);
        }
        
        // Process the notification
        $eventType = $payload['eventType'] ?? null;
        $eventId = $payload['eventId'] ?? null;
        
        if (!$eventType || !$eventId) {
            return response('Invalid notification data', 400);
        }
        
        // Find the session with this Google Calendar event ID
        $session = TeachingSession::where('google_calendar_event_id', $eventId)->first();
        
        if (!$session) {
            Log::warning('No session found for Google Calendar event ID: ' . $eventId);
            return response('Session not found', 404);
        }
        
        switch ($eventType) {
            case 'created':
                Log::info('Google Meet event created', ['session_id' => $session->id]);
                break;
                
            case 'updated':
                Log::info('Google Meet event updated', ['session_id' => $session->id]);
                break;
                
            case 'deleted':
                Log::info('Google Meet event deleted', ['session_id' => $session->id]);
                // Mark session as cancelled if event was deleted
                $session->status = 'cancelled';
                $session->save();
                break;
                
            default:
                Log::info('Unknown Google Calendar event type: ' . $eventType);
        }
        
        return response('Push notification processed', 200);
    }
}
