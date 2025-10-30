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
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique()->nullable(); // Prevents duplicate transactions
            $table->foreignId('wallet_id')->constrained('student_wallets')->onDelete('cascade');
            $table->enum('transaction_type', ['credit', 'debit']);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN'); // Multi-currency support
            $table->decimal('original_amount', 10, 2)->nullable(); // For currency conversions
            $table->string('original_currency', 3)->nullable(); // Original currency
            $table->decimal('exchange_rate', 10, 4)->nullable(); // Conversion rate
            $table->decimal('balance_before', 10, 2)->nullable(); // Snapshot before transaction
            $table->decimal('balance_after', 10, 2)->nullable(); // Snapshot after transaction
            $table->text('description');
            $table->string('reference_type')->nullable(); // Type of related entity (subscription, booking, etc.)
            $table->unsignedBigInteger('reference_id')->nullable(); // ID of related entity
            $table->enum('status', ['pending', 'completed', 'failed'])->default('completed');
            $table->timestamp('transaction_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            // Composite indexes for better query performance
            $table->index(['wallet_id', 'created_at'], 'idx_wallet_created');
            $table->index(['transaction_type', 'status'], 'idx_type_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
}; 