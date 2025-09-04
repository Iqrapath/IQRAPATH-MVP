<?php

/**
 * Quick Google OAuth Refresh Token Generator
 * This bypasses the redirect URI issue by using a direct approach
 */

echo "🔑 Quick Google OAuth Refresh Token Generator\n";
echo "============================================\n\n";

// Your credentials
$clientId = "235454609820-u5on4mrn41u2frqm2fhaa9r6jl8mr5ab.apps.googleusercontent.com";
$clientSecret = "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";

echo "📋 Step 1: Update Google Cloud Console\n";
echo "=====================================\n";
echo "1. Go to: https://console.cloud.google.com/apis/credentials\n";
echo "2. Click on your OAuth 2.0 Client ID\n";
echo "3. In 'Authorized redirect URIs', add these EXACT URLs:\n";
echo "   - http://localhost:8000/oauth/callback\n";
echo "   - http://localhost:8000\n";
echo "   - urn:ietf:wg:oauth:2.0:oob\n";
echo "4. Save the changes\n\n";

echo "📋 Step 2: Generate Authorization URL\n";
echo "====================================\n";
echo "Copy and paste this URL in your browser:\n\n";

$authUrl = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => $clientId,
    'redirect_uri' => 'http://localhost:8000/oauth/callback',
    'response_type' => 'code',
    'scope' => 'https://www.googleapis.com/auth/calendar',
    'access_type' => 'offline',
    'prompt' => 'consent'
]);

echo $authUrl . "\n\n";

echo "📋 Step 3: Get Authorization Code\n";
echo "=================================\n";
echo "1. Click the URL above\n";
echo "2. Sign in with your Google account\n";
echo "3. Grant permissions\n";
echo "4. You'll be redirected to a page that shows an error (this is normal)\n";
echo "5. Look at the URL in your browser - it will contain 'code=' followed by a long string\n";
echo "6. Copy everything after 'code=' until the next '&' or end of URL\n\n";

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
    echo "- Redirect URI mismatch (make sure you added all 3 URIs above)\n";
}

echo "\n";
