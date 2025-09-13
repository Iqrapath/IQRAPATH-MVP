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
        Schema::create('teacher_availabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->boolean('holiday_mode')->default(false); // Holiday mode affects entire account
            $table->json('available_days')->nullable(); // Store selected days as JSON array
            $table->json('day_schedules')->nullable(); // Store detailed day schedules as JSON
            $table->tinyInteger('day_of_week')->nullable(); // 0-6 (Sunday-Saturday) - for backward compatibility
            $table->time('start_time')->nullable(); // for backward compatibility
            $table->time('end_time')->nullable(); // for backward compatibility
            $table->boolean('is_active')->default(true);
            $table->string('time_zone')->nullable();
            $table->string('preferred_teaching_hours')->nullable();
            $table->enum('availability_type', ['Part-Time', 'Full-Time'])->default('Part-Time');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_availabilities');
    }
}; 