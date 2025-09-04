<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Notification;

echo "🔍 Verifying Booking Notifications\n";
echo "==================================\n\n";

try {
    $notifications = Notification::where('type', 'App\Notifications\BookingNotification')->get();
    
    echo "Total booking notifications found: {$notifications->count()}\n\n";
    
    if ($notifications->count() > 0) {
        echo "Notification Details:\n";
        echo "====================\n";
        
        foreach ($notifications as $notification) {
            echo "ID: {$notification->id}\n";
            echo "User ID: {$notification->notifiable_id}\n";
            echo "Title: {$notification->data['title']}\n";
            echo "Message: {$notification->data['message']}\n";
            echo "Level: {$notification->data['level']}\n";
            echo "Created: {$notification->created_at}\n";
            echo "Read: " . ($notification->read_at ? 'Yes' : 'No') . "\n";
            echo "---\n";
        }
    } else {
        echo "❌ No booking notifications found\n";
    }
    
    echo "\n✅ Verification complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
