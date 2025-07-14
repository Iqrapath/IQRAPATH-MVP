@echo off
echo Testing Laravel Scheduler for Scheduled Notifications

echo Creating test scheduled notification...
php artisan notification:create-test-scheduled

echo Waiting for 65 seconds for the notification to become due...
timeout /t 65

echo Running scheduler to send the notification...
php artisan notifications:send-scheduled

echo Test completed. Check the notification status in the admin panel.
pause 