<?php

namespace Database\Seeders;

use App\Models\FinancialSetting;
use Illuminate\Database\Seeder;

class FinancialSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            ['setting_key' => 'commission_rate', 'setting_value' => '10'],
            ['setting_key' => 'commission_type', 'setting_value' => 'fixed_percentage'],
            ['setting_key' => 'auto_payout_threshold', 'setting_value' => '50000'],
            ['setting_key' => 'minimum_withdrawal_amount', 'setting_value' => '10000'],
            ['setting_key' => 'bank_verification_enabled', 'setting_value' => 'true'],
            ['setting_key' => 'withdrawal_note', 'setting_value' => 'Withdrawals are processed within 1-3 business days.'],
        ];

        foreach ($settings as $setting) {
            FinancialSetting::updateOrCreate(
                ['setting_key' => $setting['setting_key']],
                ['setting_value' => $setting['setting_value']]
            );
        }
    }
}
