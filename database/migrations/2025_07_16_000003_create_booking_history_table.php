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
        Schema::create('booking_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->enum('action', [
                'created', 'approved', 'rejected', 'rescheduled', 
                'cancelled', 'teacher_reassigned', 'completed', 'missed'
            ]);
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->foreignId('performed_by_id')->constrained('users');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_history');
    }
}; 