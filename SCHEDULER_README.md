# Laravel Scheduler for IQRAPATH

This document explains how to use the Laravel scheduler in the IQRAPATH application.

## What This Does

The scheduler is responsible for running scheduled tasks in your application, such as:

1. Processing scheduled notifications
2. Sending session reminders
3. Running the WebSocket server for real-time notifications
4. Other background tasks

## Scheduled Tasks

The following tasks are currently scheduled in the application:

1. **Session Reminders** - Runs hourly to send reminders for upcoming teaching sessions
2. **Scheduled Notifications** - Runs every minute to send scheduled notifications
3. **Scheduled Ticket Responses** - Runs every minute to process scheduled responses

## Running the Scheduler

### Option 1: Using Composer Scripts

When you start the development environment using `composer run dev`, the scheduler will automatically run in the background:

```bash
composer run dev
```

This will start:
- Laravel development server
- Queue worker
- Vite development server
- Laravel scheduler

### Option 2: Running the Scheduler Independently

If you need to run the scheduler independently:

#### On Linux/Mac:
```bash
./run-scheduler.sh
```

#### On Windows:
```bash
run-scheduler.bat
```

Or directly using Artisan:
```bash
php artisan schedule:work
```

## Testing the Scheduler

To test if the scheduler is working correctly, you can use the provided test scripts:

#### On Linux/Mac:
```bash
./test-scheduler.sh
```

#### On Windows:
```bash
test-scheduler.bat
```

These scripts will:
1. Create a test notification scheduled for 1 minute from now
2. Wait for 65 seconds
3. Run the scheduler to send the notification
4. Check the logs for confirmation

## Scheduler Logs

Scheduler logs can be found at:
- `storage/logs/scheduler.log` - Contains output from the scheduled notification command
- `storage/logs/laravel.log` - Contains general Laravel logs including scheduler activities

## Adding New Scheduled Tasks

To add new scheduled tasks, edit the `schedule` method in `app/Console/Kernel.php`. 