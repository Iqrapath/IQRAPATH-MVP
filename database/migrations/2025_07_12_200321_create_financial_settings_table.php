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
            ['instant_payouts_enabled', 'true'],
            ['multi_currency_mode', 'true'],
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
