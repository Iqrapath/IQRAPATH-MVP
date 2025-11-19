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
        Schema::create('message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('messages')->onDelete('cascade');
            $table->string('filename')->comment('Stored filename');
            $table->string('original_filename')->comment('Original uploaded filename');
            $table->string('file_path', 500)->comment('Storage path');
            $table->unsignedBigInteger('file_size')->comment('File size in bytes');
            $table->string('mime_type', 100)->comment('File MIME type');
            $table->json('metadata')->nullable()->comment('Additional file metadata');
            $table->timestamps();
            
            // Index for performance
            $table->index('message_id', 'idx_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_attachments');
    }
};
