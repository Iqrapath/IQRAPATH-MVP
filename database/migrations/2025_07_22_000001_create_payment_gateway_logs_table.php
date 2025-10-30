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
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique()->nullable(); // Prevents duplicate payments
            $table->string('gateway')->index(); // paystack, flutterwave, etc.
            $table->string('reference')->unique();
            $table->string('transaction_reference')->nullable()->index(); // Links to internal transactions
            $table->string('transaction_id')->nullable(); // Gateway transaction ID
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_transaction_id')->nullable()->constrained('subscription_transactions')->nullOnDelete();
            $table->enum('status', ['pending', 'success', 'failed', 'abandoned'])->default('pending');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 10);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->json('webhook_data')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
}; 