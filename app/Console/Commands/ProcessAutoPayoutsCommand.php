<?php

namespace App\Console\Commands;

use App\Jobs\ProcessAutoPayouts;
use Illuminate\Console\Command;

class ProcessAutoPayoutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'payouts:process-auto';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process automatic payouts for teachers who have reached the threshold';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Processing automatic payouts...');
        
        ProcessAutoPayouts::dispatch();
        
        $this->info('Auto-payout job dispatched successfully!');
        
        return Command::SUCCESS;
    }
}
