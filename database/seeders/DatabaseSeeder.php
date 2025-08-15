<?php

namespace Database\Seeders;

use App\Models\User;
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
            TeacherSeeder::class,
            StudentSeeder::class,
            GuardianSeeder::class,
            UnassignedUserSeeder::class, // Create unassigned users for testing
            UserSeeder::class, // This should run last to handle relationships
            TeachingSessionSeeder::class,
            NotificationTemplateSeeder::class,
            NotificationTriggerSeeder::class,
            UrgentActionSeeder::class,
            ScheduledNotificationSeeder::class,
            CompletedClassesSeeder::class,
        ]);
    }
}
