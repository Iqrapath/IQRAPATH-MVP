#!/bin/bash

# Test script for running Laravel scheduler
# This script creates a test notification and then runs the scheduler to send it

# Create a test notification scheduled for 1 minute from now
echo "Creating test scheduled notification..."
php artisan notification:create-test-scheduled

# Wait for 1 minute and 5 seconds to ensure the notification is due
echo "Waiting for 65 seconds for the notification to become due..."
sleep 65

# Run the scheduler to send the notification
echo "Running scheduler to send the notification..."
php artisan notifications:send-scheduled

# Check the logs
echo "Checking logs for confirmation..."
echo "Last 10 lines of storage/logs/laravel.log:"
tail -n 10 storage/logs/laravel.log

echo ""
echo "Test completed. Check the notification status in the admin panel." 