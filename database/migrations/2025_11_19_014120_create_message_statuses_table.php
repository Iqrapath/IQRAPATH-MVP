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
        Schema::create('message_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('status', 50)->comment('sent, delivered, read');
            $table->timestamp('status_at')->comment('When status was set');
            $table->timestamps();
            
            // Constraints and indexes
            $table->unique(['message_id', 'user_id'], 'unique_user_message_status');
            
            // Only add index if not SQLite (to avoid duplicate index issues in tests)
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->index(['user_id', 'status'], 'idx_user_status');
            }
        });
        
        // For SQLite, add index separately
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            try {
                Schema::table('message_statuses', function (Blueprint $table) {
                    $table->index(['user_id', 'status'], 'idx_user_status');
                });
            } catch (\Exception $e) {
                // Index might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_statuses');
    }
};
