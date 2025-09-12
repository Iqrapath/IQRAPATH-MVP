<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🔧 Environment Configuration Test\n";
echo "=================================\n\n";

// Check Zoom configuration
echo "📋 Zoom Configuration:\n";
echo "   ZOOM_API_KEY: " . (env('ZOOM_API_KEY') ? '✅ Set' : '❌ Not set') . "\n";
echo "   ZOOM_API_SECRET: " . (env('ZOOM_API_SECRET') ? '✅ Set' : '❌ Not set') . "\n";
echo "   ZOOM_CLIENT_ID: " . (env('ZOOM_CLIENT_ID') ? '✅ Set' : '❌ Not set') . "\n";
echo "   ZOOM_CLIENT_SECRET: " . (env('ZOOM_CLIENT_SECRET') ? '✅ Set' : '❌ Not set') . "\n";
echo "   ZOOM_ACCOUNT_ID: " . (env('ZOOM_ACCOUNT_ID') ? '✅ Set' : '❌ Not set') . "\n\n";

// Check Google Meet configuration
echo "📋 Google Meet Configuration:\n";
echo "   GOOGLE_MEET_CLIENT_ID: " . (env('GOOGLE_MEET_CLIENT_ID') ? '✅ Set' : '❌ Not set') . "\n";
echo "   GOOGLE_MEET_CLIENT_SECRET: " . (env('GOOGLE_MEET_CLIENT_SECRET') ? '✅ Set' : '❌ Not set') . "\n";
echo "   GOOGLE_MEET_REFRESH_TOKEN: " . (env('GOOGLE_MEET_REFRESH_TOKEN') ? '✅ Set' : '❌ Not set') . "\n";
echo "   GOOGLE_MEET_CALENDAR_ID: " . (env('GOOGLE_MEET_CALENDAR_ID') ?: 'primary') . "\n\n";

// Check config values
echo "📋 Config Values:\n";
echo "   Zoom Key: " . (config('services.zoom.key') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Zoom Secret: " . (config('services.zoom.secret') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Zoom Client ID: " . (config('services.zoom.client_id') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Zoom Client Secret: " . (config('services.zoom.client_secret') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Zoom Account ID: " . (config('services.zoom.account_id') ? '✅ Set' : '❌ Not set') . "\n\n";

echo "📋 Google Meet Config Values:\n";
echo "   Google Client ID: " . (config('services.google_meet.client_id') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Google Client Secret: " . (config('services.google_meet.client_secret') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Google Refresh Token: " . (config('services.google_meet.refresh_token') ? '✅ Set' : '❌ Not set') . "\n";
echo "   Google Calendar ID: " . (config('services.google_meet.calendar_id') ?: 'primary') . "\n\n";

echo "🎉 Configuration test completed!\n";
