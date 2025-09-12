<?php

require_once 'vendor/autoload.php';

use App\Services\TeachingSessionMeetingService;
use App\Services\ZoomService;
use App\Services\GoogleMeetService;
use App\Models\TeachingSession;
use App\Models\User;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🧪 Detailed Meeting Creation Test\n";
echo "=================================\n\n";

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

    // Test Zoom service with detailed error handling
    echo "🔗 Testing Zoom Service in detail...\n";
    try {
        $zoomService = new ZoomService();
        
        // Check Zoom configuration
        $reflection = new ReflectionClass($zoomService);
        $apiKeyProperty = $reflection->getProperty('apiKey');
        $apiKeyProperty->setAccessible(true);
        $apiKey = $apiKeyProperty->getValue($zoomService);
        
        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientId = $clientIdProperty->getValue($zoomService);
        
        echo "   Zoom API Key: " . (empty($apiKey) ? '❌ Not configured' : '✅ Configured') . "\n";
        echo "   Zoom Client ID: " . (empty($clientId) ? '❌ Not configured' : '✅ Configured') . "\n";
        
        if (!empty($apiKey) && !empty($clientId)) {
            echo "   🔄 Attempting to create Zoom meeting...\n";
            try {
                $zoomMeeting = $zoomService->createMeeting($session, $session->teacher);
                echo "   ✅ Zoom meeting created successfully!\n";
                echo "   Meeting ID: " . ($zoomMeeting['id'] ?? 'N/A') . "\n";
                echo "   Join URL: " . ($zoomMeeting['join_url'] ?? 'N/A') . "\n";
            } catch (Exception $e) {
                echo "   ❌ Zoom meeting creation failed: " . $e->getMessage() . "\n";
                echo "   Error details: " . $e->getTraceAsString() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Zoom Service Error: " . $e->getMessage() . "\n";
    }

    // Test Google Meet service with detailed error handling
    echo "\n🔗 Testing Google Meet Service in detail...\n";
    try {
        $googleMeetService = new GoogleMeetService();
        
        // Check Google Meet configuration
        $reflection = new ReflectionClass($googleMeetService);
        $clientIdProperty = $reflection->getProperty('clientId');
        $clientIdProperty->setAccessible(true);
        $clientId = $clientIdProperty->getValue($googleMeetService);
        
        $refreshTokenProperty = $reflection->getProperty('refreshToken');
        $refreshTokenProperty->setAccessible(true);
        $refreshToken = $refreshTokenProperty->getValue($googleMeetService);
        
        echo "   Google Client ID: " . (empty($clientId) ? '❌ Not configured' : '✅ Configured') . "\n";
        echo "   Google Refresh Token: " . (empty($refreshToken) ? '❌ Not configured' : '✅ Configured') . "\n";
        
        if (!empty($clientId) && !empty($refreshToken)) {
            echo "   🔄 Attempting to get access token...\n";
            try {
                // Use reflection to call protected method
                $reflection = new ReflectionClass($googleMeetService);
                $method = $reflection->getMethod('getAccessToken');
                $method->setAccessible(true);
                $accessToken = $method->invoke($googleMeetService);
                echo "   ✅ Access token obtained: " . substr($accessToken, 0, 20) . "...\n";
                
                echo "   🔄 Attempting to create Google Meet...\n";
                try {
                    $googleMeet = $googleMeetService->createMeeting($session, $session->teacher);
                    echo "   ✅ Google Meet created successfully!\n";
                    echo "   Event ID: " . ($googleMeet['id'] ?? 'N/A') . "\n";
                    echo "   Meet Link: " . ($googleMeet['conferenceData']['entryPoints'][0]['uri'] ?? 'N/A') . "\n";
                } catch (Exception $e) {
                    echo "   ❌ Google Meet creation failed: " . $e->getMessage() . "\n";
                    echo "   Error details: " . $e->getTraceAsString() . "\n";
                }
            } catch (Exception $e) {
                echo "   ❌ Access token failed: " . $e->getMessage() . "\n";
            }
        }
        
    } catch (Exception $e) {
        echo "   ❌ Google Meet Service Error: " . $e->getMessage() . "\n";
    }

    // Check final session state
    $session->refresh();
    echo "\n🔍 Final Session State:\n";
    echo "   Meeting Platform: " . ($session->meeting_platform ?? 'None') . "\n";
    echo "   Meeting Link: " . ($session->meeting_link ?? 'None') . "\n";
    echo "   Zoom Join URL: " . ($session->zoom_join_url ?? 'None') . "\n";
    echo "   Google Meet Link: " . ($session->google_meet_link ?? 'None') . "\n";

} catch (Exception $e) {
    echo "❌ General Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎉 Detailed test completed!\n";
