<?php

namespace App\Console\Commands;

use App\Models\ActionLog;
use App\Models\Notification;
use App\Models\TicketResponse;
use App\Services\NotificationService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledResponses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-scheduled-responses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process and send scheduled ticket responses';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Processing scheduled ticket responses...');
        
        // Get all scheduled responses that are due to be sent
        $responses = TicketResponse::where('notification_sent', false)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '<=', Carbon::now())
            ->get();
        
        $count = $responses->count();
        $this->info("Found {$count} scheduled responses to process.");
        
        foreach ($responses as $response) {
            try {
                $this->sendResponse($response);
                $this->info("Sent response #{$response->id} for ticket #{$response->ticket_id}");
            } catch (\Exception $e) {
                $this->error("Failed to send response #{$response->id}: " . $e->getMessage());
                Log::error("Failed to send scheduled response #{$response->id}", [
                    'error' => $e->getMessage(),
                    'response_id' => $response->id,
                    'ticket_id' => $response->ticket_id,
                ]);
            }
        }
        
        $this->info('Finished processing scheduled ticket responses.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Send a notification for a response.
     */
    private function sendResponse(TicketResponse $response)
    {
        // Get ticket and requester information
        $ticket = $response->ticket;
        $requester = $ticket->requester;
        $responder = $response->responder;
        
        // Create notification using NotificationService
        $notificationService = app(NotificationService::class);
        
        // Prepare notification data
        $notificationData = [
            'title' => "New response to your {$ticket->ticket_type} #{$ticket->id}",
            'body' => $response->content,
            'type' => 'support',
            'status' => 'sent',
            'sender_type' => 'user',
            'sender_id' => $response->responder_id,
            'metadata' => [
                'ticket_id' => $ticket->id,
                'response_id' => $response->id,
                'ticket_type' => $ticket->ticket_type,
                'ticket_subject' => $ticket->subject,
            ],
        ];
        
        // Create notification
        $notification = $notificationService->createNotification($notificationData);
        
        // Add recipient (the ticket requester)
        $notificationService->addRecipient($notification, $requester->id, ['in-app', 'email']);
        
        // Send notification
        $notificationService->sendNotification($notification);
        
        // Mark response as notification sent
        $response->update([
            'notification_sent' => true,
        ]);
        
        // Log action
        ActionLog::create([
            'loggable_id' => $response->ticket_id,
            'loggable_type' => get_class($response->ticket),
            'action' => 'response_notification_sent',
            'performed_by' => $response->responder_id,
            'details' => [
                'response_id' => $response->id,
                'scheduled' => true,
                'notification_id' => $notification->id,
            ],
        ]);
    }
}
