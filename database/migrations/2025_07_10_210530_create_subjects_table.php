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
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_template_id')->constrained()->onDelete('cascade');
            $table->text('teacher_notes')->nullable(); // Teacher's specific approach
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // One teacher can only have one instance of each subject template
            $table->unique(['teacher_profile_id', 'subject_template_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
