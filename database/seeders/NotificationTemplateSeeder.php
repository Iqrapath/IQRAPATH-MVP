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
        $templates = [
            [
                'name' => 'welcome_user',
                'title' => 'Welcome to IQRAPATH!',
                'body' => "Dear [User_Name],\n\nWelcome to IQRAPATH! We're excited to have you join our community of Quran learners.\n\nYour account has been successfully created and you can now access all the features of our platform.\n\nIf you have any questions, please don't hesitate to contact our support team.\n\nBest regards,\nThe IQRAPATH Team",
                'type' => 'system',
                'placeholders' => ['User_Name', 'action_url', 'action_text'],
                'is_active' => true,
            ],
            [
                'name' => 'payment_confirmation',
                'title' => 'Payment Successful – "Jazakallah Khair!"',
                'body' => "Hello [User_Name],\n\nYour subscription for [Plan_Name] has been successfully processed.\n\nAmount: [Amount_Paid] [Currency]\nDate: [Date]\n\nYour Teacher will contact you soon, In Shaa Allah.",
                'type' => 'payment',
                'placeholders' => ['User_Name', 'Amount_Paid', 'Currency', 'Date', 'Plan_Name', 'action_url', 'action_text'],
                'is_active' => true,
            ],
            [
                'name' => 'session_reminder',
                'title' => 'Class Reminder: Your session starts soon',
                'body' => "Dear [User_Name],\n\nThis is a reminder that your class \"[Session_Title]\" is scheduled to start at [Session_Time].\n\nYour teacher [Teacher_Name] is looking forward to seeing you.\n\nPlease ensure you are ready 5 minutes before the scheduled time.",
                'type' => 'class',
                'placeholders' => ['User_Name', 'Session_Title', 'Session_Time', 'Teacher_Name', 'action_url', 'action_text'],
                'is_active' => true,
            ],
            [
                'name' => 'subscription_expiry',
                'title' => 'Your subscription is expiring soon',
                'body' => "Dear [User_Name],\n\nYour subscription to [Plan_Name] will expire on [Expiry_Date] ([Days_Remaining] days remaining).\n\nTo continue enjoying uninterrupted access to our services, please renew your subscription before the expiry date.\n\nThank you for being a valued member of IQRAPATH.",
                'type' => 'subscription',
                'placeholders' => ['User_Name', 'Plan_Name', 'Expiry_Date', 'Days_Remaining', 'action_url', 'action_text'],
                'is_active' => true,
            ],
            [
                'name' => 'new_feature',
                'title' => 'New Feature Announcement',
                'body' => "Dear [User_Name],\n\nWe're excited to announce a new feature on IQRAPATH: [Feature_Name]!\n\n[Feature_Description]\n\nWe hope you enjoy this new addition to our platform. Your feedback is always welcome.",
                'type' => 'feature',
                'placeholders' => ['User_Name', 'Feature_Name', 'Feature_Description', 'action_url', 'action_text'],
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