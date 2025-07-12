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
        Schema::create('verification_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verification_request_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'verified', 'rejected', 'live_video', 'scheduled', 'completed', 'cancelled']);
            $table->foreignId('changed_by')->nullable()->constrained('users');
            $table->timestamp('changed_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verification_audit_logs');
    }
}; 