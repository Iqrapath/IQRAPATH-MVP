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
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->unique(); // Gateway's event ID
            $table->string('gateway'); // 'stripe', 'paystack', 'paypal'
            $table->string('type'); // Event type (e.g., 'payment_intent.succeeded')
            $table->json('payload'); // Full webhook payload
            $table->enum('status', ['pending', 'processing', 'processed', 'failed'])->default('pending');
            $table->string('idempotency_key')->unique()->nullable(); // Prevent duplicate processing
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['gateway', 'type'], 'idx_gateway_type');
            $table->index(['status', 'created_at'], 'idx_status_created');
            $table->index(['event_id'], 'idx_event_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};
