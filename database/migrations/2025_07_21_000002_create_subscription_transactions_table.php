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
        Schema::create('subscription_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid')->unique();
            $table->foreignId('subscription_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('currency', ['USD', 'NGN']);
            $table->enum('type', ['new_subscription', 'renewal', 'refund', 'cancellation']);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->json('payment_details')->nullable();
            $table->timestamps();
            
            // Add indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['subscription_id', 'type']);
            $table->index('transaction_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_transactions');
    }
}; 