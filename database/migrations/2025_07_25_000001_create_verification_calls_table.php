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
        Schema::create('verification_calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->constrained()->onDelete('cascade');
            $table->timestamp('scheduled_at');
            $table->enum('platform', ['zoom', 'google_meet', 'other']);
            $table->string('meeting_link');
            $table->text('notes')->nullable();
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_calls');
    }
}; 