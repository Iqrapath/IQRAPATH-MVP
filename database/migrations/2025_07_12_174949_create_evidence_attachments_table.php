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
        Schema::create('evidence_attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable'); // polymorphic relationship (feedback, ticket, dispute, response)
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->text('description')->nullable();
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidence_attachments');
    }
};
