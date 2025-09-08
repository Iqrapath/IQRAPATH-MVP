<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\StudentWallet;
use App\Models\TeacherWallet;
use App\Models\GuardianWallet;

class UpdateWalletPaymentIds extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wallets:update-payment-ids';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update existing wallets with unique payment IDs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating wallet payment IDs...');

        // Update Student Wallets
        $studentWallets = StudentWallet::whereNull('payment_id')->get();
        $this->info("Found {$studentWallets->count()} student wallets without payment IDs");

        foreach ($studentWallets as $wallet) {
            $wallet->payment_id = StudentWallet::generateUniquePaymentId();
            $wallet->save();
            $this->line("Updated student wallet {$wallet->id} with payment ID: {$wallet->payment_id}");
        }

        // Update Teacher Wallets (if they have payment_id field)
        if (Schema::hasColumn('teacher_wallets', 'payment_id')) {
            $teacherWallets = TeacherWallet::whereNull('payment_id')->get();
            $this->info("Found {$teacherWallets->count()} teacher wallets without payment IDs");

            foreach ($teacherWallets as $wallet) {
                $wallet->payment_id = $this->generateUniquePaymentId('teacher');
                $wallet->save();
                $this->line("Updated teacher wallet {$wallet->id} with payment ID: {$wallet->payment_id}");
            }
        }

        // Update Guardian Wallets (if they have payment_id field)
        if (Schema::hasColumn('guardian_wallets', 'payment_id')) {
            $guardianWallets = GuardianWallet::whereNull('payment_id')->get();
            $this->info("Found {$guardianWallets->count()} guardian wallets without payment IDs");

            foreach ($guardianWallets as $wallet) {
                $wallet->payment_id = $this->generateUniquePaymentId('guardian');
                $wallet->save();
                $this->line("Updated guardian wallet {$wallet->id} with payment ID: {$wallet->payment_id}");
            }
        }

        $this->info('Wallet payment IDs updated successfully!');
    }

    /**
     * Generate a unique payment ID for different wallet types.
     * Format: IQR-{type}-{timestamp}-{random} (e.g., IQR-STU-1704067200-A7B9)
     */
    private function generateUniquePaymentId(string $type): string
    {
        $typeCode = match($type) {
            'teacher' => 'TCH',
            'guardian' => 'GRD',
            default => 'STU'
        };

        do {
            $timestamp = time();
            $random = strtoupper(substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 4));
            $paymentId = "IQR-{$typeCode}-{$timestamp}-{$random}";
        } while (
            StudentWallet::where('payment_id', $paymentId)->exists() ||
            TeacherWallet::where('payment_id', $paymentId)->exists() ||
            GuardianWallet::where('payment_id', $paymentId)->exists()
        );

        return $paymentId;
    }
}