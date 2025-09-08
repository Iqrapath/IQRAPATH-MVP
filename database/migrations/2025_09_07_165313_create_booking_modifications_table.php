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
        Schema::create('booking_modifications', function (Blueprint $table) {
            $table->id();
            $table->uuid('modification_uuid')->unique();
            
            // Foreign keys
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            
            // Modification type and status
            $table->enum('type', ['reschedule', 'rebook'])->index();
            $table->enum('status', [
                'pending',           // Waiting for teacher approval
                'approved',          // Teacher approved
                'rejected',          // Teacher rejected
                'expired',           // Request expired
                'cancelled',         // Student cancelled request
                'completed'          // Modification completed
            ])->default('pending')->index();
            
            // Original booking details (for reschedule)
            $table->date('original_booking_date')->nullable();
            $table->time('original_start_time')->nullable();
            $table->time('original_end_time')->nullable();
            $table->integer('original_duration_minutes')->nullable();
            
            // New booking details
            $table->date('new_booking_date');
            $table->time('new_start_time');
            $table->time('new_end_time');
            $table->integer('new_duration_minutes');
            $table->string('new_meeting_platform')->default('zoom');
            $table->text('new_meeting_url')->nullable();
            
            // For rebook - new teacher/subject
            $table->foreignId('new_teacher_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('new_subject_id')->nullable()->constrained('subjects')->onDelete('cascade');
            
            // Request details
            $table->text('reason')->nullable(); // Student's reason for modification
            $table->text('teacher_notes')->nullable(); // Teacher's response/notes
            $table->text('admin_notes')->nullable(); // Admin notes if needed
            
            // Timestamps and deadlines
            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // Auto-expire after X days
            $table->timestamp('completed_at')->nullable();
            
            // Priority and flags
            $table->boolean('is_urgent')->default(false);
            $table->boolean('requires_admin_approval')->default(false);
            $table->boolean('auto_approved')->default(false);
            
            // Financial implications
            $table->decimal('price_difference', 10, 2)->default(0.00); // Positive = student pays more, negative = refund
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'waived'])->default('pending');
            $table->text('payment_notes')->nullable();
            
            // Audit trail
            $table->json('modification_history')->nullable(); // Track all changes
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
            
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes support
            
            // Indexes for performance
            $table->index(['type', 'status']);
            $table->index(['student_id', 'status']);
            $table->index(['teacher_id', 'status']);
            $table->index(['booking_id', 'type']);
            $table->index(['expires_at', 'status']);
            $table->index(['requested_at', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_modifications');
    }
};