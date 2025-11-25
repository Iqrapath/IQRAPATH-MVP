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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('conversations')->onDelete('cascade');
            $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
            $table->text('content')->comment('Message content');
            $table->string('type', 50)->default('text')->comment('text, image, file, system');
            $table->json('metadata')->nullable()->comment('Additional message data');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['conversation_id', 'created_at'], 'idx_conversation_messages');
            $table->index('sender_id', 'idx_sender');
        });
        
        // Fulltext index for search (MySQL only)
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::table('messages', function (Blueprint $table) {
                $table->fullText('content', 'idx_content_search');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
