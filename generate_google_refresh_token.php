<?php

/**
 * Google OAuth Refresh Token Generator
 * 
 * This script helps you generate a refresh token for Google Meet integration.
 * Run this with: php generate_google_refresh_token.php
 */

echo "🔑 Google OAuth Refresh Token Generator\n";
echo "=====================================\n\n";

// Pre-filled credentials (you can edit these directly in the file)
$clientId = "235454609820-u5on4mrn41u2frqm2fhaa9r6jl8mr5ab.apps.googleusercontent.com";
$clientSecret = "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";

echo "Using credentials:\n";
echo "Client ID: " . $clientId . "\n";
echo "Client Secret: " . $clientSecret . "\n\n";

if (empty($clientId) || empty($clientSecret)) {
    echo "❌ Error: Both Client ID and Client Secret are required.\n";
    exit(1);
}

echo "\n📋 Step-by-Step Instructions:\n";
echo "============================\n\n";

echo "1. Open your browser and go to this URL:\n";
echo "   https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => 'http://localhost:8000/oauth/callback',
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/calendar',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

echo "\n\n2. Sign in with your Google account\n";
echo "3. Grant permissions for Calendar access\n";
echo "4. Copy the authorization code from the page\n";
echo "5. Paste it below:\n\n";

// For Windows compatibility, we'll use a simple input method
echo "Enter the authorization code: ";
$authCode = trim(fgets(STDIN));

if (empty($authCode)) {
    echo "❌ Error: Authorization code is required.\n";
    exit(1);
}

echo "\n🔄 Exchanging authorization code for refresh token...\n";

// Exchange authorization code for refresh token
$response = file_get_contents('https://oauth2.googleapis.com/token', false, stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $authCode,
            'grant_type' => 'authorization_code',
            'redirect_uri' => 'http://localhost:8000/oauth/callback'
        ])
    ]
]));

$data = json_decode($response, true);

if (isset($data['refresh_token'])) {
    echo "✅ Success! Your refresh token has been generated.\n\n";
    echo "📝 Add this to your .env file:\n";
    echo "==============================\n";
    echo "GOOGLE_MEET_CLIENT_ID={$clientId}\n";
    echo "GOOGLE_MEET_CLIENT_SECRET={$clientSecret}\n";
    echo "GOOGLE_MEET_REFRESH_TOKEN={$data['refresh_token']}\n";
    echo "GOOGLE_MEET_CALENDAR_ID=primary\n";
    echo "GOOGLE_MEET_WEBHOOK_SECRET=your_webhook_secret_here\n\n";
    
    echo "🎉 You can now test Google Meet integration!\n";
} else {
    echo "❌ Error: Failed to generate refresh token.\n";
    echo "Response: " . $response . "\n";
    echo "\nCommon issues:\n";
    echo "- Authorization code expired (try again)\n";
    echo "- Client ID/Secret incorrect\n";
    echo "- Redirect URI mismatch\n";
}

echo "\n";
