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
        Schema::create('booking_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('notification_type', [
                'booking_created', 'booking_approved', 'booking_rejected', 
                'reminder', 'rescheduled', 'cancelled', 'teacher_reassigned',
                'session_starting_soon', 'session_started', 'session_completed'
            ]);
            $table->enum('channel', ['in_app', 'email', 'sms', 'push'])->default('in_app');
            $table->string('title');
            $table->text('message');
            $table->json('metadata')->nullable(); // Store additional data like meeting links, etc.
            $table->boolean('is_read')->default(false);
            $table->boolean('is_sent')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('scheduled_at')->nullable(); // For scheduled notifications
            $table->timestamps();
            
            $table->index(['user_id', 'is_read']);
            $table->index(['booking_id', 'notification_type']);
            $table->index(['scheduled_at', 'is_sent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_notifications');
    }
}; 