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
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'bank_transfer', 'mobile_money', 'card', etc.
            $table->string('name'); // User-defined name for the method
            
            // Gateway integration fields
            $table->string('gateway')->nullable(); // 'stripe', 'paystack', etc.
            $table->string('gateway_token')->nullable(); // Tokenized payment method from gateway
            $table->string('gateway_customer_id')->nullable(); // Customer ID in gateway system
            
            // Card-specific fields
            $table->string('last_four', 4)->nullable(); // Last 4 digits of card/account
            $table->string('card_brand')->nullable(); // 'visa', 'mastercard', etc.
            $table->string('card_number_prefix', 4)->nullable(); // First 4 digits for display
            $table->string('card_number_middle', 4)->nullable(); // Middle 4 digits for display
            $table->unsignedTinyInteger('exp_month')->nullable(); // 1-12
            $table->unsignedSmallInteger('exp_year')->nullable(); // YYYY
            $table->string('stripe_payment_method_id')->nullable(); // Stripe payment method ID
            
            // Bank account fields
            $table->string('bank_name')->nullable();
            $table->string('bank_code')->nullable();
            $table->string('account_name')->nullable();
            $table->string('account_number')->nullable(); // Masked or last 4 only
            
            // Mobile money fields
            $table->string('phone_number')->nullable();
            $table->string('provider')->nullable(); // 'MTN', 'Airtel', etc.
            
            // Currency and limits
            $table->string('currency', 3)->default('NGN'); // 'USD', 'NGN'
            $table->decimal('daily_limit', 10, 2)->nullable();
            $table->decimal('transaction_limit', 10, 2)->nullable();
            
            // Status and verification
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_verified')->default(false);
            $table->enum('verification_status', ['pending', 'verified', 'failed'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            
            // Expiry tracking (for cards)
            $table->date('expires_at')->nullable();
            
            // Usage tracking
            $table->timestamp('last_used_at')->nullable();
            $table->unsignedInteger('usage_count')->default(0);
            
            // Additional metadata
            $table->json('details')->nullable(); // Legacy field for backward compatibility
            $table->json('metadata')->nullable(); // Additional gateway-specific data
            
            $table->timestamps();
            $table->softDeletes(); // Audit trail
            
            // Composite indexes for better query performance
            $table->index(['user_id', 'type', 'is_active'], 'idx_user_type_active');
            $table->index(['user_id', 'is_default'], 'idx_user_default');
            $table->index(['verification_status'], 'idx_verification_status');
            $table->index(['expires_at'], 'idx_expires_at');
            $table->index(['gateway', 'gateway_token'], 'idx_gateway_token');
            $table->index(['exp_year', 'exp_month'], 'idx_card_expiry');
            
            // Unique constraint to prevent duplicate tokens
            $table->unique(['user_id', 'gateway_token'], 'unique_user_gateway_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
