<?php

namespace Database\Seeders;

use App\Models\NotificationTrigger;
use Illuminate\Database\Seeder;

class NotificationTriggerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create common notification triggers
        $triggers = [
            // User registration trigger
            [
                'name' => 'Welcome New User',
                'event' => 'UserRegistered',
                'template_name' => 'welcome_user',
                'audience_type' => 'individual', // Only the registered user
                'audience_filter' => null, // No filter needed, event user is used
                'channels' => ['in-app', 'mail'],
                'timing_type' => 'immediate',
                'level' => 'info',
                'is_enabled' => true,
            ],
            
            // Payment processed trigger
            [
                'name' => 'Payment Confirmation',
                'event' => 'PaymentProcessed',
                'template_name' => 'payment_confirmation',
                'audience_type' => 'individual', // Only the user who made the payment
                'audience_filter' => null, // No filter needed, event user is used
                'channels' => ['in-app', 'mail'],
                'timing_type' => 'immediate',
                'level' => 'success',
                'is_enabled' => true,
            ],
            
            // Session scheduled trigger
            [
                'name' => 'Session Reminder',
                'event' => 'SessionScheduled',
                'template_name' => 'session_reminder',
                'audience_type' => 'individual', // Only the user who scheduled the session
                'audience_filter' => null, // No filter needed, event user is used
                'channels' => ['in-app', 'mail'],
                'timing_type' => 'immediate',
                'level' => 'info',
                'is_enabled' => true,
            ],
            
            // Subscription expiring trigger
            [
                'name' => 'Subscription Expiry Reminder',
                'event' => 'SubscriptionExpiring',
                'template_name' => 'subscription_expiry',
                'audience_type' => 'individual', // Only the user whose subscription is expiring
                'audience_filter' => null, // No filter needed, event user is used
                'channels' => ['in-app', 'mail'],
                'timing_type' => 'immediate',
                'level' => 'warning',
                'is_enabled' => true,
            ],
            
            // New feature announcement trigger (example of a custom trigger)
            [
                'name' => 'New Feature Announcement',
                'event' => 'FeatureReleased',
                'template_name' => 'new_feature',
                'audience_type' => 'all', // All users
                'audience_filter' => null,
                'channels' => ['in-app'],
                'timing_type' => 'immediate',
                'level' => 'info',
                'is_enabled' => true,
            ],
        ];

        foreach ($triggers as $trigger) {
            NotificationTrigger::updateOrCreate(
                ['name' => $trigger['name'], 'event' => $trigger['event']],
                $trigger
            );
        }
    }
}
