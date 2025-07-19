<?php

namespace Database\Seeders;

use App\Models\NotificationTemplate;
use Illuminate\Database\Seeder;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create common notification templates
        $templates = [
            [
                'name' => 'welcome_user',
                'title' => 'Welcome to IqraPath!',
                'body' => 'Hello {User_Name}, thank you for joining our platform. We are excited to have you with us.',
                'type' => 'system',
                'placeholders' => ['User_Name'],
                'level' => 'info',
                'action_text' => 'Explore Dashboard',
                'action_url' => '/dashboard',
                'is_active' => true,
            ],
            [
                'name' => 'payment_confirmation',
                'title' => 'Payment Confirmation',
                'body' => 'Hello {User_Name}, we have received your payment of {Amount} {Currency} for {Plan_Name}.',
                'type' => 'payment',
                'placeholders' => ['User_Name', 'Amount', 'Currency', 'Plan_Name', 'Date'],
                'level' => 'success',
                'action_text' => 'View Subscription',
                'action_url' => '/subscriptions/my',
                'is_active' => true,
            ],
            [
                'name' => 'session_reminder',
                'title' => 'Upcoming Session Reminder',
                'body' => 'Hello {User_Name}, this is a reminder that you have a session "{Session_Title}" scheduled for {Session_Time} with {Teacher_Name}.',
                'type' => 'session',
                'placeholders' => ['User_Name', 'Session_Title', 'Session_Time', 'Teacher_Name'],
                'level' => 'info',
                'action_text' => 'View Session Details',
                'action_url' => '/dashboard',
                'is_active' => true,
            ],
            [
                'name' => 'subscription_expiry',
                'title' => 'Subscription Expiring Soon',
                'body' => 'Hello {User_Name}, your subscription to {Plan_Name} will expire on {Expiry_Date}. You have {Days_Remaining} days remaining.',
                'type' => 'subscription',
                'placeholders' => ['User_Name', 'Plan_Name', 'Expiry_Date', 'Days_Remaining'],
                'level' => 'warning',
                'action_text' => 'Renew Subscription',
                'action_url' => '/subscriptions/plans',
                'is_active' => true,
            ],
            [
                'name' => 'new_feature',
                'title' => 'New Feature Available',
                'body' => 'Hello {User_Name}, we have added a new feature to our platform: {Feature_Name}. {Feature_Description}',
                'type' => 'feature',
                'placeholders' => ['User_Name', 'Feature_Name', 'Feature_Description'],
                'level' => 'info',
                'action_text' => 'Check it Out',
                'action_url' => '/dashboard',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}
