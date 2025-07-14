# IQRAPATH - Islamic Education Platform

## Scheduled Notifications Setup

The platform includes a scheduled notification system that automatically sends notifications at their scheduled time. To ensure this works properly, you need to set up the Laravel scheduler to run every minute.

### Setting Up the Scheduler

#### For Linux/Unix Servers:

Add this Cron entry to your server:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

Replace `/path-to-your-project` with the actual path to your Laravel project.

To edit your crontab:

```bash
crontab -e
```

#### For Windows Servers:

Create a batch file (e.g., `scheduler.bat`) with the following content:

```batch
cd C:\path-to-your-project
php artisan schedule:run
```

Replace `C:\path-to-your-project` with the actual path to your Laravel project.

Then set up a Windows Task Scheduler task to run this batch file every minute.

### Testing Scheduled Notifications

To test that scheduled notifications are working correctly:

#### Using the Test Scripts

We've included test scripts that create a scheduled notification and then run the scheduler to send it:

- **For Linux/Unix**: Run `./test-scheduler.sh`
- **For Windows**: Run `test-scheduler.bat`

These scripts will:
1. Create a test notification scheduled for 1 minute in the future
2. Wait for the notification to become due
3. Run the scheduler to send the notification
4. Show the logs to confirm it was sent

#### Manual Testing

You can also test manually:

1. Create a scheduled notification:
   ```bash
   php artisan notification:create-test-scheduled
   ```

2. Wait until the scheduled time has passed

3. Run the scheduler:
   ```bash
   php artisan notifications:send-scheduled
   ```

4. Check the notification status in the admin panel

### Verifying the Setup

To verify that the scheduler is working correctly:

1. Check the log file at `storage/logs/scheduler.log`
2. Create a scheduled notification and wait for it to be sent
3. Run the command manually to test: `php artisan notifications:send-scheduled`

### Troubleshooting

If scheduled notifications are not being sent:

1. Check that the cron job or task scheduler is running
2. Verify that the `scheduled_at` time is in the correct format and timezone
3. Check the log files at `storage/logs/laravel.log` and `storage/logs/scheduler.log`
4. Make sure the queue worker is running if you're using queues for notification delivery

## Additional Resources

- [Laravel Scheduling Documentation](https://laravel.com/docs/10.x/scheduling)
- [Laravel Notifications Documentation](https://laravel.com/docs/10.x/notifications) 