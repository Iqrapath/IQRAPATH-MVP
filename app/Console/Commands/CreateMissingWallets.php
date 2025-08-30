<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\UnifiedWalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateMissingWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:create-missing
                          {--role= : Create wallets only for specific role (student, teacher, guardian)}
                          {--dry-run : Show what would be created without actually creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create missing wallets for existing users who don\'t have them';

    /**
     * The unified wallet service instance.
     *
     * @var \App\Services\UnifiedWalletService
     */
    protected $walletService;

    /**
     * Create a new command instance.
     */
    public function __construct(UnifiedWalletService $walletService)
    {
        parent::__construct();
        $this->walletService = $walletService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking for users without wallets...');
        
        $role = $this->option('role');
        $dryRun = $this->option('dry-run');
        
        $validRoles = ['student', 'teacher', 'guardian'];
        $rolesToProcess = $role ? [$role] : $validRoles;
        
        // Validate role if specified
        if ($role && !in_array($role, $validRoles)) {
            $this->error("Invalid role: {$role}. Valid roles are: " . implode(', ', $validRoles));
            return 1;
        }
        
        $totalCreated = 0;
        $totalErrors = 0;
        
        foreach ($rolesToProcess as $userRole) {
            $this->info("\n📋 Processing {$userRole}s...");
            
            $usersWithoutWallets = $this->getUsersWithoutWallet($userRole);
            $count = $usersWithoutWallets->count();
            
            if ($count === 0) {
                $this->line("✅ All {$userRole}s already have wallets!");
                continue;
            }
            
            if ($dryRun) {
                $this->warn("🔍 DRY RUN: Would create {$count} {$userRole} wallets");
                $usersWithoutWallets->each(function ($user) {
                    $this->line("  - {$user->name} (ID: {$user->id}, Email: {$user->email})");
                });
                continue;
            }
            
            $progressBar = $this->output->createProgressBar($count);
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - Creating %message%');
            $progressBar->start();
            
            $created = 0;
            $errors = 0;
            
            $usersWithoutWallets->each(function ($user) use (&$created, &$errors, $progressBar) {
                $progressBar->setMessage($user->name);
                $progressBar->advance();
                
                try {
                    $wallet = $this->walletService->getWalletForUser($user);
                    $created++;
                    
                    $this->line("\n✅ Created {$user->role} wallet for {$user->name} (ID: {$wallet->id})");
                } catch (\Exception $e) {
                    $errors++;
                    $this->line("\n❌ Failed to create wallet for {$user->name}: {$e->getMessage()}");
                }
            });
            
            $progressBar->finish();
            $this->newLine(2);
            
            $totalCreated += $created;
            $totalErrors += $errors;
            
            $this->info("✅ Created {$created} {$userRole} wallets");
            if ($errors > 0) {
                $this->warn("⚠️  {$errors} errors occurred");
            }
        }
        
        // Summary
        $this->newLine();
        $this->info('📊 SUMMARY');
        $this->info("Total wallets created: {$totalCreated}");
        
        if ($totalErrors > 0) {
            $this->warn("Total errors: {$totalErrors}");
            return 1;
        }
        
        $this->info('🎉 All wallets created successfully!');
        return 0;
    }
    
    /**
     * Get users without wallets for a specific role.
     */
    private function getUsersWithoutWallet(string $role): \Illuminate\Database\Eloquent\Collection
    {
        $walletTable = match ($role) {
            'student' => 'student_wallets',
            'teacher' => 'teacher_wallets', 
            'guardian' => 'guardian_wallets',
        };
        
        return User::where('role', $role)
            ->whereDoesntHave($role . 'Wallet')
            ->get();
    }
}