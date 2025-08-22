<?php

namespace App\Services;

use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

class ZoomService
{
    protected $apiKey;
    protected $apiSecret;
    protected $clientId;
    protected $clientSecret;
    protected $accountId;
    protected $baseUrl = 'https://api.zoom.us/v2/';
    protected $jwt;

    public function __construct()
    {
        $this->apiKey = config('services.zoom.key');
        $this->apiSecret = config('services.zoom.secret');
        $this->clientId = config('services.zoom.client_id');
        $this->clientSecret = config('services.zoom.client_secret');
        $this->accountId = config('services.zoom.account_id');
        // Only generate legacy JWT if a secret is configured
        $this->jwt = $this->generateJWT();
    }

    /**
     * Generate JWT token for Zoom API authentication
     */
    protected function generateJWT()
    {
        $key = $this->apiKey;
        $secret = $this->apiSecret;
        // If secret is not configured, return empty token to avoid HMAC error
        if (empty($key) || empty($secret) || !is_string($secret)) {
            return '';
        }
        $token = [
            'iss' => $key,
            'exp' => time() + 60 * 60, // 1 hour expiration
        ];
        
        return \Firebase\JWT\JWT::encode($token, $secret, 'HS256');
    }

    /**
     * Get an access token, preferring Server-to-Server OAuth when configured.
     */
    protected function getAccessToken(): string
    {
        // Prefer Server-to-Server OAuth when account_id & client credentials are set
        if (!empty($this->clientId) && !empty($this->clientSecret) && !empty($this->accountId)) {
            return $this->getS2SToken();
        }
        // Fallback to legacy JWT (not recommended by Zoom)
        return $this->jwt;
    }

    /**
     * Retrieve and cache Server-to-Server OAuth token.
     */
    protected function getS2SToken(): string
    {
        return cache()->remember('zoom_s2s_token', 55 * 60, function () {
            $basic = base64_encode($this->clientId . ':' . $this->clientSecret);
            $http = Http::withHeaders([
                'Authorization' => 'Basic ' . $basic,
            ]);
            if (app()->environment('local')) {
                // Disable SSL verification only in local to avoid cURL 60 errors
                $http = $http->withOptions(['verify' => false]);
            }
            $response = $http->asForm()->post('https://zoom.us/oauth/token', [
                'grant_type' => 'account_credentials',
                'account_id' => $this->accountId,
            ]);

            if (!$response->successful()) {
                Log::error('Zoom OAuth token error: ' . $response->body());
                throw new Exception('Failed to obtain Zoom access token');
            }
            $data = $response->json();
            return $data['access_token'] ?? '';
        });
    }

