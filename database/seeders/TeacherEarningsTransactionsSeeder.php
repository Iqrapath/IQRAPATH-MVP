<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TeacherEarningsTransactionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teacher with ID 14 (Ahmad Ali)
        $teacherId = 14;
        
        // Get teacher wallet
        $teacherWallet = DB::table('teacher_wallets')->where('user_id', $teacherId)->first();
        
        if (!$teacherWallet) {
            $this->command->error('No teacher wallet found for teacher ID: ' . $teacherId);
            return;
        }
        
        // Get completed teaching sessions for this teacher
        $completedSessions = DB::table('teaching_sessions')
            ->where('teacher_id', $teacherId)
            ->where('status', 'completed')
            ->get();
        
        if ($completedSessions->count() === 0) {
            $this->command->warn('No completed sessions found. Creating sample transactions without sessions.');
        }
        
        // Get teacher rates
        $teacherProfile = DB::table('teacher_profiles')->where('user_id', $teacherId)->first();
        $hourlyRateNGN = $teacherProfile->hourly_rate_ngn ?? 42000;
        $hourlyRateUSD = $teacherProfile->hourly_rate_usd ?? 35;
        
        // Create unified transactions for recent earnings
        $transactions = [];
        
        // If we have completed sessions, create transactions for them
        if ($completedSessions->count() > 0) {
            foreach ($completedSessions as $index => $session) {
                // Calculate session duration
                $startTime = Carbon::parse($session->start_time);
                $endTime = Carbon::parse($session->end_time);
                $durationHours = $startTime->diffInHours($endTime);
                if ($durationHours == 0) $durationHours = 1; // Minimum 1 hour
                
                $amountNGN = $hourlyRateNGN * $durationHours;
                $amountUSD = $hourlyRateUSD * $durationHours;
                
                $transactions[] = [
                    'transaction_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                    'wallet_type' => 'App\\Models\\TeacherWallet',
                    'wallet_id' => $teacherWallet->id,
                    'session_id' => $session->id,
                    'transaction_type' => 'credit',  // Teacher receives credit for earnings
                    'amount' => $amountNGN,
                    'currency' => 'NGN',
                    'status' => 'completed',
                    'transaction_date' => Carbon::parse($session->session_date),
                    'description' => 'Earnings from teaching session',
                    'created_at' => Carbon::parse($session->session_date),
                    'updated_at' => Carbon::parse($session->session_date),
                ];
            }
        } else {
            // Create sample transactions without sessions (for testing)
            $student = DB::table('users')->where('role', 'student')->first();
            $subject = DB::table('subjects')->first();
            
            if ($student && $subject) {
                for ($i = 1; $i <= 5; $i++) {
                    $transactions[] = [
                        'transaction_uuid' => \Illuminate\Support\Str::uuid()->toString(),
                        'wallet_type' => 'App\\Models\\TeacherWallet',
                        'wallet_id' => $teacherWallet->id,
                        'session_id' => null,
                        'transaction_type' => 'credit',  // Teacher receives credit for earnings
                        'amount' => $hourlyRateNGN,
                        'currency' => 'NGN',
                        'status' => 'completed',
                        'transaction_date' => Carbon::now()->subDays($i * 3),
                        'description' => 'Earnings from teaching session',
                        'created_at' => Carbon::now()->subDays($i * 3),
                        'updated_at' => Carbon::now()->subDays($i * 3),
                    ];
                }
            }
        }
        
        // Insert transactions
        if (count($transactions) > 0) {
            DB::table('unified_transactions')->insert($transactions);
            
            $this->command->info('✅ Created ' . count($transactions) . ' earning transactions for teacher ID: ' . $teacherId);
            $this->command->info('   These will appear in "Recent Transaction" section');
        } else {
            $this->command->error('No transactions created. Please check data.');
        }
    }
}
