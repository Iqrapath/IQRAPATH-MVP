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
        Schema::create('authorization_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('action'); // 'view_conversation', 'send_message', 'delete_message', etc.
            $table->string('resource_type'); // 'Conversation', 'Message'
            $table->unsignedBigInteger('resource_id');
            $table->boolean('granted');
            $table->string('reason')->nullable();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Indexes for efficient querying
            $table->index(['user_id', 'created_at']);
            $table->index(['action', 'granted']);
            $table->index(['resource_type', 'resource_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('authorization_audit_logs');
    }
};
