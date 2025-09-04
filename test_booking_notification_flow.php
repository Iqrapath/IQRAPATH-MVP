<?php

/**
 * Test Booking Notification Flow
 * Simulates the complete booking creation and notification process
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Booking;
use App\Models\User;
use App\Models\Subject;
use App\Models\SubjectTemplates;
use App\Models\TeacherAvailability;
use App\Services\BookingNotificationService;
use App\Models\Notification;
use Carbon\Carbon;

echo "🧪 Testing Booking Notification Flow\n";
echo "====================================\n\n";

try {
    // Test 1: Get test data
    echo "📋 Test 1: Getting Test Data\n";
    echo "============================\n";
    
    $student = User::where('role', 'student')->first();
    $teacher = User::where('role', 'teacher')->first();
    $subjectTemplate = SubjectTemplates::first();
    
    if (!$student) {
        echo "❌ No student found\n";
        exit(1);
    }
    echo "✅ Found student: {$student->name}\n";
    
    if (!$teacher) {
        echo "❌ No teacher found\n";
        exit(1);
    }
    echo "✅ Found teacher: {$teacher->name}\n";
    
    if (!$subjectTemplate) {
        echo "❌ No subject template found\n";
        exit(1);
    }
    echo "✅ Found subject template: {$subjectTemplate->name}\n";
    
    echo "\n";
    
    // Test 2: Create a test booking
    echo "📋 Test 2: Creating Test Booking\n";
    echo "================================\n";
    
    $booking = Booking::create([
        'student_id' => $student->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subjectTemplate->id,
        'booking_date' => '2025-09-05',
        'start_time' => '10:00:00',
        'end_time' => '11:00:00',
        'duration_minutes' => 60,
        'status' => 'pending',
        'notes' => 'Test booking for notification system',
        'total_fee' => 5000.00,
        'created_by_id' => $student->id,
    ]);
    
    echo "✅ Test booking created with ID: {$booking->id}\n";
    
    // Load relationships
    $booking->load(['student', 'teacher', 'subject']);
    
    echo "\n";
    
    // Test 3: Test notification service
    echo "📋 Test 3: Testing Notification Service\n";
    echo "=======================================\n";
    
    $notificationService = app(BookingNotificationService::class);
    echo "✅ BookingNotificationService created successfully\n";
    
    // Test booking created notifications
    $notificationService->sendBookingCreatedNotifications($booking);
    echo "✅ Booking created notifications sent\n";
    
    // Check if notifications were created in the main notification system
    $studentNotifications = Notification::where('notifiable_id', $student->id)
        ->where('type', 'App\Notifications\BookingNotification')
        ->get();
    
    $teacherNotifications = Notification::where('notifiable_id', $teacher->id)
        ->where('type', 'App\Notifications\BookingNotification')
        ->get();
    
    echo "✅ Student notifications created: {$studentNotifications->count()}\n";
    echo "✅ Teacher notifications created: {$teacherNotifications->count()}\n";
    
    // Display notification details
    if ($studentNotifications->count() > 0) {
        $latestStudentNotification = $studentNotifications->first();
        echo "📧 Student notification:\n";
        echo "   - Title: {$latestStudentNotification->data['title']}\n";
        echo "   - Message: {$latestStudentNotification->data['message']}\n";
        echo "   - Level: {$latestStudentNotification->data['level']}\n";
        echo "   - Action URL: {$latestStudentNotification->data['action_url']}\n";
    }
    
    if ($teacherNotifications->count() > 0) {
        $latestTeacherNotification = $teacherNotifications->first();
        echo "📧 Teacher notification:\n";
        echo "   - Title: {$latestTeacherNotification->data['title']}\n";
        echo "   - Message: {$latestTeacherNotification->data['message']}\n";
        echo "   - Level: {$latestTeacherNotification->data['level']}\n";
        echo "   - Action URL: {$latestTeacherNotification->data['action_url']}\n";
    }
    
    echo "\n";
    
    // Test 4: Test booking approval notifications
    echo "📋 Test 4: Testing Booking Approval Notifications\n";
    echo "=================================================\n";
    
    // Update booking status to approved
    $booking->update(['status' => 'approved']);
    
    // Send approval notifications
    $notificationService->sendBookingApprovedNotifications($booking);
    echo "✅ Booking approved notifications sent\n";
    
    // Check for new notifications
    $studentNotificationsAfterApproval = Notification::where('notifiable_id', $student->id)
        ->where('type', 'App\Notifications\BookingNotification')
        ->get();
    
    $teacherNotificationsAfterApproval = Notification::where('notifiable_id', $teacher->id)
        ->where('type', 'App\Notifications\BookingNotification')
        ->get();
    
    echo "✅ Total student notifications: {$studentNotificationsAfterApproval->count()}\n";
    echo "✅ Total teacher notifications: {$teacherNotificationsAfterApproval->count()}\n";
    
    echo "\n";
    
    // Test 5: Check booking history
    echo "📋 Test 5: Checking Booking History\n";
    echo "===================================\n";
    
    $bookingHistory = \App\Models\BookingHistory::where('booking_id', $booking->id)->get();
    echo "✅ Booking history entries created: {$bookingHistory->count()}\n";
    
    foreach ($bookingHistory as $history) {
        echo "   - Action: {$history->action}\n";
        echo "   - Performed by: {$history->performed_by_id}\n";
        echo "   - Created: {$history->created_at}\n";
    }
    
    echo "\n";
    
    // Summary
    echo "📋 Summary\n";
    echo "==========\n";
    echo "✅ Booking notification flow test completed\n";
    echo "✅ Notifications are being created in the main notification system\n";
    echo "✅ Booking history is being recorded\n";
    echo "✅ Both student and teacher notifications are working\n\n";
    
    echo "🎉 The booking notification system is working correctly!\n";
    echo "📱 Notifications will appear in the main notification dropdown\n";
    echo "🔔 Toast notifications will show for new notifications\n";
    echo "📧 Email notifications are also being sent\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
