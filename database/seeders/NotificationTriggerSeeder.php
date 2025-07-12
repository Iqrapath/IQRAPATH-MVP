<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use App\Models\NotificationTrigger;
use Illuminate\Database\Seeder;

class NotificationTriggerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $triggers = [
            // Payment confirmation trigger
            [
                'name' => 'Payment Confirmation',
                'event' => 'payment.processed',
                'template_name' => 'payment_confirmation',
                'audience_type' => 'specific_users',
                'audience_filter' => null, // Will use user_id from event data
                'channels' => ['in-app', 'email'],
                'timing_type' => 'immediate',
                'timing_value' => null,
                'timing_unit' => null,
                'is_enabled' => true,
            ],
            
            // Class reminder trigger - 1 hour before
            [
                'name' => 'Class Reminder - 1 Hour Before',
                'event' => 'session.scheduled',
                'template_name' => 'session_reminder',
                'audience_type' => 'specific_users',
                'audience_filter' => null, // Will use user_id from event data
                'channels' => ['in-app', 'email'],
                'timing_type' => 'before_event',
                'timing_value' => 1,
                'timing_unit' => 'hours',
                'is_enabled' => true,
            ],
            
            // Subscription expiry reminder - 3 days before
            [
                'name' => 'Subscription Expiry - 3 Days Before',
                'event' => 'subscription.expiring',
                'template_name' => 'subscription_expiry',
                'audience_type' => 'specific_users',
                'audience_filter' => null, // Will use user_id from event data
                'channels' => ['in-app', 'email'],
                'timing_type' => 'before_event',
                'timing_value' => 3,
                'timing_unit' => 'days',
                'is_enabled' => true,
            ],
        ];

        foreach ($triggers as $triggerData) {
            // Get template ID
            $template = NotificationTemplate::where('name', $triggerData['template_name'])->first();
            
            if ($template) {
                // Remove template_name from data
                $templateName = $triggerData['template_name'];
                unset($triggerData['template_name']);
                
                // Create or update trigger
                NotificationTrigger::updateOrCreate(
                    ['name' => $triggerData['name'], 'event' => $triggerData['event']],
                    array_merge($triggerData, ['template_id' => $template->id])
                );
            }
        }
    }
} 