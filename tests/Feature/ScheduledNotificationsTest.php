<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduledNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that scheduled notifications are sent when due.
     */
    public function test_scheduled_notifications_are_sent_when_due(): void
    {
        // Create a user
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        // Create a notification scheduled for 5 minutes ago
        $notification = Notification::create([
            'title' => 'Test Scheduled Notification',
            'body' => 'This is a test scheduled notification.',
            'type' => 'test',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => Carbon::now()->subMinutes(5),
        ]);

        // Add recipient
        $notificationService = app(NotificationService::class);
        $notificationService->addRecipients($notification, [
            'user_ids' => [$user->id],
            'channels' => ['in-app'],
        ]);

        // Run the command
        $this->artisan('notifications:send-scheduled')
            ->expectsOutput('Successfully sent 1 scheduled notifications.')
            ->assertExitCode(0);

        // Check that the notification status was updated
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'sent',
        ]);

        // Check that the recipient status was updated
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'status' => 'delivered',
        ]);
    }

    /**
     * Test that scheduled notifications are not sent before their scheduled time.
     */
    public function test_scheduled_notifications_are_not_sent_before_scheduled_time(): void
    {
        // Create a user
        $user = User::factory()->create([
            'role' => 'student',
        ]);

        // Create a notification scheduled for 5 minutes in the future
        $notification = Notification::create([
            'title' => 'Test Future Scheduled Notification',
            'body' => 'This is a test scheduled notification for the future.',
            'type' => 'test',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => Carbon::now()->addMinutes(5),
        ]);

        // Add recipient
        $notificationService = app(NotificationService::class);
        $notificationService->addRecipients($notification, [
            'user_ids' => [$user->id],
            'channels' => ['in-app'],
        ]);

        // Run the command
        $this->artisan('notifications:send-scheduled')
            ->expectsOutput('Successfully sent 0 scheduled notifications.')
            ->assertExitCode(0);

        // Check that the notification status was not updated
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'status' => 'scheduled',
        ]);

        // Check that the recipient status was not updated
        $this->assertDatabaseHas('notification_recipients', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'status' => 'pending',
        ]);
    }
} 