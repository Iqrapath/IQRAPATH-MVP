<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\BookingModification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendModificationReminders implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct()
    {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notificationService): void
    {
        try {
            // Get modifications expiring in 24 hours
            $expiringModifications = BookingModification::expiringSoon(24)
                ->with(['student', 'teacher'])
                ->get();

            foreach ($expiringModifications as $modification) {
                // Send reminder to teacher
                $notificationService->createNotification([
                    'title' => 'Modification Request Expiring Soon',
                    'body' => "A {$modification->formatted_type} from {$modification->student->name} will expire in 24 hours.",
                    'type' => 'modification_reminder',
                    'user_id' => $modification->teacher_id,
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]);

                // Send reminder to student
                $notificationService->createNotification([
                    'title' => 'Modification Request Expiring Soon',
                    'body' => "Your {$modification->formatted_type} will expire in 24 hours if not responded to.",
                    'type' => 'modification_reminder',
                    'user_id' => $modification->student_id,
                    'related_type' => 'booking_modification',
                    'related_id' => $modification->id,
                ]);
            }

            Log::info('Sent modification reminders', [
                'reminder_count' => $expiringModifications->count(),
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to send modification reminders', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}