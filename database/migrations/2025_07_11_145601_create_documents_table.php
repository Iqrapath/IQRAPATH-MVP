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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_profile_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['id_verification', 'certificate', 'resume']);
            $table->string('name');
            $table->string('path');
            $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users');
            $table->text('rejection_reason')->nullable();
            $table->json('metadata')->nullable();
            $table->unsignedTinyInteger('resubmission_count')->default(0);
            $table->unsignedTinyInteger('max_resubmissions')->default(3);
            $table->foreignId('uploaded_by')->nullable()->constrained('users');
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
