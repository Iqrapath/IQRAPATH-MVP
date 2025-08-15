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
        Schema::create('teaching_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_uuid')->unique();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->date('session_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('actual_duration_minutes')->nullable();
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->string('meeting_link')->nullable();
            $table->string('meeting_platform')->nullable();
            $table->string('meeting_password')->nullable();
            $table->string('zoom_meeting_id')->nullable();
            $table->string('zoom_host_id')->nullable();
            $table->string('zoom_join_url')->nullable();
            $table->string('zoom_start_url')->nullable();
            $table->string('zoom_password')->nullable();
            
            // Attendance tracking
            $table->boolean('teacher_marked_present')->default(false);
            $table->boolean('student_marked_present')->default(false);
            $table->json('attendance_data')->nullable();
            $table->timestamp('teacher_joined_at')->nullable();
            $table->timestamp('student_joined_at')->nullable();
            $table->timestamp('teacher_left_at')->nullable();
            $table->timestamp('student_left_at')->nullable();
            $table->string('recording_url')->nullable();
            $table->text('teacher_notes')->nullable();
            $table->text('student_notes')->nullable();
            
            // Completion tracking
            $table->timestamp('completion_date')->nullable();
            $table->integer('attendance_count')->default(0);
            $table->decimal('teacher_rating', 3, 2)->nullable();
            $table->decimal('student_rating', 3, 2)->nullable();
            $table->integer('notifications_sent_count')->default(0);
            $table->json('notification_history')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teaching_sessions');
    }
}; 