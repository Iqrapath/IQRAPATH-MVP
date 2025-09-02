<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\TeacherWallet;
use App\Models\PaymentMethod;

class TeacherWalletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all teachers
        $teachers = User::where('role', 'teacher')->get();

        $paymentMethodTemplates = [
            [
                'type' => 'bank_transfer',
                'name' => 'Primary Bank Account',
                'details' => [
                    'bank_name' => 'First City Monument Bank',
                    'account_holder' => 'Teacher Account',
                    'account_number' => '1234567890',
                    'swift_code' => 'FCMBNGLAXXX',
                ]
            ],
            [
                'type' => 'mobile_money',
                'name' => 'MTN Mobile Money',
                'details' => [
                    'provider' => 'MTN',
                    'phone_number' => '+234802123456',
                ]
            ],
            [
                'type' => 'bank_transfer',
                'name' => 'Secondary Bank',
                'details' => [
                    'bank_name' => 'Guaranty Trust Bank',
                    'account_holder' => 'Teacher Account',
                    'account_number' => '0987654321',
                    'swift_code' => 'GTBINGLAXXX',
                ]
            ],
        ];

        foreach ($teachers as $teacher) {
            // Skip if wallet already exists
            if ($teacher->teacherWallet) {
                continue;
            }

            // Create 1-2 payment methods for each teacher
            $numPaymentMethods = rand(1, 2);
            $defaultPaymentMethodId = null;

            for ($i = 0; $i < $numPaymentMethods; $i++) {
                $template = $paymentMethodTemplates[array_rand($paymentMethodTemplates)];
                
                // Customize the template for this teacher
                $details = $template['details'];
                if ($template['type'] === 'bank_transfer') {
                    $details['account_holder'] = $teacher->name;
                    $details['account_number'] = '12345' . str_pad((string)$teacher->id, 5, '0', STR_PAD_LEFT);
                } elseif ($template['type'] === 'mobile_money') {
                    $details['phone_number'] = '+234' . rand(800, 909) . rand(1000000, 9999999);
                }

                $paymentMethod = PaymentMethod::create([
                    'user_id' => $teacher->id,
                    'type' => $template['type'],
                    'name' => $template['name'] . ($i > 0 ? ' ' . ($i + 1) : ''),
                    'details' => $details,
                    'is_default' => $i === 0, // First payment method is default
                    'is_active' => true,
                ]);

                if ($i === 0) {
                    $defaultPaymentMethodId = $paymentMethod->id;
                }
            }

            TeacherWallet::create([
                'user_id' => $teacher->id,
                'balance' => rand(0, 5000) + (rand(0, 99) / 100), // Random balance between 0-5000
                'total_earned' => rand(10000, 100000) + (rand(0, 99) / 100), // Random earnings
                'total_withdrawn' => rand(5000, 50000) + (rand(0, 99) / 100), // Random withdrawals
                'pending_payouts' => rand(0, 2000) + (rand(0, 99) / 100), // Random pending
                'default_payment_method_id' => $defaultPaymentMethodId,
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