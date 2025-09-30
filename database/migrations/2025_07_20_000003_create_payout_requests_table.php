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
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_uuid')->unique();
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['bank_transfer', 'paypal', 'mobile_money', 'other']);
            $table->json('payment_details');
            $table->enum('status', ['pending', 'processing', 'approved', 'declined', 'paid'])->default('pending');
            $table->date('request_date');
            $table->date('processed_date')->nullable();
            $table->foreignId('processed_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            
            // Multi-currency support
            $table->string('currency', 3)->default('NGN');
            $table->decimal('exchange_rate_used', 10, 6)->nullable();
            $table->decimal('fee_amount', 10, 2)->nullable();
            $table->string('fee_currency', 3)->default('NGN');
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
}; 