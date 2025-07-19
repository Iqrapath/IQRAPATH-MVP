#!/bin/bash
echo "Starting Reverb WebSocket server..."
php artisan app:start-reverb --background

echo "Starting Laravel Scheduler..."
php artisan schedule:work 