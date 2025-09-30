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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_uuid')->unique();
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('booking_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->enum('status', [
                'pending', 'approved', 'rejected', 'upcoming', 
                'completed', 'missed', 'cancelled'
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('approved_by_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('cancelled_by_id')->nullable()->constrained('users');
            $table->timestamp('cancelled_at')->nullable();
            
            // Rate locking fields
            $table->decimal('hourly_rate_ngn', 10, 2)->nullable();
            $table->decimal('hourly_rate_usd', 8, 2)->nullable();
            $table->string('rate_currency', 3)->default('NGN');
            $table->decimal('exchange_rate_used', 10, 6)->nullable();
            $table->timestamp('rate_locked_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
}; 