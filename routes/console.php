<?php

use App\Models\Notification;
use App\Models\User;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
|
| Define scheduled tasks using the Schedule facade.
|
*/

use Illuminate\Console\Scheduling\Schedule;

app(Schedule::class)->command('app:process-scheduled-responses')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->description('Process scheduled support ticket responses');

app(Schedule::class)->command('app:send-session-reminders')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->description('Send session reminders to teachers and students');

app(Schedule::class)->command('app:start-reverb --background')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->description('Ensure Reverb WebSocket server is running');

app(Schedule::class)->job(new \App\Jobs\UpdateUrgentActionCounts())
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->description('Update urgent action counts for admin dashboard');

app(Schedule::class)->command('subscriptions:process-renewals')
    ->daily()
    ->withoutOverlapping()
    ->description('Process subscription renewals and expirations');

app(Schedule::class)->command('payouts:process-auto')
    ->dailyAt('10:00')
    ->withoutOverlapping()
    ->description('Process automatic payouts for teachers who reached threshold');
Artisan::command('notification:create-test-scheduled', function () {
    $this->info('Creating a test scheduled notification...');
    
    try {
        // Find an admin user
        $user = User::where('role', 'admin')->first();
        
        if (!$user) {
            $user = User::first();
        }
        
        if (!$user) {
            $this->error('No users found in the system.');
            return;
        }
        
        // Create a notification scheduled for 1 minute from now
        $scheduledTime = Carbon::now()->addMinute();
        
        $notification = Notification::create([
            'title' => 'Test Scheduled Notification',
            'body' => "This is a test scheduled notification.\nScheduled for: " . $scheduledTime->format('Y-m-d H:i:s') . "\nCreated at: " . Carbon::now()->format('Y-m-d H:i:s'),
            'type' => 'test',
            'status' => 'scheduled',
            'sender_type' => 'system',
            'sender_id' => null,
            'scheduled_at' => $scheduledTime,
        ]);
        
        // Add recipient
        $notificationService = app(NotificationService::class);
        $notificationService->addRecipients($notification, [
            'user_ids' => [$user->id],
            'channels' => ['in-app'],
        ]);
        
        $this->info('Test notification created successfully!');
        $this->info("Notification ID: {$notification->id}");
        $this->info("Recipient: {$user->name} (ID: {$user->id})");
        $this->info("Scheduled for: {$scheduledTime->format('Y-m-d H:i:s')}");
        $this->info("Run 'php artisan notifications:send-scheduled' after {$scheduledTime->format('H:i:s')} to send it manually.");
        
        Log::info('Test scheduled notification created', [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'scheduled_at' => $scheduledTime->format('Y-m-d H:i:s')
        ]);
    } catch (\Exception $e) {
        $this->error("Error creating test notification: {$e->getMessage()}");
        Log::error('Error creating test notification', [
            'exception' => $e
        ]);
    }
})->purpose('Create a test scheduled notification for immediate delivery');

