<?php

namespace App\Console\Commands;

use App\Services\WalletSyncService;
use Illuminate\Console\Command;

class SyncTeacherWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:sync-teachers
                            {--force : Force sync even if recently synced}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync all teacher wallet balances from transactions';

    /**
     * Execute the console command.
     */
    public function handle(WalletSyncService $walletSyncService): int
    {
        $this->info('Starting teacher wallet sync...');

        $results = $walletSyncService->syncAllTeacherWallets();

        $this->info("Sync complete!");
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Teachers', $results['total']],
                ['Successfully Synced', $results['synced']],
                ['Failed', $results['failed']],
            ]
        );

        return $results['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
