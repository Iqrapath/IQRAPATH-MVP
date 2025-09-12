<?php

namespace App\Services;

use App\Models\TeachingSession;
use App\Models\User;
use App\Services\ZoomService;
use App\Services\GoogleMeetService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TeachingSessionMeetingService
{
    public function __construct(
        private ZoomService $zoomService,
        private GoogleMeetService $googleMeetService
    ) {}

    /**
     * Create meeting links for a teaching session
     */
    public function createMeetingLinks(TeachingSession $session, User $teacher): array
    {
        $meetingData = [
            'meeting_platform' => 'zoom', // Default to Zoom
            'meeting_link' => null,
            'meeting_password' => null,
            'zoom_meeting_id' => null,
            'zoom_host_id' => null,
            'zoom_join_url' => null,
            'zoom_start_url' => null,
            'zoom_password' => null,
            'google_meet_id' => null,
            'google_meet_link' => null,
            'google_calendar_event_id' => null,
        ];

        try {
            // Create Zoom meeting using adhoc method (primary)
            $zoomMeeting = $this->createZoomMeeting($session, $teacher);
            if ($zoomMeeting) {
                $meetingData = array_merge($meetingData, $zoomMeeting);
                // Set Zoom as primary meeting link
                $meetingData['meeting_link'] = $meetingData['zoom_join_url'];
                $meetingData['meeting_platform'] = 'zoom';
            }

            // Try Google Meet as backup (but don't let it override Zoom)
            try {
                $googleMeet = $this->createGoogleMeet($session, $teacher);
                if ($googleMeet) {
                    $meetingData = array_merge($meetingData, $googleMeet);
                }
            } catch (\Exception $e) {
                Log::warning('Google Meet creation failed, continuing with Zoom only', [
                    'session_id' => $session->id,
                    'error' => $e->getMessage(),
                ]);
            }

            Log::info('Meeting links created for session', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'meeting_platform' => $meetingData['meeting_platform'],
                'has_zoom' => !empty($meetingData['zoom_join_url']),
                'has_google_meet' => !empty($meetingData['google_meet_link']),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create meeting links for session', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $meetingData;
    }

    /**
     * Create Zoom meeting for the session using adhoc method
     */
    private function createZoomMeeting(TeachingSession $session, User $teacher): ?array
    {
        try {
            Log::info('Attempting to create Zoom adhoc meeting', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);
            
            // Create topic
            $topic = 'Session: ' . ($session->subject->template->name ?? 'Unknown Subject') . ' with ' . $teacher->name;
            
            // Create start time
            $startAt = \Carbon\Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'));
            
            // Calculate duration
            $startTimeObj = \Carbon\Carbon::parse($session->start_time);
            $endTimeObj = \Carbon\Carbon::parse($session->end_time);
            $durationMinutes = $endTimeObj->diffInMinutes($startTimeObj);
            
            if ($durationMinutes <= 0) {
                $durationMinutes = 30;
            }
            
            $zoomMeeting = $this->zoomService->createAdhocMeeting($topic, $startAt, $durationMinutes);
            
            Log::info('Zoom adhoc meeting creation result', [
                'session_id' => $session->id,
                'result' => $zoomMeeting,
            ]);
            
            if ($zoomMeeting && isset($zoomMeeting['id'])) {
                return [
                    'meeting_platform' => 'zoom',
                    'zoom_meeting_id' => $zoomMeeting['id'],
                    'zoom_host_id' => $zoomMeeting['host_id'] ?? null,
                    'zoom_join_url' => $zoomMeeting['join_url'] ?? null,
                    'zoom_start_url' => $zoomMeeting['start_url'] ?? null,
                    'zoom_password' => $zoomMeeting['password'] ?? null,
                    'meeting_password' => $zoomMeeting['password'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Zoom adhoc meeting', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Create Google Meet for the session
     */
    private function createGoogleMeet(TeachingSession $session, User $teacher): ?array
    {
        try {
            Log::info('Attempting to create Google Meet', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
            ]);
            
            $googleMeet = $this->googleMeetService->createMeeting($session, $teacher);
            
            Log::info('Google Meet creation result', [
                'session_id' => $session->id,
                'result' => $googleMeet,
            ]);
            
            if ($googleMeet && isset($googleMeet['conferenceData']['entryPoints'][0]['uri'])) {
                return [
                    'google_meet_id' => $this->extractMeetId($googleMeet['conferenceData']['entryPoints'][0]['uri']),
                    'google_meet_link' => $googleMeet['conferenceData']['entryPoints'][0]['uri'],
                    'google_calendar_event_id' => $googleMeet['id'] ?? null,
                ];
            }
        } catch (\Exception $e) {
            Log::error('Failed to create Google Meet', [
                'session_id' => $session->id,
                'teacher_id' => $teacher->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return null;
    }

    /**
     * Extract meeting ID from Google Meet link
     */
    private function extractMeetId(string $meetLink): ?string
    {
        if (preg_match('/meet\.google\.com\/([a-zA-Z0-9-]+)/', $meetLink, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Generate a secure meeting password
     */
    private function generatePassword(): string
    {
        return Str::random(8);
    }

    /**
     * Update teaching session with meeting data
     */
    public function updateSessionWithMeetingData(TeachingSession $session, array $meetingData): bool
    {
        try {
            Log::info('Updating session with meeting data', [
                'session_id' => $session->id,
                'meeting_data' => $meetingData,
            ]);
            
            // Update session with meeting data
            $session->update($meetingData);
            
            // Refresh to verify the update
            $session->refresh();
            
            Log::info('Session updated successfully', [
                'session_id' => $session->id,
                'meeting_platform' => $session->meeting_platform,
                'meeting_link' => $session->meeting_link,
                'zoom_join_url' => $session->zoom_join_url,
                'has_meeting_link' => !empty($session->meeting_link),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Failed to update session with meeting data', [
                'session_id' => $session->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}
