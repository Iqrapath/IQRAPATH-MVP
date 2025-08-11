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
        Schema::create('teacher_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('bio')->nullable();
            $table->string('experience_years')->nullable();
            $table->boolean('verified')->default(false);
            $table->json('languages')->nullable();
            $table->string('teaching_type')->nullable(); // Online, In-person, etc.
            $table->string('teaching_mode')->nullable(); // One-to-One, Group, etc.
            $table->string('intro_video_url')->nullable();
            $table->string('education')->nullable();
            $table->string('qualification')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->integer('reviews_count')->default(0);
            $table->timestamp('join_date')->useCurrent(); // Track when teacher joined the platform
            $table->decimal('hourly_rate_usd', 8, 2)->nullable(); // Hourly rate in USD
            $table->decimal('hourly_rate_ngn', 10, 2)->nullable(); // Hourly rate in Nigerian Naira
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_profiles');
    }
};
