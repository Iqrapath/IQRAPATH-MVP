<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherWallet;

class TeacherWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teachers
        $teachers = User::where('role', 'teacher')->get();

        $paymentMethodOptions = [
            ['PayPal', 'Credit/Debit Card', 'Bank Transfer'],
            ['PayPal', 'Bank Transfer'],
            ['Credit/Debit Card', 'Bank Transfer'],
            ['PayPal', 'Credit/Debit Card'],
            ['PayPal', 'Credit/Debit Card', 'Bank Transfer', 'Mobile Money'],
        ];

        $defaultPaymentMethods = ['PayPal', 'Credit/Debit Card', 'Bank Transfer'];

        foreach ($teachers as $teacher) {
            // Skip if wallet already exists
            if ($teacher->teacherWallet) {
                continue;
            }

            $paymentMethods = $paymentMethodOptions[array_rand($paymentMethodOptions)];
            $defaultPaymentMethod = $paymentMethods[array_rand($paymentMethods)];

            TeacherWallet::create([
                'user_id' => $teacher->id,
                'balance' => rand(0, 5000) + (rand(0, 99) / 100), // Random balance between 0-5000
                'total_earned' => rand(10000, 100000) + (rand(0, 99) / 100), // Random earnings
                'total_withdrawn' => rand(5000, 50000) + (rand(0, 99) / 100), // Random withdrawals
                'pending_payouts' => rand(0, 2000) + (rand(0, 99) / 100), // Random pending
                'payment_methods' => $paymentMethods,
                'default_payment_method' => $defaultPaymentMethod,
                'auto_withdrawal_enabled' => rand(0, 1) === 1,
                'auto_withdrawal_threshold' => rand(0, 1) === 1 ? rand(100, 1000) : null,
                'withdrawal_settings' => [
                    'notification_enabled' => true,
                    'email_notifications' => true,
                    'currency_preference' => rand(0, 1) === 1 ? 'USD' : 'NGN',
                ],
                'last_sync_at' => now(),
            ]);
        }
    }
}