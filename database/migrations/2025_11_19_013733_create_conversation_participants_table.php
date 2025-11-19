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
        Schema::create('conversation_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->boolean('is_muted')->default(false)->comment('User has muted this conversation');
            $table->boolean('is_archived')->default(false)->comment('User has archived this conversation');
            $table->timestamp('last_read_at')->nullable()->comment('Last time user read messages');
            $table->timestamps();
            
            // Constraints and indexes
            $table->unique(['conversation_id', 'user_id'], 'unique_participant');
            $table->index(['user_id', 'is_archived'], 'idx_user_conversations');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_participants');
    }
};
