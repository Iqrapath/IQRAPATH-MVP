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
        Schema::create('system_settings', function (Blueprint $table) {
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
        Schema::dropIfExists('system_settings');
    }

    /**
     * Seed default system settings.
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            ['platform_name', 'IQRAQUEST'],
            ['logo_path', 'assets/images/logo/IqraQuest-logo.png'],
            ['support_email', 'support@iqraquest.com'],
            ['office_address', 'IQRAQUEST Headquarters, 123 Business Lane, Lagos, Nigeria'],
            ['contact_number', '+234 700 123 4567'],
            ['whatsapp_number', '+234 700 123 4567'],
            ['platform_language', 'English'],
            ['time_zone', 'Africa/Lagos'],
            ['date_format', 'DD/MM/YYYY'],
            ['default_landing_page', 'home'],
        ];

        $now = now();

        foreach ($settings as [$key, $value]) {
            DB::table('system_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
