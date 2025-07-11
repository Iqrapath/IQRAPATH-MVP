<?php

namespace App\Console\Commands;

use App\Models\TeachingSession;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use App\Notifications\SessionReminder;

class SendSessionReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:send-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send reminders for upcoming teaching sessions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Sending session reminders...');
        
        // Find sessions starting in the next hour
        $startTime = Carbon::now();
        $endTime = Carbon::now()->addHour();
        
        $upcomingSessions = TeachingSession::where('status', 'scheduled')
            ->whereDate('session_date', Carbon::today())
            ->whereTime('start_time', '>=', $startTime->format('H:i:s'))
            ->whereTime('start_time', '<=', $endTime->format('H:i:s'))
            ->get();
            
        $this->info("Found {$upcomingSessions->count()} upcoming sessions in the next hour.");
        
        foreach ($upcomingSessions as $session) {
            try {
                // Send reminder to teacher
                $teacher = $session->teacher;
                if ($teacher) {
                    Notification::send($teacher, new SessionReminder($session, 'teacher'));
                    $this->info("Sent reminder to teacher {$teacher->name} for session {$session->session_uuid}");
                }
                
                // Send reminder to student
                $student = $session->student;
                if ($student) {
                    Notification::send($student, new SessionReminder($session, 'student'));
                    $this->info("Sent reminder to student {$student->name} for session {$session->session_uuid}");
                }
                
                // Create a record of this reminder in booking_notifications
                $session->booking->notifications()->create([
                    'user_id' => $teacher->id,
                    'notification_type' => 'reminder',
                    'sent_at' => now(),
                ]);
                
                $session->booking->notifications()->create([
                    'user_id' => $student->id,
                    'notification_type' => 'reminder',
                    'sent_at' => now(),
                ]);
                
            } catch (\Exception $e) {
                Log::error("Failed to send reminder for session {$session->id}: " . $e->getMessage());
                $this->error("Failed to send reminder for session {$session->id}: " . $e->getMessage());
            }
        }
        
        $this->info('Session reminders sent successfully.');
        
        return 0;
    }
} 