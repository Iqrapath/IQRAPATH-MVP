<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\TeachingSession;
use App\Models\User;
use App\Services\TeachingSessionMeetingService;

class TestMeetingCreation extends Command
{
    protected $signature = 'test:meeting-creation {session_id}';
    protected $description = 'Test meeting creation for a teaching session';

    public function handle(TeachingSessionMeetingService $meetingService)
    {
        $sessionId = $this->argument('session_id');
        
        $session = TeachingSession::find($sessionId);
        if (!$session) {
            $this->error("Teaching session with ID {$sessionId} not found.");
            return 1;
        }

        $teacher = $session->teacher;
        if (!$teacher) {
            $this->error("Teacher not found for session {$sessionId}.");
            return 1;
        }

        $this->info("Testing meeting creation for session: {$session->session_uuid}");
        $this->info("Teacher: {$teacher->name}");
        $subjectName = $session->subject->template->name ?? 'Unknown';
        $this->info("Subject: {$subjectName}");
        $this->info("Date: {$session->session_date} at {$session->start_time}");

        try {
            $meetingData = $meetingService->createMeetingLinks($session, $teacher);
            
            $this->info("\nMeeting data created:");
            $this->table(
                ['Field', 'Value'],
                [
                    ['Meeting Platform', $meetingData['meeting_platform'] ?? 'None'],
                    ['Meeting Link', $meetingData['meeting_link'] ?? 'None'],
                    ['Zoom Meeting ID', $meetingData['zoom_meeting_id'] ?? 'None'],
                    ['Zoom Join URL', $meetingData['zoom_join_url'] ?? 'None'],
                    ['Google Meet Link', $meetingData['google_meet_link'] ?? 'None'],
                    ['Google Calendar Event ID', $meetingData['google_calendar_event_id'] ?? 'None'],
                ]
            );

            // Update the session with meeting data
            $updated = $meetingService->updateSessionWithMeetingData($session, $meetingData);
            
            if ($updated) {
                $this->info("✅ Session updated successfully with meeting data!");
            } else {
                $this->error("❌ Failed to update session with meeting data.");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error creating meeting links: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
