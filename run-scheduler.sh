#!/bin/bash

# Run Laravel scheduler in the foreground
echo "Starting Laravel Scheduler..."
php artisan schedule:work 