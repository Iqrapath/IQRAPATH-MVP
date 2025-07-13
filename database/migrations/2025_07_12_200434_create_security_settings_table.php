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
        Schema::create('security_settings', function (Blueprint $table) {
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
        Schema::dropIfExists('security_settings');
    }

    /**
     * Seed default security settings.
     */
    private function seedDefaultSettings(): void
    {
        $settings = [
            ['two_factor_auth_enabled', 'true'],
            ['password_strength_requirement', 'true'],
            ['max_login_attempts', '5'],
            ['session_timeout_duration', '20'], // minutes
        ];

        $now = now();

        foreach ($settings as [$key, $value]) {
            DB::table('security_settings')->insert([
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
