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
        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('feature_key', 100)->unique();
            $table->boolean('is_enabled')->default(false);
            $table->string('description')->nullable();
            $table->timestamps();
        });

        // Insert default feature flags
        $this->seedDefaultFeatureFlags();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_flags');
    }

    /**
     * Seed default feature flags.
     */
    private function seedDefaultFeatureFlags(): void
    {
        $features = [
            ['auto_payouts', true, 'Enable automatic payouts to teachers'],
            ['referral_program', true, 'Enable referral program for users'],
            ['email_verification', true, 'Require email verification on signup'],
            ['teacher_withdrawals', true, 'Allow teachers to request withdrawals'],
            ['blog_comments', false, 'Enable comments on blog posts'],
            ['sms_notifications', true, 'Enable SMS notifications'],
        ];

        $now = now();

        foreach ($features as [$key, $enabled, $description]) {
            DB::table('feature_flags')->insert([
                'feature_key' => $key,
                'is_enabled' => $enabled,
                'description' => $description,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
