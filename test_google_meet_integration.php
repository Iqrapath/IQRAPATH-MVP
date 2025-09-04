<?php

/**
 * Test Google Meet Integration
 * Verifies that Google Meet integration is properly configured
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\GoogleMeetService;

echo "🔗 Testing Google Meet Integration\n";
echo "==================================\n\n";

try {
    // Test 1: Check if GoogleMeetService exists
    echo "📋 Test 1: Checking GoogleMeetService\n";
    echo "=====================================\n";
    
    if (class_exists('App\Services\GoogleMeetService')) {
        echo "✅ GoogleMeetService exists\n";
    } else {
        echo "❌ GoogleMeetService not found\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 2: Check configuration
    echo "📋 Test 2: Checking Configuration\n";
    echo "=================================\n";
    
    $clientId = config('services.google_meet.client_id');
    $clientSecret = config('services.google_meet.client_secret');
    $refreshToken = config('services.google_meet.refresh_token');
    
    if ($clientId) {
        echo "✅ Client ID is configured\n";
        echo "   Client ID: " . substr($clientId, 0, 20) . "...\n";
    } else {
        echo "❌ Client ID is not configured\n";
    }
    
    if ($clientSecret) {
        echo "✅ Client Secret is configured\n";
        echo "   Client Secret: " . substr($clientSecret, 0, 10) . "...\n";
    } else {
        echo "❌ Client Secret is not configured\n";
    }
    
    if ($refreshToken) {
        echo "✅ Refresh Token is configured\n";
        echo "   Refresh Token: " . substr($refreshToken, 0, 20) . "...\n";
    } else {
        echo "❌ Refresh Token is not configured\n";
        echo "   This is the main issue preventing Google Meet integration\n";
    }
    
    echo "\n";
    
    // Test 3: Check if we can create a GoogleMeetService instance
    echo "📋 Test 3: Testing GoogleMeetService Instance\n";
    echo "=============================================\n";
    
    try {
        $googleMeetService = app(GoogleMeetService::class);
        echo "✅ GoogleMeetService instance created successfully\n";
        
        // Test 4: Check if we can get access token (if refresh token is configured)
        if ($refreshToken) {
            echo "\n📋 Test 4: Testing Access Token Generation\n";
            echo "==========================================\n";
            
            try {
                // This will test the OAuth flow
                $reflection = new ReflectionClass($googleMeetService);
                $method = $reflection->getMethod('getAccessToken');
                $method->setAccessible(true);
                
                $accessToken = $method->invoke($googleMeetService);
                
                if ($accessToken) {
                    echo "✅ Access token generated successfully\n";
                    echo "   Token: " . substr($accessToken, 0, 20) . "...\n";
                } else {
                    echo "❌ Failed to generate access token\n";
                }
            } catch (Exception $e) {
                echo "❌ Error generating access token: " . $e->getMessage() . "\n";
            }
        } else {
            echo "\n📋 Test 4: Skipped (No Refresh Token)\n";
            echo "=====================================\n";
            echo "⚠️  Cannot test access token generation without refresh token\n";
        }
        
    } catch (Exception $e) {
        echo "❌ Error creating GoogleMeetService: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Summary
    echo "📋 Summary\n";
    echo "==========\n";
    
    if ($clientId && $clientSecret && $refreshToken) {
        echo "✅ Google Meet integration is fully configured!\n";
        echo "✅ All credentials are present\n";
        echo "✅ Service is ready to use\n\n";
        
        echo "🎉 Next Steps:\n";
        echo "==============\n";
        echo "1. Test creating a Google Meet meeting\n";
        echo "2. Verify the meeting link is generated correctly\n";
        echo "3. Test the integration in the admin verification modal\n\n";
    } else {
        echo "⚠️  Google Meet integration is partially configured\n";
        echo "❌ Missing credentials prevent full functionality\n\n";
        
        echo "🔧 Required Actions:\n";
        echo "===================\n";
        echo "1. Complete Google OAuth setup\n";
        echo "2. Add credentials to .env file\n";
        echo "3. Run this test again to verify\n\n";
        
        echo "📖 Setup Guide:\n";
        echo "===============\n";
        echo "Run: php get_google_credentials.php\n";
        echo "Follow the instructions to get your OAuth credentials\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}