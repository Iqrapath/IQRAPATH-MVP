<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\UrgentAction;
use Illuminate\Support\Facades\Log;

class UpdateUrgentActionCounts implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    public function handle(): void
    {
        try {
            Log::info('Starting urgent action count update job');
            
            $urgentActions = UrgentAction::where('is_active', true)->get();
            
            foreach ($urgentActions as $action) {
                try {
                    $action->updateCachedCount();
                    Log::info("Updated count for {$action->type}: {$action->cached_count}");
                } catch (\Exception $e) {
                    Log::error("Failed to update count for {$action->type}: " . $e->getMessage());
                    continue;
                }
            }
            
            Log::info('Completed urgent action count update job');
            
        } catch (\Exception $e) {
            Log::error('Urgent action count update job failed: ' . $e->getMessage());
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('Urgent action count update job failed permanently: ' . $exception->getMessage());
    }
}
