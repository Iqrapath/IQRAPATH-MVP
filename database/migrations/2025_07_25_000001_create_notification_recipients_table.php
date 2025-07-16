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
        Schema::create('notification_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending'); // pending, sent, delivered, read, failed
            $table->string('channel')->default('in-app'); // in-app, email, sms
            $table->timestamp('delivered_at')->nullable();
            $table->json('personalized_content')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate notifications to the same user via the same channel
            $table->unique(['notification_id', 'user_id', 'channel']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_recipients');
    }
}; 