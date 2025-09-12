<?php

require_once 'vendor/autoload.php';

use App\Services\ZoomService;
use App\Models\TeachingSession;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Testing Zoom Adhoc Meeting Creation\n";
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

    // Test Zoom adhoc meeting creation
    echo "🔗 Testing Zoom Adhoc Meeting Creation...\n";
    
    $zoomService = new ZoomService();
    
    // Create a topic
    $topic = 'Session: ' . ($session->subject->template->name ?? 'Unknown Subject') . ' with ' . $session->teacher->name;
    
    // Create start time
    $startAt = Carbon::parse($session->session_date->format('Y-m-d') . ' ' . $session->start_time->format('H:i:s'));
    
    // Calculate duration
    $startTimeObj = Carbon::parse($session->start_time);
    $endTimeObj = Carbon::parse($session->end_time);
    $durationMinutes = $endTimeObj->diffInMinutes($startTimeObj);
    
    if ($durationMinutes <= 0) {
        $durationMinutes = 30;
    }
    
    echo "   Topic: {$topic}\n";
    echo "   Start Time: {$startAt}\n";
    echo "   Duration: {$durationMinutes} minutes\n\n";
    
    try {
        $meeting = $zoomService->createAdhocMeeting($topic, $startAt, $durationMinutes);
        
        echo "✅ Zoom adhoc meeting created successfully!\n";
        echo "   Meeting ID: " . ($meeting['id'] ?? 'N/A') . "\n";
        echo "   Host ID: " . ($meeting['host_id'] ?? 'N/A') . "\n";
        echo "   Join URL: " . ($meeting['join_url'] ?? 'N/A') . "\n";
        echo "   Start URL: " . ($meeting['start_url'] ?? 'N/A') . "\n";
        echo "   Password: " . ($meeting['password'] ?? 'N/A') . "\n\n";
        
        // Update the session with the meeting data
        echo "🔄 Updating session with meeting data...\n";
        $session->update([
            'meeting_platform' => 'zoom',
            'meeting_link' => $meeting['join_url'],
            'meeting_password' => $meeting['password'],
            'zoom_meeting_id' => $meeting['id'],
            'zoom_host_id' => $meeting['host_id'],
            'zoom_join_url' => $meeting['join_url'],
            'zoom_start_url' => $meeting['start_url'],
            'zoom_password' => $meeting['password'],
        ]);
        
        echo "✅ Session updated successfully!\n\n";
        
        // Display final session data
        $session->refresh();
        echo "📊 Final Session Data:\n";
        echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
        echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
        echo "   Zoom Meeting ID: " . ($session->zoom_meeting_id ?? 'None') . "\n";
        echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
        echo "   Zoom Start URL: " . ($session->zoom_start_url ?? 'None') . "\n";
        echo "   Zoom Password: " . ($session->zoom_password ?? 'None') . "\n";
        
    } catch (Exception $e) {
        echo "❌ Zoom adhoc meeting creation failed: " . $e->getMessage() . "\n";
        echo "Error details: " . $e->getTraceAsString() . "\n";
    }

} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Zoom adhoc meeting test completed!\n";
