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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('date_of_birth')->nullable();
            $table->string('grade_level')->nullable();
            $table->string('school_name')->nullable();
            $table->foreignId('guardian_id')->nullable()->constrained('users');
            $table->text('learning_goals')->nullable();
            $table->json('subjects_of_interest')->nullable();
            $table->json('preferred_learning_times')->nullable();
            $table->string('age_group')->nullable();
            $table->string('payment_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
