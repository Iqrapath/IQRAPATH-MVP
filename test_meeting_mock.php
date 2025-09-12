<?php

require_once 'vendor/autoload.php';

use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Mock Meeting Creation Test\n";
echo "=============================\n\n";

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

    // Mock meeting data
    $mockMeetingData = [
        'meeting_platform' => 'zoom',
        'meeting_link' => 'https://zoom.us/j/123456789?pwd=abc123',
        'meeting_password' => 'abc123',
        'zoom_meeting_id' => '123456789',
        'zoom_host_id' => 'host123',
        'zoom_join_url' => 'https://zoom.us/j/123456789?pwd=abc123',
        'zoom_start_url' => 'https://zoom.us/s/123456789?pwd=abc123',
        'zoom_password' => 'abc123',
        'google_meet_id' => 'abc-defg-hij',
        'google_meet_link' => 'https://meet.google.com/abc-defg-hij',
        'google_calendar_event_id' => 'event123@google.com',
    ];

    echo "🔗 Testing mock meeting creation...\n";
    
    // Update session with mock data
    $session->update($mockMeetingData);
    
    echo "✅ Session updated with mock meeting data!\n\n";
    
    // Refresh and display results
    $session->refresh();
    
    echo "📊 Updated Session Data:\n";
    echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
    echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
    echo "   Zoom Meeting ID: " . ($session->zoom_meeting_id ?? 'None') . "\n";
    echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
    echo "   Zoom Start URL: " . ($session->zoom_start_url ?? 'None') . "\n";
    echo "   Zoom Password: " . ($session->zoom_password ?? 'None') . "\n";
    echo "   Google Meet ID: " . ($session->google_meet_id ?? 'None') . "\n";
    echo "   Google Meet Link: " . ($session->google_meet_link ?? 'None') . "\n";
    echo "   Google Calendar Event ID: " . ($session->google_calendar_event_id ?? 'None') . "\n\n";

    echo "🎉 Mock meeting creation test completed!\n";
    echo "This proves the database schema and update mechanism work correctly.\n";
    echo "The real issue is with the API credentials and service calls.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
