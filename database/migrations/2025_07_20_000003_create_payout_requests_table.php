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
            $table->enum('payment_method', ['bank_transfer', 'paypal', 'stripe', 'mobile_money', 'other']);
            $table->json('payment_details');
            $table->enum('status', [
                'pending', 
                'processing', 
                'approved', 
                'approved_pending_transfer',
                'declined', 
                'rejected',
                'paid',
                'completed',
                'failed',
                'cancelled',
                'returned',
                'unclaimed',
                'requires_manual_processing'
            ])->default('pending');
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
            
            // Payment gateway integration fields
            $table->string('external_reference')->nullable()->comment('Reference ID from payment gateway');
            $table->string('external_transfer_code')->nullable()->comment('Transfer code from payment gateway');
            $table->timestamp('processed_at')->nullable()->comment('When admin approved/rejected');
            $table->timestamp('completed_at')->nullable()->comment('When payment gateway completed transfer');
            $table->timestamp('failed_at')->nullable()->comment('When payment gateway failed');
            $table->timestamp('cancelled_at')->nullable()->comment('When payment was cancelled');
            $table->timestamp('returned_at')->nullable()->comment('When payment was returned');
            $table->text('failure_reason')->nullable()->comment('Reason for failure from payment gateway');
            
            $table->timestamps();
            
            // Indexes for better query performance
            $table->index('status');
            $table->index('teacher_id');
            $table->index('external_reference');
            $table->index('external_transfer_code');
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