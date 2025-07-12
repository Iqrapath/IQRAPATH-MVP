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
        Schema::create('disputes', function (Blueprint $table) {
            $table->id();
            $table->string('complaint_id')->unique(); // Format: DSP-XXXXX
            $table->foreignId('filed_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('against')->constrained('users')->onDelete('cascade');
            $table->string('subject');
            $table->text('issue');
            $table->enum('status', ['open', 'under_review', 'resolved', 'dismissed'])->default('open');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('disputes');
    }
};
