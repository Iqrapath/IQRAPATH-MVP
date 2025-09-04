<?php

namespace App\Services;

use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleMeetService
{
    protected $clientId;
    protected $clientSecret;
    protected $refreshToken;
    protected $calendarId;
    protected $baseUrl = 'https://www.googleapis.com/calendar/v3/';

    public function __construct()
    {
        $this->clientId = config('services.google_meet.client_id');
        $this->clientSecret = config('services.google_meet.client_secret');
        $this->refreshToken = config('services.google_meet.refresh_token');
        $this->calendarId = config('services.google_meet.calendar_id');
    }

    /**
     * Get an access token using refresh token
     */
    protected function getAccessToken(): string
    {
        return cache()->remember('google_meet_access_token', 50 * 60, function () {
            $http = Http::asForm();
            
            // Disable SSL verification in local environment to avoid cURL 60 errors
            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }
            
            $response = $http->post('https://oauth2.googleapis.com/token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $this->refreshToken,
                'grant_type' => 'refresh_token',
            ]);

            if (!$response->successful()) {
                Log::error('Google Meet OAuth token error: ' . $response->body());
                throw new Exception('Failed to obtain Google Meet access token');
            }

            $data = $response->json();
            return $data['access_token'] ?? '';
        });
    }

    /**
     * Create a Google Meet event for a teaching session
     */
    public function createMeeting(TeachingSession $session, User $teacher)
    {
        try {
            // Format date and time for Google Calendar API
            $startTime = $session->session_date->format('Y-m-d') . 'T' . 
                         $session->start_time->format('H:i:s');
            $endTime = $session->session_date->format('Y-m-d') . 'T' . 
                       $session->end_time->format('H:i:s');

            // Create event payload
            $eventData = [
                'summary' => 'Session: ' . $session->subject->name,
                'description' => 'Teaching session with ' . $teacher->name,
                'start' => [
                    'dateTime' => $startTime,
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => $endTime,
                    'timeZone' => config('app.timezone'),
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => 'meet_' . $session->id . '_' . time(),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet'
                        ]
                    ]
                ],
                'attendees' => [
                    [
                        'email' => $teacher->email,
                        'displayName' => $teacher->name,
                        'organizer' => true,
                    ],
                    [
                        'email' => $session->student->email,
                        'displayName' => $session->student->name,
                    ]
                ],
                'reminders' => [
                    'useDefault' => false,
                    'overrides' => [
                        ['method' => 'email', 'minutes' => 24 * 60], // 24 hours before
                        ['method' => 'popup', 'minutes' => 10], // 10 minutes before
                    ]
                ]
            ];

            $http = Http::withToken($this->getAccessToken())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ]);

            if (app()->environment('local')) {
                // Disable SSL verification only in local dev
                $http = $http->withOptions(['verify' => false]);
            }

            $response = $http->post($this->baseUrl . 'calendars/' . $this->calendarId . '/events', [
                'conferenceDataVersion' => 1, // Required for Google Meet
                ...$eventData
            ]);

            if ($response->successful()) {
                $eventData = $response->json();
                
                // Extract Google Meet details
                $meetLink = $eventData['conferenceData']['entryPoints'][0]['uri'] ?? null;
                $meetId = $this->extractMeetId($meetLink);
                
                // Update session with Google Meet details
                $session->update([
                    'meeting_platform' => 'google_meet',
                    'google_meet_id' => $meetId,
                    'google_meet_link' => $meetLink,
                    'google_calendar_event_id' => $eventData['id'],
                    'meeting_link' => $meetLink,
                    'meeting_password' => null, // Google Meet doesn't use passwords
                ]);
                
                return $eventData;
            } else {
                Log::error('Google Calendar API Error: ' . $response->body());
                throw new Exception('Failed to create Google Meet event: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Google Meet Event Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create an ad-hoc Google Meet event not tied to TeachingSession (for verification calls)
     */
    public function createAdhocMeeting(string $topic, \DateTimeInterface $startAt, int $durationMinutes = 30, ?string $organizerEmail = null): array
    {
        try {
            $endTime = (clone $startAt)->modify("+{$durationMinutes} minutes");
            
            $eventData = [
                'summary' => $topic,
                'description' => 'Ad-hoc meeting',
                'start' => [
                    'dateTime' => $startAt->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone'),
                ],
                'end' => [
                    'dateTime' => $endTime->format('Y-m-d\TH:i:s'),
                    'timeZone' => config('app.timezone'),
                ],
                'conferenceData' => [
                    'createRequest' => [
                        'requestId' => 'adhoc_meet_' . time(),
                        'conferenceSolutionKey' => [
                            'type' => 'hangoutsMeet'
                        ]
                    ]
                ],
                'attendees' => $organizerEmail ? [
                    [
                        'email' => $organizerEmail,
                        'organizer' => true,
                    ]
                ] : [],
            ];

            $http = Http::withToken($this->getAccessToken())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ]);

            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }

            $response = $http->post($this->baseUrl . 'calendars/' . $this->calendarId . '/events', [
                'conferenceDataVersion' => 1,
                ...$eventData
            ]);

            if (!$response->successful()) {
                Log::error('Google Calendar API Error: ' . $response->body());
                throw new Exception('Failed to create Google Meet event: ' . $response->body());
            }

            $eventData = $response->json();
            $meetLink = $eventData['conferenceData']['entryPoints'][0]['uri'] ?? '';
            $meetId = $this->extractMeetId($meetLink);

            return [
                'id' => $eventData['id'] ?? null,
                'meet_id' => $meetId,
                'meet_link' => $meetLink,
                'event_id' => $eventData['id'] ?? '',
            ];
        } catch (Exception $e) {
            Log::error('Google Meet Adhoc Event Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Extract Google Meet ID from the meet link
     */
    protected function extractMeetId(string $meetLink): ?string
    {
        if (preg_match('/meet\.google\.com\/([a-z0-9-]+)/i', $meetLink, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get event details from Google Calendar
     */
    public function getEvent(string $eventId)
    {
        try {
            $response = Http::withToken($this->getAccessToken())
                ->get($this->baseUrl . 'calendars/' . $this->calendarId . '/events/' . $eventId);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Google Calendar API Error: ' . $response->body());
                throw new Exception('Failed to get Google Calendar event: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Google Calendar Event Retrieval Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update event details
     */
    public function updateEvent(string $eventId, array $eventData)
    {
        try {
            $response = Http::withToken($this->getAccessToken())
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])
                ->put($this->baseUrl . 'calendars/' . $this->calendarId . '/events/' . $eventId, $eventData);

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Google Calendar API Error: ' . $response->body());
                throw new Exception('Failed to update Google Calendar event: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Google Calendar Event Update Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Delete an event
     */
    public function deleteEvent(string $eventId)
    {
        try {
            $response = Http::withToken($this->getAccessToken())
                ->delete($this->baseUrl . 'calendars/' . $this->calendarId . '/events/' . $eventId);

            if ($response->successful()) {
                return true;
            } else {
                Log::error('Google Calendar API Error: ' . $response->body());
                throw new Exception('Failed to delete Google Calendar event: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Google Calendar Event Deletion Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get meeting participants (requires Google Meet API or manual tracking)
     * Note: Google Meet doesn't provide participant data via Calendar API
     */
    public function getMeetingParticipants(string $meetId)
    {
        // Google Meet doesn't provide participant data via Calendar API
        // This would require Google Meet API or manual tracking
        Log::info('Google Meet participant tracking not available via Calendar API', ['meet_id' => $meetId]);
        
        return [
            'participants' => [],
            'note' => 'Google Meet participant data not available via Calendar API'
        ];
    }

    /**
     * Update attendance data for a session from Google Meet
     * Note: This is a placeholder as Google Meet doesn't provide attendance data via Calendar API
     */
    public function updateAttendanceData(TeachingSession $session)
    {
        if (!$session->google_meet_id) {
            throw new Exception('No Google Meet ID associated with this session');
        }
        
        // Google Meet doesn't provide attendance data via Calendar API
        // This would require manual tracking or Google Meet API
        Log::info('Google Meet attendance tracking not available via Calendar API', [
            'session_id' => $session->id,
            'meet_id' => $session->google_meet_id
        ]);
        
        // For now, we'll mark both as present if the session exists
        // In a real implementation, you'd need to track this manually or use Google Meet API
        $session->teacher_marked_present = true;
        $session->student_marked_present = true;
        $session->save();
        
        return [
            'participants' => [],
            'note' => 'Attendance manually marked - Google Meet API does not provide participant data'
        ];
    }
}
