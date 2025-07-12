<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notification_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event'); // payment.successful, class.reminder, subscription.expiry, etc.
            $table->foreignId('template_id')->constrained('notification_templates')->onDelete('cascade');
            $table->string('audience_type'); // all, role, specific_users
            $table->json('audience_filter')->nullable(); // For role-specific or other filtering criteria
            $table->json('channels')->default(json_encode(['in-app'])); // Array of channels: in-app, email, sms
            $table->string('timing_type')->nullable(); // immediate, before_event, after_event
            $table->integer('timing_value')->nullable(); // Number of minutes/hours/days
            $table->string('timing_unit')->nullable(); // minutes, hours, days
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_triggers');
    }
}; 