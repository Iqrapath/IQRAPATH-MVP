<?php

/**
 * Get Google OAuth Credentials
 * Simple guide to get your Google OAuth client ID and secret
 */

echo "🔑 Google OAuth Credentials Guide\n";
echo "=================================\n\n";

echo "📋 Step 1: Go to Google Cloud Console\n";
echo "=====================================\n";
echo "1. Open: https://console.cloud.google.com/apis/credentials\n";
echo "2. Make sure you're in the correct project\n";
echo "3. Look for 'OAuth 2.0 Client IDs' section\n\n";

echo "📋 Step 2: Find Your Client ID\n";
echo "==============================\n";
echo "Your Client ID should look like this:\n";
echo "123456789012-abcdefghijklmnopqrstuvwxyz123456.apps.googleusercontent.com\n\n";

echo "📋 Step 3: Configure Redirect URIs\n";
echo "==================================\n";
echo "1. Click on your OAuth 2.0 Client ID\n";
echo "2. In 'Authorized redirect URIs', add these EXACT URLs:\n";
echo "   - http://localhost:8000/oauth/callback\n";
echo "   - urn:ietf:wg:oauth:2.0:oob\n";
echo "3. Click 'Save'\n";
echo "4. Wait 1-2 minutes for changes to take effect\n\n";

echo "📋 Step 4: Copy Your Credentials\n";
echo "===============================\n";
echo "From your OAuth client settings, copy:\n";
echo "- Client ID (the long string ending in .apps.googleusercontent.com)\n";
echo "- Client Secret (starts with GOCSPX-)\n\n";

echo "📋 Step 5: Test the Setup\n";
echo "========================\n";
echo "Once you have the correct credentials, run:\n";
echo "php google_oauth_diagnostic.php\n\n";

echo "⚠️  Important Notes:\n";
echo "===================\n";
echo "- Client ID must be the FULL string ending in .apps.googleusercontent.com\n";
echo "- Client Secret starts with GOCSPX-\n";
echo "- Redirect URIs must match exactly (no extra spaces)\n";
echo "- Changes in Google Cloud Console take 1-2 minutes to propagate\n\n";

echo "🔗 Quick Links:\n";
echo "==============\n";
echo "- Google Cloud Console: https://console.cloud.google.com/apis/credentials\n";
echo "- Google Calendar API: https://console.cloud.google.com/apis/library/calendar-json.googleapis.com\n\n";

echo "Need help? Check the GOOGLE_MEET_SETUP.md file for detailed instructions.\n";
