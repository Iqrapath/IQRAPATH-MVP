<?php

/**
 * Google OAuth Diagnostic Tool
 * Helps diagnose OAuth client configuration issues
 */

echo "🔍 Google OAuth Diagnostic Tool\n";
echo "===============================\n\n";

echo "This tool will help you diagnose OAuth client issues.\n\n";

echo "📋 Step 1: Verify Google Cloud Console Setup\n";
echo "============================================\n";
echo "1. Go to: https://console.cloud.google.com/apis/credentials\n";
echo "2. Make sure you're in the correct project\n";
echo "3. Look for your OAuth 2.0 Client ID\n";
echo "4. Click on it to view details\n\n";

echo "📋 Step 2: Check Your Client ID\n";
echo "===============================\n";
echo "In the OAuth client details, you should see:\n";
echo "- Client ID: A long string ending in .apps.googleusercontent.com\n";
echo "- Client Secret: A string starting with GOCSPX-\n\n";

echo "Enter your Client ID (the one ending in .apps.googleusercontent.com): ";
$clientId = trim(fgets(STDIN));

if (empty($clientId)) {
    echo "❌ Error: Client ID is required.\n";
    exit(1);
}

echo "\nEnter your Client Secret (starts with GOCSPX-): ";
$clientSecret = trim(fgets(STDIN));

if (empty($clientSecret)) {
    echo "❌ Error: Client Secret is required.\n";
    exit(1);
}

echo "\n📋 Step 3: Verify Redirect URIs\n";
echo "===============================\n";
echo "In your OAuth client settings, make sure you have these redirect URIs:\n";
echo "- http://localhost:8000/oauth/callback\n";
echo "- urn:ietf:wg:oauth:2.0:oob\n\n";

echo "Do you have these redirect URIs configured? (y/n): ";
$hasRedirectUris = trim(fgets(STDIN));

if (strtolower($hasRedirectUris) !== 'y') {
    echo "\n❌ You need to add the redirect URIs first!\n";
    echo "1. Go to your OAuth client settings\n";
    echo "2. Add these URIs to 'Authorized redirect URIs':\n";
    echo "   - http://localhost:8000/oauth/callback\n";
    echo "   - urn:ietf:wg:oauth:2.0:oob\n";
    echo "3. Save and wait 1-2 minutes\n";
    echo "4. Run this script again\n";
    exit(1);
}

echo "\n📋 Step 4: Test OAuth Configuration\n";
echo "===================================\n";

// Test with the out-of-band redirect URI first (simpler)
echo "Testing with out-of-band redirect URI...\n";

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob',
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/calendar',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

echo "\n🔗 Authorization URL (out-of-band):\n";
echo $authUrl . "\n\n";

echo "📋 Instructions:\n";
echo "1. Copy the URL above and paste it in your browser\n";
echo "2. Sign in with your Google account\n";
echo "3. Grant permissions\n";
echo "4. You'll see a page with an authorization code\n";
echo "5. Copy the code and paste it here\n\n";

echo "Enter the authorization code: ";
$authCode = trim(fgets(STDIN));

if (empty($authCode)) {
    echo "❌ Error: Authorization code is required.\n";
    exit(1);
}

echo "\n🔄 Exchanging authorization code for refresh token...\n";

// Exchange authorization code for refresh token
$postData = http_build_query([
    'client_id' => $clientId,
    'client_secret' => $clientSecret,
    'code' => $authCode,
    'grant_type' => 'authorization_code',
    'redirect_uri' => 'urn:ietf:wg:oauth:2.0:oob'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$response = file_get_contents('https://oauth2.googleapis.com/token', false, $context);
$data = json_decode($response, true);

if (isset($data['refresh_token'])) {
    echo "✅ Success! Your refresh token has been generated.\n\n";
    echo "📝 Add these to your .env file:\n";
    echo "===============================\n";
    echo "GOOGLE_MEET_CLIENT_ID={$clientId}\n";
    echo "GOOGLE_MEET_CLIENT_SECRET={$clientSecret}\n";
    echo "GOOGLE_MEET_REFRESH_TOKEN={$data['refresh_token']}\n";
    echo "GOOGLE_MEET_CALENDAR_ID=primary\n";
    echo "GOOGLE_MEET_WEBHOOK_SECRET=your_webhook_secret_here\n\n";
    
    echo "🎉 Google Meet integration is now configured!\n";
    echo "You can test it by trying to create a Google Meet link in the admin verification modal.\n";
} else {
    echo "❌ Error: Failed to generate refresh token.\n";
    echo "Response: " . $response . "\n\n";
    
    if (isset($data['error'])) {
        echo "Error details:\n";
        echo "- Error: " . $data['error'] . "\n";
        if (isset($data['error_description'])) {
            echo "- Description: " . $data['error_description'] . "\n";
        }
    }
    
    echo "\nCommon solutions:\n";
    echo "1. Make sure your Client ID and Secret are correct\n";
    echo "2. Ensure redirect URIs are properly configured\n";
    echo "3. Wait 1-2 minutes after making changes in Google Cloud Console\n";
    echo "4. Try using a different browser or incognito mode\n";
}

echo "\n";
