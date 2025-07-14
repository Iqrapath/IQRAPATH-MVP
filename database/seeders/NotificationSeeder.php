<?php

namespace Database\Seeders;

use App\Models\Notification;
use App\Models\NotificationRecipient;
use App\Models\User;
use Illuminate\Database\Seeder;
use Faker\Factory as FakerFactory;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get users by role for targeted notifications
        $adminUsers = User::where('role', 'admin')->get();
        $teacherUsers = User::where('role', 'teacher')->get();
        $studentUsers = User::where('role', 'student')->get();
        $guardianUsers = User::where('role', 'guardian')->get();
        
        // Check if we have users
        if ($adminUsers->isEmpty() && $teacherUsers->isEmpty() && $studentUsers->isEmpty() && $guardianUsers->isEmpty()) {
            $this->command->info('No users found. Please run the UserSeeder first.');
            return;
        }
        
        // Create system notifications
        $this->createSystemNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers);
        
        // Create admin notifications only if we have admin users
        if ($adminUsers->isNotEmpty()) {
            $this->createAdminNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers);
        }
        
        // Create teacher notifications only if we have teacher users
        if ($teacherUsers->isNotEmpty() && $studentUsers->isNotEmpty()) {
            $this->createTeacherNotifications($teacherUsers, $studentUsers, $guardianUsers);
        }
        
        // Create scheduled notifications
        $this->createScheduledNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers);
    }
    
    /**
     * Create system notifications.
     */
    private function createSystemNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers)
    {
        $faker = FakerFactory::create();
        
        // System announcement to all users
        $systemAnnouncement = Notification::factory()->create([
            'title' => 'Platform Maintenance Notice',
            'body' => "Dear users,\n\nWe will be performing scheduled maintenance on our platform this weekend. The system may be unavailable for short periods between Saturday 10:00 PM and Sunday 2:00 AM (UTC).\n\nWe apologize for any inconvenience this may cause.\n\nThe IQRAPATH Team",
            'type' => 'system',
            'status' => 'sent',
            'sender_type' => 'system',
            'sender_id' => null,
            'sent_at' => now()->subDays(2),
        ]);
        
        // Add recipients for system announcement (all users)
        $allUsers = $adminUsers->merge($teacherUsers)->merge($studentUsers)->merge($guardianUsers);
        foreach ($allUsers as $user) {
            NotificationRecipient::factory()->create([
                'notification_id' => $systemAnnouncement->id,
                'user_id' => $user->id,
                'status' => $faker->randomElement(['delivered', 'read']),
                'channel' => 'in-app',
                'delivered_at' => now()->subDays(2),
                'read_at' => $faker->boolean(70) ? now()->subDays(rand(0, 2)) : null,
            ]);
        }
        
        // New feature announcement
        $featureAnnouncement = Notification::factory()->create([
            'title' => 'New Feature: Enhanced Learning Dashboard',
            'body' => "Dear users,\n\nWe're excited to announce our new Enhanced Learning Dashboard feature! This update brings improved progress tracking, personalized recommendations, and a more intuitive interface.\n\nLog in to check out the new features and let us know what you think!\n\nThe IQRAPATH Team",
            'type' => 'feature',
            'status' => 'sent',
            'sender_type' => 'system',
            'sender_id' => null,
            'sent_at' => now()->subDays(5),
        ]);
        
        // Add recipients for feature announcement (all users)
        foreach ($allUsers as $user) {
            NotificationRecipient::factory()->create([
                'notification_id' => $featureAnnouncement->id,
                'user_id' => $user->id,
                'status' => $faker->randomElement(['delivered', 'read']),
                'channel' => 'in-app',
                'delivered_at' => now()->subDays(5),
                'read_at' => $faker->boolean(60) ? now()->subDays(rand(0, 5)) : null,
            ]);
        }
    }
    
    /**
     * Create admin notifications.
     */
    private function createAdminNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers)
    {
        $faker = FakerFactory::create();
        
        // Skip if no admin users
        if ($adminUsers->isEmpty()) {
            return;
        }
        
        // Get a random admin for sender
        $admin = $adminUsers->random();
        
        // Admin announcement to teachers - only if we have teachers
        if ($teacherUsers->isNotEmpty()) {
            $teacherAnnouncement = Notification::factory()->create([
                'title' => 'Teacher Training Session',
                'body' => "Dear Teachers,\n\nWe will be hosting a training session on the new curriculum materials this Friday at 3:00 PM UTC.\n\nPlease make sure to attend as important information will be shared.\n\nThank you,\nAdmin Team",
                'type' => 'announcement',
                'status' => 'sent',
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'sent_at' => now()->subDays(3),
            ]);
            
            // Add recipients for teacher announcement
            foreach ($teacherUsers as $teacher) {
                NotificationRecipient::factory()->create([
                    'notification_id' => $teacherAnnouncement->id,
                    'user_id' => $teacher->id,
                    'status' => $faker->randomElement(['delivered', 'read']),
                    'channel' => 'in-app',
                    'delivered_at' => now()->subDays(3),
                    'read_at' => $faker->boolean(80) ? now()->subDays(rand(0, 3)) : null,
                ]);
            }
        }
        
        // Admin announcement to students - only if we have students
        if ($studentUsers->isNotEmpty()) {
            $studentAnnouncement = Notification::factory()->create([
                'title' => 'New Learning Resources Available',
                'body' => "Dear Students,\n\nWe have added new learning resources to the platform. These materials cover advanced Tajweed rules and are available in your resource library.\n\nHappy learning!\n\nAdmin Team",
                'type' => 'announcement',
                'status' => 'sent',
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'sent_at' => now()->subDays(7),
            ]);
            
            // Add recipients for student announcement
            foreach ($studentUsers as $student) {
                NotificationRecipient::factory()->create([
                    'notification_id' => $studentAnnouncement->id,
                    'user_id' => $student->id,
                    'status' => $faker->randomElement(['delivered', 'read', 'sent']),
                    'channel' => 'in-app',
                    'delivered_at' => $faker->boolean(80) ? now()->subDays(rand(0, 7)) : null,
                    'read_at' => $faker->boolean(50) ? now()->subDays(rand(0, 7)) : null,
                ]);
            }
        }
    }
    
    /**
     * Create teacher notifications.
     */
    private function createTeacherNotifications($teacherUsers, $studentUsers, $guardianUsers)
    {
        $faker = FakerFactory::create();
        
        // Only proceed if we have teachers and students
        if ($teacherUsers->isEmpty() || $studentUsers->isEmpty()) {
            return;
        }
        
        // Get random students (up to 5)
        $targetStudents = $studentUsers->take(min(5, $studentUsers->count()));
        
        foreach ($targetStudents as $student) {
            // Get a random teacher
            $teacher = $teacherUsers->random();
            
            // Teacher notification to student
            $sessionReminder = Notification::factory()->create([
                'title' => 'Class Reminder',
                'body' => "Dear {$student->name},\n\nThis is a reminder that our next Quran session is scheduled for tomorrow at 4:00 PM UTC.\n\nPlease make sure to complete your assigned reading before the class.\n\nBest regards,\n{$teacher->name}",
                'type' => 'class',
                'status' => 'sent',
                'sender_type' => 'teacher',
                'sender_id' => $teacher->id,
                'sent_at' => now()->subDays(1),
            ]);
            
            // Add recipient for session reminder
            NotificationRecipient::factory()->create([
                'notification_id' => $sessionReminder->id,
                'user_id' => $student->id,
                'status' => $faker->randomElement(['delivered', 'read']),
                'channel' => 'in-app',
                'delivered_at' => now()->subDays(1),
                'read_at' => $faker->boolean(70) ? now()->subHours(rand(1, 24)) : null,
            ]);
            
            // If student has a guardian, notify them too
            $guardian = $guardianUsers->where('id', $student->guardian_id)->first();
            if ($guardian) {
                NotificationRecipient::factory()->create([
                    'notification_id' => $sessionReminder->id,
                    'user_id' => $guardian->id,
                    'status' => $faker->randomElement(['delivered', 'read']),
                    'channel' => 'in-app',
                    'delivered_at' => now()->subDays(1),
                    'read_at' => $faker->boolean(60) ? now()->subHours(rand(1, 24)) : null,
                ]);
            }
        }
    }

    /**
     * Create scheduled notifications.
     */
    private function createScheduledNotifications($adminUsers, $teacherUsers, $studentUsers, $guardianUsers)
    {
        $faker = FakerFactory::create();
        
        // Only proceed if we have users
        if ($adminUsers->isEmpty() && $teacherUsers->isEmpty() && $studentUsers->isEmpty() && $guardianUsers->isEmpty()) {
            return;
        }
        
        // Get a random admin for sender
        $admin = $adminUsers->isNotEmpty() ? $adminUsers->random() : null;
        
        // 1. Scheduled system maintenance notification
        Notification::factory()->create([
            'title' => 'Upcoming System Maintenance',
            'body' => "Dear users,\n\nWe will be performing scheduled maintenance on our platform next weekend. The system may be unavailable for short periods between Saturday 10:00 PM and Sunday 2:00 AM (UTC).\n\nWe apologize for any inconvenience this may cause.\n\nThe IQRAPATH Team",
            'type' => 'system',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => now()->addDays(3),
            'metadata' => [
                'audience' => [
                    'type' => 'all',
                    'name' => 'All Users'
                ],
                'frequency' => 'One-time'
            ],
        ]);
        
        // 2. Weekly report notification
        Notification::factory()->create([
            'title' => 'Weekly Progress Summary',
            'body' => "Your weekly learning progress summary is now available. Log in to view your achievements and areas for improvement.",
            'type' => 'report',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => now()->addDays(7),
            'metadata' => [
                'audience' => [
                    'type' => 'role',
                    'name' => 'Students'
                ],
                'frequency' => 'Weekly'
            ],
        ]);
        
        // 3. Teacher meeting reminder
        if ($admin && $teacherUsers->isNotEmpty()) {
            Notification::factory()->create([
                'title' => 'Upcoming Teacher Meeting',
                'body' => "Dear teachers,\n\nThis is a reminder about our monthly staff meeting scheduled for next Monday at 3:00 PM UTC.\n\nAgenda items include curriculum updates and teaching best practices.\n\nAdmin Team",
                'type' => 'meeting',
                'status' => 'scheduled',
                'sender_type' => 'admin',
                'sender_id' => $admin->id,
                'scheduled_at' => now()->addDays(5),
                'metadata' => [
                    'audience' => [
                        'type' => 'role',
                        'name' => 'Teachers'
                    ],
                    'frequency' => 'One-time'
                ],
            ]);
        }
        
        // 4. Class reminder for specific student
        if ($teacherUsers->isNotEmpty() && $studentUsers->isNotEmpty()) {
            $teacher = $teacherUsers->random();
            $student = $studentUsers->random();
            
            Notification::factory()->create([
                'title' => 'Upcoming Quran Session',
                'body' => "Dear students,\n\nThis is a reminder about our upcoming Quran session on Advanced Tajweed Rules scheduled for tomorrow at 5:00 PM UTC.\n\nPlease prepare Surah Al-Baqarah verses 1-10 for practice.\n\nBest regards,\n{$teacher->name}",
                'type' => 'class',
                'status' => 'scheduled',
                'sender_type' => 'teacher',
                'sender_id' => $teacher->id,
                'scheduled_at' => now()->addDays(1)->setHour(17)->setMinute(0)->setSecond(0),
                'metadata' => [
                    'audience' => [
                        'type' => 'specific',
                        'name' => 'Advanced Tajweed Students'
                    ],
                    'frequency' => 'One-time'
                ],
            ]);
        }
        
        // 5. Payment reminder for subscription
        Notification::factory()->create([
            'title' => 'Subscription Payment Reminder',
            'body' => "Dear user,\n\nYour subscription payment is due in 3 days. Please ensure your payment method is up to date to avoid any interruption in service.\n\nThank you,\nIQRAPATH Team",
            'type' => 'payment',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => now()->addDays(2),
            'metadata' => [
                'audience' => [
                    'type' => 'role',
                    'name' => 'Subscribers'
                ],
                'frequency' => 'Monthly'
            ],
        ]);
    }
} 