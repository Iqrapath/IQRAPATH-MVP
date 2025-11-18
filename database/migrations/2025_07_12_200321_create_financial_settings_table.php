<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('financial_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->timestamps();
        });

        // Insert default settings
        $this->seedDefaultSettings();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_settings');
    }

    /**
     * Seed default financial settings.
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            ['platform_currency', 'NGN'],
            ['commission_rate', '10'],
            ['commission_type', 'fixed_percentage'],
            ['auto_payout_threshold', '50000'],
            ['minimum_withdrawal_amount', '10000'],
            ['bank_verification_enabled', 'true'],
            ['withdrawal_note', 'Withdrawals are processed within 1-3 business days.'],
            ['instant_payouts_enabled', 'true'],
            ['multi_currency_mode', 'true'],
            
            // Withdrawal limits
            ['daily_withdrawal_limit', '500000'],
            ['monthly_withdrawal_limit', '5000000'],
            
            // Withdrawal fees by method
            ['bank_transfer_fee_type', 'flat'],
            ['bank_transfer_fee_amount', '100'],
            ['mobile_money_fee_type', 'percentage'],
            ['mobile_money_fee_amount', '2.5'],
            ['cryptocurrency_fee_type', 'flat'],
            ['cryptocurrency_fee_amount', '500'],
            ['paypal_fee_type', 'percentage'],
            ['paypal_fee_amount', '3.5'],
            ['skrill_fee_type', 'percentage'],
            ['skrill_fee_amount', '2.9'],
            ['flutterwave_fee_type', 'flat'],
            ['flutterwave_fee_amount', '50'],
            ['paystack_fee_type', 'flat'],
            ['paystack_fee_amount', '100'],
            ['stripe_fee_type', 'percentage'],
            ['stripe_fee_amount', '2.9'],
            
            // Processing times
            ['bank_transfer_processing_time', '1-3 business days'],
            ['mobile_money_processing_time', 'Instant'],
            ['cryptocurrency_processing_time', '30 minutes - 2 hours'],
            ['paypal_processing_time', 'Instant'],
            ['skrill_processing_time', 'Instant'],
            ['flutterwave_processing_time', '1-2 business days'],
            ['paystack_processing_time', '1-2 business days'],
            ['stripe_processing_time', '1-2 business days'],
        ];

        $now = now();

        foreach ($settings as [$key, $value]) {
            DB::table('financial_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
