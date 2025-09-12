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

echo "🧪 Testing Meeting Creation Service (Simple)\n";
echo "============================================\n\n";

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

    // Check current session data
    echo "🔍 Current Session Data:\n";
    echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
    echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
    echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
    echo "   Google Meet Link: " . ($session->google_meet_link ?? 'None') . "\n\n";

    // Test Zoom service directly
    echo "🔗 Testing Zoom Service...\n";
    try {
        $zoomService = new ZoomService();
        echo "   ✅ ZoomService instantiated successfully\n";
        
        // Check if Zoom credentials are configured
        $reflection = new ReflectionClass($zoomService);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $apiKey = $apiKeyProperty->getValue($zoomService);
        
        if (empty($apiKey)) {
            echo "   ⚠️  Zoom API key not configured\n";
        } else {
            echo "   ✅ Zoom API key is configured\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Zoom Service Error: " . $e->getMessage() . "\n";
    }

    // Test Google Meet service directly
    echo "\n🔗 Testing Google Meet Service...\n";
    try {
        $googleMeetService = new GoogleMeetService();
        echo "   ✅ GoogleMeetService instantiated successfully\n";
        
        // Check if Google Meet credentials are configured
        $reflection = new ReflectionClass($googleMeetService);
        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientId = $clientIdProperty->getValue($googleMeetService);
        
        if (empty($clientId)) {
            echo "   ⚠️  Google Meet client ID not configured\n";
        } else {
            echo "   ✅ Google Meet client ID is configured\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Google Meet Service Error: " . $e->getMessage() . "\n";
    }

    // Test the meeting service
    echo "\n🔗 Testing TeachingSessionMeetingService...\n";
    try {
        $zoomService = new ZoomService();
        $googleMeetService = new GoogleMeetService();
        $meetingService = new TeachingSessionMeetingService($zoomService, $googleMeetService);
        echo "   ✅ TeachingSessionMeetingService instantiated successfully\n";
        
        // Try to create meeting links
        echo "   🔄 Creating meeting links...\n";
        $meetingData = $meetingService->createMeetingLinks($session, $session->teacher);
        
        echo "\n📊 Meeting Data Created:\n";
        echo "   Meeting Platform: " . ($meetingData['meeting_platform'] ?? 'None') . "\n";
        echo "   Meeting Link: " . ($meetingData['meeting_link'] ?? 'None') . "\n";
        echo "   Zoom Meeting ID: " . ($meetingData['zoom_meeting_id'] ?? 'None') . "\n";
        echo "   Zoom Join URL: " . ($meetingData['zoom_join_url'] ?? 'None') . "\n";
        echo "   Google Meet Link: " . ($meetingData['google_meet_link'] ?? 'None') . "\n";
        echo "   Google Calendar Event ID: " . ($meetingData['google_calendar_event_id'] ?? 'None') . "\n\n";

        // Check updated session data
        $session->refresh();
        echo "🔍 Updated Session Data:\n";
        echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
        echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
        echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
        echo "   Google Meet Link: " . ($session->google_meet_link ?? 'None') . "\n";
        
    } catch (Exception $e) {
        echo "   ❌ TeachingSessionMeetingService Error: " . $e->getMessage() . "\n";
        echo "   Stack trace: " . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Test completed!\n";
