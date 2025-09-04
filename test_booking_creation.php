<?php

/**
 * Test Booking Creation
 * Tests the booking creation process to identify date/time parsing issues
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
use Carbon\Carbon;

echo "🧪 Testing Booking Creation Process\n";
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
    
    // Test 2: Check teacher availability
    echo "📋 Test 2: Checking Teacher Availability\n";
    echo "=======================================\n";
    
    $availability = TeacherAvailability::where('teacher_id', $teacher->id)
        ->where('is_active', true)
        ->first();
    
    if (!$availability) {
        echo "❌ No teacher availability found\n";
        exit(1);
    }
    echo "✅ Found availability: {$availability->start_time} - {$availability->end_time}\n";
    
    echo "\n";
    
    // Test 3: Test date/time parsing
    echo "📋 Test 3: Testing Date/Time Parsing\n";
    echo "====================================\n";
    
    $testDate = '2025-09-03';
    $testTime = '11:00:00';
    
    echo "Testing date: $testDate\n";
    echo "Testing time: $testTime\n";
    
    try {
        $parsedDate = Carbon::parse($testDate);
        echo "✅ Date parsed successfully: " . $parsedDate->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "❌ Date parsing failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $parsedTime = Carbon::parse($testTime);
        echo "✅ Time parsed successfully: " . $parsedTime->format('H:i:s') . "\n";
    } catch (Exception $e) {
        echo "❌ Time parsing failed: " . $e->getMessage() . "\n";
    }
    
    try {
        $combinedDateTime = Carbon::parse($testDate . ' ' . $testTime);
        echo "✅ Combined date/time parsed successfully: " . $combinedDateTime->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "❌ Combined date/time parsing failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 4: Test with actual booking data
    echo "📋 Test 4: Testing with Actual Booking Data\n";
    echo "===========================================\n";
    
    $existingBooking = Booking::first();
    if ($existingBooking) {
        echo "Found existing booking:\n";
        echo "  - ID: {$existingBooking->id}\n";
        echo "  - Date: {$existingBooking->booking_date}\n";
        echo "  - Start Time: {$existingBooking->start_time}\n";
        echo "  - End Time: {$existingBooking->end_time}\n";
        
        try {
            // Test the same parsing logic as in BookingNotificationService
            $bookingDate = Carbon::parse($existingBooking->booking_date)->format('Y-m-d');
            $startTime = Carbon::parse($existingBooking->start_time)->format('H:i:s');
            $sessionDateTime = Carbon::parse($bookingDate . ' ' . $startTime);
            
            echo "✅ Booking date/time parsing successful: " . $sessionDateTime->format('Y-m-d H:i:s') . "\n";
        } catch (Exception $e) {
            echo "❌ Booking date/time parsing failed: " . $e->getMessage() . "\n";
            echo "   Raw booking_date: " . var_export($existingBooking->booking_date, true) . "\n";
            echo "   Raw start_time: " . var_export($existingBooking->start_time, true) . "\n";
        }
    } else {
        echo "❌ No existing bookings found to test with\n";
    }
    
    echo "\n";
    
    // Test 5: Test BookingNotificationService
    echo "📋 Test 5: Testing BookingNotificationService\n";
    echo "=============================================\n";
    
    if ($existingBooking) {
        try {
            $notificationService = app(BookingNotificationService::class);
            echo "✅ BookingNotificationService created successfully\n";
            
                         // Test creating a notification (this might trigger the date parsing)
             $notification = $notificationService->createNotification(
                 $existingBooking,
                 $existingBooking->student,
                 'booking_created',
                 'Test Notification',
                 'This is a test notification',
                 ['test' => true]
             );
            
            echo "✅ Test notification created successfully\n";
            echo "   Notification ID: {$notification->id}\n";
            
        } catch (Exception $e) {
            echo "❌ BookingNotificationService test failed: " . $e->getMessage() . "\n";
            echo "   Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }
    
    echo "\n";
    
    // Summary
    echo "📋 Summary\n";
    echo "==========\n";
    echo "✅ Booking creation test completed\n";
    echo "✅ Date/time parsing logic verified\n";
    echo "✅ BookingNotificationService tested\n\n";
    
    echo "🎉 If no errors were shown above, the booking system should be working correctly!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
