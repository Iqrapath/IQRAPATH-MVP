<?php

namespace Database\Seeders;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teachers
        $teachers = User::where('role', 'teacher')->get();

        if ($teachers->isEmpty()) {
            $this->command->warn('No teachers found. Please seed users first.');
            return;
        }

        $this->command->info('Creating transactions for ' . $teachers->count() . ' teachers...');

        foreach ($teachers as $teacher) {
            // Create 5-15 session payment transactions for each teacher
            $sessionCount = rand(5, 15);
            Transaction::factory()
                ->count($sessionCount)
                ->forTeacher($teacher)
                ->create();

            // Create 1-3 withdrawal transactions for some teachers (70% chance)
            if (rand(1, 100) <= 70) {
                $withdrawalCount = rand(1, 3);
                Transaction::factory()
                    ->count($withdrawalCount)
                    ->withdrawal($teacher)
                    ->create();
            }

            // Create 0-2 referral bonuses for some teachers (30% chance)
            if (rand(1, 100) <= 30) {
                $referralCount = rand(1, 2);
                Transaction::factory()
                    ->count($referralCount)
                    ->referralBonus($teacher)
                    ->create();
            }

            $this->command->info("Created transactions for teacher: {$teacher->name}");
        }

        $totalTransactions = Transaction::count();
        $this->command->info("Total transactions created: {$totalTransactions}");
    }
}
