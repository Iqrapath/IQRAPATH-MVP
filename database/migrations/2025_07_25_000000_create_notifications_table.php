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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('body');
            $table->string('type')->default('custom'); // custom, system, payment, class, subscription, feature
            $table->string('status')->default('draft'); // draft, scheduled, sent, delivered, read, failed
            $table->string('sender_type')->default('system'); // system, admin, teacher
            $table->unsignedBigInteger('sender_id')->nullable(); // User ID if sender is admin or teacher
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->json('metadata')->nullable(); // For additional data like payment details
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
}; 