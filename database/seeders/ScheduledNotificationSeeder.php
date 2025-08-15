<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ScheduledNotification;
use App\Models\User;

class ScheduledNotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get an admin user to create the notifications
        $adminUser = User::whereIn('role', ['admin', 'super-admin'])->first();
        
        if (!$adminUser) {
            $this->command->error('No admin user found. Please create an admin user first.');
            return;
        }

        $notifications = [
            [
                'scheduled_date' => now()->addDays(1)->setTime(7, 0, 0), // Tomorrow at 7 AM
                'message' => 'Reminder: Class with Ustadh Yusuf in 1 hour',
                'target_audience' => 'All Scheduled Students',
                'frequency' => 'one-time',
                'status' => 'scheduled',
                'created_by' => $adminUser->id,
            ],
            [
                'scheduled_date' => now()->addDays(2)->setTime(18, 0, 0), // Day after tomorrow at 6 PM
                'message' => 'Weekly summary report is now available',
                'target_audience' => 'Guardians',
                'frequency' => 'weekly',
                'status' => 'scheduled',
                'created_by' => $adminUser->id,
            ],
            [
                'scheduled_date' => now()->addDays(5)->setTime(12, 0, 0), // 5 days from now at 12 PM
                'message' => 'System update tonight',
                'target_audience' => 'All Users',
                'frequency' => 'one-time',
                'status' => 'scheduled',
                'created_by' => $adminUser->id,
            ],
            [
                'scheduled_date' => now()->subDays(1)->setTime(8, 0, 0), // Yesterday at 8 AM (sent)
                'message' => 'Daily Quran reading reminder',
                'target_audience' => 'Students',
                'frequency' => 'daily',
                'status' => 'sent',
                'created_by' => $adminUser->id,
                'sent_at' => now()->subDays(1)->setTime(8, 5, 0),
            ],
            [
                'scheduled_date' => now()->subDays(2)->setTime(10, 0, 0), // 2 days ago at 10 AM (cancelled)
                'message' => 'Monthly progress report',
                'target_audience' => 'Guardians',
                'frequency' => 'monthly',
                'status' => 'cancelled',
                'created_by' => $adminUser->id,
                'cancelled_at' => now()->subDays(2)->setTime(9, 30, 0),
            ],
        ];

        foreach ($notifications as $notification) {
            ScheduledNotification::create($notification);
        }

        $this->command->info('Scheduled notifications seeded successfully!');
    }
}
