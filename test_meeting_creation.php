<?php

require_once 'vendor/autoload.php';

use App\Services\TeachingSessionMeetingService;
use App\Services\ZoomService;
use App\Services\GoogleMeetService;
use App\Models\TeachingSession;
use App\Models\User;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Meeting Creation Service\n";
echo "=====================================\n\n";

try {
    // Get a recent teaching session
    $session = TeachingSession::with(['teacher', 'subject.template'])
        ->where('status', 'scheduled')
        ->latest()
        ->first();

    if (!$session) {
        echo "❌ No teaching sessions found. Please create a booking first.\n";
        exit(1);
    }

    echo "📋 Session Details:\n";
    echo "   ID: {$session->id}\n";
    echo "   UUID: {$session->session_uuid}\n";
    echo "   Teacher: {$session->teacher->name}\n";
    echo "   Subject: " . ($session->subject->template->name ?? 'Unknown') . "\n";
    echo "   Date: {$session->session_date} at {$session->start_time}\n\n";

    // Initialize services
    $zoomService = new ZoomService();
    $googleMeetService = new GoogleMeetService();
    $meetingService = new TeachingSessionMeetingService($zoomService, $googleMeetService);

    echo "🔗 Creating meeting links...\n";
    
    // Create meeting links
    $meetingData = $meetingService->createMeetingLinks($session, $session->teacher);
    
    echo "\n📊 Meeting Data Created:\n";
    echo "   Meeting Platform: " . ($meetingData['meeting_platform'] ?? 'None') . "\n";
    echo "   Meeting Link: " . ($meetingData['meeting_link'] ?? 'None') . "\n";
    echo "   Zoom Meeting ID: " . ($meetingData['zoom_meeting_id'] ?? 'None') . "\n";
    echo "   Zoom Join URL: " . ($meetingData['zoom_join_url'] ?? 'None') . "\n";
    echo "   Google Meet Link: " . ($meetingData['google_meet_link'] ?? 'None') . "\n";
    echo "   Google Calendar Event ID: " . ($meetingData['google_calendar_event_id'] ?? 'None') . "\n\n";

    // Update session
    echo "💾 Updating session with meeting data...\n";
    $updated = $meetingService->updateSessionWithMeetingData($session, $meetingData);
    
    if ($updated) {
        echo "✅ Session updated successfully!\n\n";
        
        // Refresh session to get updated data
        $session->refresh();
        
        echo "🔍 Updated Session Data:\n";
        echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
        echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
        echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
        echo "   Google Meet Link: " . ($session->google_meet_link ?? 'None') . "\n";
        
    } else {
        echo "❌ Failed to update session with meeting data.\n";
    }

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Test completed!\n";
