<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TeacherEarning;
use App\Models\TeacherWallet;
use App\Models\User;
use App\Services\UnifiedWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MigrateTeacherWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallet:migrate-teachers {--force : Force migration even if wallets exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing teacher earnings to the new unified wallet system';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private UnifiedWalletService $walletService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting teacher wallet migration...');

        $teachers = User::where('role', 'teacher')->get();
        $migratedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        $this->info("Found {$teachers->count()} teachers to process.");

        $progressBar = $this->output->createProgressBar($teachers->count());
        $progressBar->start();

        foreach ($teachers as $teacher) {
            try {
                // Check if wallet already exists
                $existingWallet = TeacherWallet::where('user_id', $teacher->id)->first();
                
                if ($existingWallet && !$this->option('force')) {
                    $skippedCount++;
                    $progressBar->advance();
                    continue;
                }

                DB::transaction(function () use ($teacher) {
                    // Get or create wallet (this will import from TeacherEarning)
                    $this->walletService->migrateTeacherEarningToWallet($teacher);
                });

                $migratedCount++;

            } catch (\Exception $e) {
                $errorCount++;
                $this->error("\nError migrating teacher {$teacher->id}: " . $e->getMessage());
            }

            $progressBar->advance();
        }

        $progressBar->finish();

        $this->newLine(2);
        $this->info("Migration completed!");
        $this->table(
            ['Status', 'Count'],
            [
                ['Migrated', $migratedCount],
                ['Skipped', $skippedCount],
                ['Errors', $errorCount],
                ['Total', $teachers->count()],
            ]
        );

        if ($errorCount > 0) {
            $this->warn("There were {$errorCount} errors during migration. Check the logs for details.");
            return self::FAILURE;
        }

        $this->info('All teacher wallets migrated successfully!');
        return self::SUCCESS;
    }
}
