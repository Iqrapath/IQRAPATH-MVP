<?php

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessSubscriptionRenewals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscriptions:process-renewals';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process subscription renewals and mark expired subscriptions';

    /**
     * Execute the console command.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $this->info('Processing subscription renewals...');

        try {
            $count = $subscriptionService->processExpiredSubscriptions();
            
            $this->info("Processed {$count} expired subscriptions.");
            
            Log::info('Subscription renewals processed', [
                'count' => $count,
                'timestamp' => now()->toDateTimeString()
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Failed to process subscription renewals: ' . $e->getMessage());
            
            Log::error('Subscription renewal processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;
        }
    }
}
