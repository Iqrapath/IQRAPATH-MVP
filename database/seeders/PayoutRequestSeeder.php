<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\PayoutRequest;
use App\Models\TeacherWallet;
use App\Models\PaymentMethod;
use Illuminate\Database\Seeder;

class PayoutRequestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get teachers with available balance OR pending payouts
        $teachersWithWallets = TeacherWallet::where(function($query) {
                $query->where('balance', '>', 0)
                      ->orWhere('pending_payouts', '>', 0);
            })
            ->with('user')
            ->get();

        if ($teachersWithWallets->isEmpty()) {
            $this->command->info('No teachers with wallets found. Please run TeacherWalletSeeder first.');
            return;
        }

        $statuses = ['pending', 'approved', 'processing', 'completed', 'rejected'];
        $paymentMethods = ['bank_transfer', 'paypal', 'mobile_money'];
        
        $createdCount = 0;

        foreach ($teachersWithWallets->take(15) as $wallet) {
            $teacher = $wallet->user;
            
            if (!$teacher) {
                continue;
            }

            // Get existing payment method or use first available
            $paymentMethod = PaymentMethod::where('user_id', $teacher->id)
                ->where('is_active', true)
                ->first();
            
            if (!$paymentMethod) {
                // Create a default payment method if none exists
                $methodType = $paymentMethods[array_rand($paymentMethods)];
                $paymentMethod = PaymentMethod::create([
                    'user_id' => $teacher->id,
                    'type' => $methodType,
                    'name' => 'Default Payment Method',
                    'is_default' => true,
                    'is_active' => true,
                    'is_verified' => true,
                    'details' => $this->getPaymentDetails($methodType, $teacher),
                ]);
            }
            
            // Get the method type from the payment method
            $methodType = $paymentMethod->type;

            // Create 1-2 payout requests per teacher
            $requestCount = rand(1, 2);
            
            for ($i = 0; $i < $requestCount; $i++) {
                // 60% pending, 20% approved, 10% completed, 10% rejected
                $rand = rand(1, 100);
                if ($rand <= 60) {
                    $status = 'pending';
                } elseif ($rand <= 80) {
                    $status = 'approved';
                } elseif ($rand <= 90) {
                    $status = 'completed';
                } else {
                    $status = 'rejected';
                }
                
                // Calculate amount based on available balance
                $availableBalance = $wallet->balance;
                
                if ($availableBalance < 100) {
                    // Not enough balance for a payout
                    continue;
                }
                
                // Request between 10% and 80% of available balance
                $minAmount = max(100, $availableBalance * 0.1);
                $maxAmount = min($availableBalance * 0.8, 50000);
                
                if ($maxAmount < $minAmount) {
                    $maxAmount = $minAmount;
                }
                
                $amount = rand((int)$minAmount, (int)$maxAmount);

                $payoutRequest = PayoutRequest::create([
                    'teacher_id' => $teacher->id,
                    'amount' => $amount,
                    'currency' => 'NGN',
                    'payment_method' => $methodType,
                    'payment_details' => $this->getPaymentDetails($methodType, $teacher),
                    'status' => $status,
                    'request_date' => now()->subDays(rand(0, 30)),
                    'processed_date' => in_array($status, ['approved', 'processing', 'completed', 'rejected']) 
                        ? now()->subDays(rand(0, 15)) 
                        : null,
                    'notes' => $this->getNotesForStatus($status),
                ]);

                // Update wallet for pending requests
                if ($status === 'pending') {
                    $wallet->balance -= $amount;
                    $wallet->pending_payouts += $amount;
                    $wallet->save();
                }

                // Update wallet for completed requests
                if ($status === 'completed') {
                    $wallet->total_withdrawn += $amount;
                    $wallet->save();
                }

                $createdCount++;
            }
        }

        $this->command->info("Created {$createdCount} payout requests for testing.");
    }

    /**
     * Get payment details based on method type
     */
    private function getPaymentDetails(string $method, User $teacher): array
    {
        return match($method) {
            'bank_transfer' => [
                // Use PayStack test account number for TEST mode
                // In production, use real account numbers
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789', // PayStack official test account
                'account_name' => $teacher->name,
            ],
            'paypal' => [
                'paypal_email' => $teacher->email,
            ],
            'mobile_money' => [
                // For mobile money, PayStack still uses bank account format
                // Use test account for mobile money providers
                'bank_name' => 'Access Bank',
                'account_number' => '0123456789', // PayStack official test account
                'account_name' => $teacher->name,
                'provider' => ['MTN', 'Airtel', 'Glo', '9mobile'][array_rand(['MTN', 'Airtel', 'Glo', '9mobile'])],
                'phone_number' => '0' . rand(7000000000, 9099999999),
            ],
            default => [],
        };
    }

    /**
     * Get notes based on status
     */
    private function getNotesForStatus(string $status): ?string
    {
        return match($status) {
            'pending' => null,
            'approved' => 'Approved by admin. Payment processing initiated.',
            'processing' => 'Payment is being processed by the payment gateway.',
            'completed' => 'Payment successfully transferred to teacher account.',
            'rejected' => 'Request rejected due to incomplete payment information.',
            default => null,
        };
    }
}
