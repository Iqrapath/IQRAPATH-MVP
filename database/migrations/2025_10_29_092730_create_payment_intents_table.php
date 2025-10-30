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
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->uuid('intent_uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->nullable()->constrained()->onDelete('set null');
            
            // Payment details
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('description')->nullable();
            
            // Gateway integration
            $table->string('gateway'); // 'stripe', 'paystack'
            $table->string('gateway_intent_id')->nullable(); // Intent ID from gateway
            $table->string('gateway_client_secret')->nullable(); // For client-side confirmation
            
            // Intent status
            $table->enum('status', [
                'pending',      // Intent created, awaiting payment
                'processing',   // Payment being processed
                'requires_action', // Requires user action (3D Secure)
                'succeeded',    // Payment successful
                'failed',       // Payment failed
                'canceled',     // Intent canceled
                'expired'       // Intent expired
            ])->default('pending');
            
            // Related entities
            $table->string('reference_type')->nullable(); // 'subscription', 'wallet_funding', 'booking'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related entity
            
            // Error tracking
            $table->string('failure_code')->nullable();
            $table->text('failure_message')->nullable();
            
            // Metadata
            $table->json('metadata')->nullable();
            
            // Timestamps
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['user_id', 'status'], 'idx_user_status');
            $table->index(['gateway', 'gateway_intent_id'], 'idx_gateway_intent');
            $table->index(['reference_type', 'reference_id'], 'idx_reference');
            $table->index(['status', 'expires_at'], 'idx_status_expiry');
            $table->index(['created_at'], 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
