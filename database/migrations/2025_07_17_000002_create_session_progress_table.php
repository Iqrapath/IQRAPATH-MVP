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
        Schema::create('session_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('teaching_sessions')->onDelete('cascade');
            $table->string('topic_covered');
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->text('teacher_assessment')->nullable();
            $table->text('next_steps')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_progress');
    }
}; 