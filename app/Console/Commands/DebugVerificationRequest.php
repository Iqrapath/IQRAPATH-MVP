<?php

namespace App\Console\Commands;

use App\Models\VerificationRequest;
use App\Models\User;
use Illuminate\Console\Command;

class DebugVerificationRequest extends Command
{
    protected $signature = 'debug:verification {verification_id} {user_id?}';
    protected $description = 'Debug verification request authorization issues';

    public function handle()
    {
        $verificationId = $this->argument('verification_id');
        $userId = $this->argument('user_id');

        // Get verification request
        $verificationRequest = VerificationRequest::find($verificationId);
        if (!$verificationRequest) {
            $this->error("Verification request {$verificationId} not found");
            return 1;
        }

        $this->info("Verification Request #{$verificationId}:");
        $this->line("Status: {$verificationRequest->status}");
        $this->line("Video Status: {$verificationRequest->video_status}");
        $this->line("Created: {$verificationRequest->created_at}");

        if ($userId) {
            // Get user
            $user = User::find($userId);
            if (!$user) {
                $this->error("User {$userId} not found");
                return 1;
            }

            $this->info("\nUser #{$userId}:");
            $this->line("Name: {$user->name}");
            $this->line("Email: {$user->email}");
            $this->line("Role: {$user->role}");

            // Test authorization
            $this->info("\nAuthorization Tests:");
            $this->line("Can view: " . ($user->can('view', $verificationRequest) ? 'YES' : 'NO'));
            $this->line("Can approve: " . ($user->can('approve', $verificationRequest) ? 'YES' : 'NO'));
            $this->line("Can reject: " . ($user->can('reject', $verificationRequest) ? 'YES' : 'NO'));
            $this->line("Can request video: " . ($user->can('requestVideoVerification', $verificationRequest) ? 'YES' : 'NO'));
            $this->line("Can complete video: " . ($user->can('completeVideoVerification', $verificationRequest) ? 'YES' : 'NO'));
        }

        return 0;
    }
}