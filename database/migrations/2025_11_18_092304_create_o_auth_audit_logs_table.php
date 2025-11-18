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
        Schema::create('oauth_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('event'); // 'initiated', 'callback_success', 'callback_failure', 'account_linked', 'provider_mismatch', 'error'
            $table->string('provider'); // 'google', 'facebook'
            $table->string('provider_id')->nullable();
            $table->string('email')->nullable();
            $table->string('intended_role')->nullable();
            $table->json('metadata')->nullable(); // Additional context
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at');
            
            // Indexes for common queries
            $table->index(['user_id', 'created_at']);
            $table->index(['email', 'created_at']);
            $table->index(['provider', 'created_at']);
            $table->index('event');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('oauth_audit_logs');
    }
};
