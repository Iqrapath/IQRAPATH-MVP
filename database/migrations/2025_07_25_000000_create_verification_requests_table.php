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
        Schema::create('verification_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'verified', 'rejected', 'live_video'])->default('pending');
            $table->enum('docs_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->enum('video_status', ['not_scheduled', 'scheduled', 'completed'])->default('not_scheduled');
            $table->timestamp('scheduled_call_at')->nullable();
            $table->enum('video_platform', ['zoom', 'google_meet', 'other'])->nullable();
            $table->string('meeting_link')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_requests');
    }
}; 