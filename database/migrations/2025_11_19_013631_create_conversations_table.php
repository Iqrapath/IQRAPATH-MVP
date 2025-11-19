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
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50)->default('direct')->comment('direct or group');
            $table->string('subject')->nullable()->comment('Optional conversation subject');
            $table->string('context_type', 50)->nullable()->comment('session, general, support');
            $table->unsignedBigInteger('context_id')->nullable()->comment('Related entity ID');
            $table->json('metadata')->nullable()->comment('Additional conversation data');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['context_type', 'context_id'], 'idx_context');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
