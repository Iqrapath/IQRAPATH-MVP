<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\BookingModificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExpiredModifications implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public function __construct()
    {
        $this->onQueue('modifications');
    }

    public function handle(BookingModificationService $modificationService): void
    {
        try {
            $expiredCount = $modificationService->expireOldModifications();
            
            Log::info('Processed expired modifications', [
                'expired_count' => $expiredCount,
                'processed_at' => now(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to process expired modifications', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
}