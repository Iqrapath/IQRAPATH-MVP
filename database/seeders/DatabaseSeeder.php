<?php

namespace Database\Seeders;

use App\Models\User;
// use Database\Seeders\TeacherWalletSeeder;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the seeders in order
        $this->call([
            SuperAdminSeeder::class,
            AdminSeeder::class,
            SubjectTemplatesSeeder::class, // Must run before TeacherSeeder
            TeacherSeeder::class,
            StudentSeeder::class, // Must run before TeacherReviewSeeder
            OngoingBookingSeeder::class,
            TodayOngoingSeeder::class,
            GuardianSeeder::class,
            TeacherAvailabilitySeeder::class,
            TeacherWalletSeeder::class,
            TeacherReviewSeeder::class, // Now students exist
            UnassignedUserSeeder::class, // Create unassigned users for testing
            UserSeeder::class, // This should run last to handle relationships
            BookingSeeder::class, // Create bookings with sessions
            TeachingSessionSeeder::class,
        //    TodayOngoingSeeder::class,
            NotificationTemplateSeeder::class,
            NotificationTriggerSeeder::class,
            UrgentActionSeeder::class,
            ScheduledNotificationSeeder::class,
            PayoutRequestSeeder::class, // Create test payout requests
        ]);
    }
}