    /**
     * Create a Zoom meeting for a teaching session
     */
    public function createMeeting(TeachingSession $session, User $teacher)
    {
        try {
            // Format date and time for Zoom API
            $startTime = $session->session_date->format('Y-m-d') . 'T' . 
                         $session->start_time->format('H:i:s');
            
            // Calculate duration in minutes
            $startTimeObj = \Carbon\Carbon::parse($session->start_time);
            $endTimeObj = \Carbon\Carbon::parse($session->end_time);
            $durationMinutes = $endTimeObj->diffInMinutes($startTimeObj);
            
            // Default to 30 minutes if calculation fails
            if ($durationMinutes <= 0) {
                $durationMinutes = 30;
            }
            
            // Create meeting payload
            $data = [
                'topic' => 'Session: ' . $session->subject->name,
                'type' => 2, // Scheduled meeting
                'start_time' => $startTime,
                'duration' => $durationMinutes,
                'timezone' => config('app.timezone'),
                'password' => $this->generatePassword(),
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'waiting_room' => true,
                    'audio' => 'both',
                    'auto_recording' => 'none',
                ],
            ];
            
            // If teacher has Zoom user ID, use it
            $http = Http::withToken($this->getAccessToken());
            if (app()->environment('local')) {
                // Disable SSL verification only in local dev to avoid cURL 60 errors
                $http = $http->withOptions(['verify' => false]);
            }
            if ($teacher->zoom_user_id) {
                $response = $http->post($this->baseUrl . 'users/' . $teacher->zoom_user_id . '/meetings', $data);
            } else {
                // Use default account
                $response = $http->post($this->baseUrl . 'users/me/meetings', $data);
            }
            
            if ($response->successful()) {
                $meetingData = $response->json();
                
                // Update session with Zoom details
                $session->update([
                    'meeting_platform' => 'zoom',
                    'zoom_meeting_id' => $meetingData['id'],
                    'zoom_host_id' => $meetingData['host_id'],
                    'zoom_join_url' => $meetingData['join_url'],
                    'zoom_start_url' => $meetingData['start_url'],
                    'zoom_password' => $meetingData['password'],
                    'meeting_link' => $meetingData['join_url'],
                    'meeting_password' => $meetingData['password'],
                ]);
                
                return $meetingData;
            } else {
                Log::error('Zoom API Error: ' . $response->body());
                throw new Exception('Failed to create Zoom meeting: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Zoom Meeting Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create an ad-hoc Zoom meeting not tied to TeachingSession (for verification calls)
     *
     * @param string $topic
     * @param \DateTimeInterface $startAt
     * @param int $durationMinutes
     * @param string|null $hostZoomUserId
     * @return array{id:mixed,host_id:string,join_url:string,start_url:string,password:string}
     * @throws Exception
     */
    public function createAdhocMeeting(string $topic, \DateTimeInterface $startAt, int $durationMinutes = 30, ?string $hostZoomUserId = null): array
    {
        try {
            $data = [
                'topic' => $topic,
                'type' => 2,
                'start_time' => $startAt->format('Y-m-d\TH:i:s'),
                'duration' => max(15, $durationMinutes),
                'timezone' => config('app.timezone'),
                'password' => $this->generatePassword(),
                'settings' => [
                    'host_video' => true,
                    'participant_video' => true,
                    'join_before_host' => false,
                    'mute_upon_entry' => true,
                    'waiting_room' => true,
                    'audio' => 'both',
                    'auto_recording' => 'none',
                ],
            ];

            $url = $this->baseUrl . 'users/' . ($hostZoomUserId ?: 'me') . '/meetings';
            $http = Http::withToken($this->getAccessToken());
            if (app()->environment('local')) {
                $http = $http->withOptions(['verify' => false]);
            }
            $response = $http->post($url, $data);

            if (!$response->successful()) {
                Log::error('Zoom API Error: ' . $response->body());
                throw new Exception('Failed to create Zoom meeting: ' . $response->body());
            }

            $meetingData = $response->json();
            return [
                'id' => $meetingData['id'] ?? null,
                'host_id' => $meetingData['host_id'] ?? '',
                'join_url' => $meetingData['join_url'] ?? '',
                'start_url' => $meetingData['start_url'] ?? '',
                'password' => $meetingData['password'] ?? '',
            ];
        } catch (Exception $e) {
            Log::error('Zoom Adhoc Meeting Creation Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generate a random password for Zoom meetings
     */
    protected function generatePassword($length = 10)
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    /**
     * Get meeting details from Zoom
     */
    public function getMeeting($meetingId)
    {
        try {
            $response = Http::withToken($this->jwt)
                ->get($this->baseUrl . 'meetings/' . $meetingId);
                
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Zoom API Error: ' . $response->body());
                throw new Exception('Failed to get Zoom meeting: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Zoom Meeting Retrieval Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Get meeting participants from Zoom
     */
    public function getMeetingParticipants($meetingId)
    {
        try {
            $response = Http::withToken($this->jwt)
                ->get($this->baseUrl . 'report/meetings/' . $meetingId . '/participants');
                
            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Zoom API Error: ' . $response->body());
                throw new Exception('Failed to get Zoom meeting participants: ' . $response->body());
            }
        } catch (Exception $e) {
            Log::error('Zoom Participants Retrieval Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update attendance data for a session from Zoom
     */
    public function updateAttendanceData(TeachingSession $session)
    {
        if (!$session->zoom_meeting_id) {
            throw new Exception('No Zoom meeting ID associated with this session');
        }
        
        try {
            $participants = $this->getMeetingParticipants($session->zoom_meeting_id);
            
            $session->attendance_data = $participants;
            
            // Check if teacher and student were present
            $teacherEmail = $session->teacher->email;
            $studentEmail = $session->student->email;
            
            $teacherPresent = false;
            $studentPresent = false;
            
            foreach ($participants['participants'] as $participant) {
                if ($participant['email'] === $teacherEmail) {
                    $teacherPresent = true;
                    $session->teacher_joined_at = $participant['join_time'];
                    $session->teacher_left_at = $participant['leave_time'];
                }
                
                if ($participant['email'] === $studentEmail) {
                    $studentPresent = true;
                    $session->student_joined_at = $participant['join_time'];
                    $session->student_left_at = $participant['leave_time'];
                }
            }
            
            $session->teacher_marked_present = $teacherPresent;
            $session->student_marked_present = $studentPresent;
            
            $session->save();
            
            return $participants;
        } catch (Exception $e) {
            Log::error('Zoom Attendance Update Error: ' . $e->getMessage());
            throw $e;
        }
    }
} 