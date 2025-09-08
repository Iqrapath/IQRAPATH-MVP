<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessExpiredModifications;
use App\Jobs\SendModificationReminders;
use Illuminate\Console\Command;

class ProcessModificationsCommand extends Command
{
    protected $signature = 'modifications:process {--reminders : Send reminder notifications}';
    protected $description = 'Process expired modifications and send reminders';

    public function handle(): int
    {
        $this->info('Processing modifications...');

        // Process expired modifications
        ProcessExpiredModifications::dispatch();
        $this->info('Dispatched expired modifications job');

        // Send reminders if requested
        if ($this->option('reminders')) {
            SendModificationReminders::dispatch();
            $this->info('Dispatched modification reminders job');
        }

        $this->info('Modification processing completed successfully!');
        return 0;
    }
}