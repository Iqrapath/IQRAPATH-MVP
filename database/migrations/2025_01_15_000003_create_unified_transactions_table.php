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
        Schema::create('unified_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid')->unique();
            
            // Polymorphic relationship to wallet
            $table->string('wallet_type'); // TeacherWallet, GuardianWallet, StudentWallet
            $table->unsignedBigInteger('wallet_id');
            
            // Transaction details
            $table->enum('transaction_type', [
                'credit',
                'debit', 
                'session_payment',
                'withdrawal',
                'family_transfer',
                'subscription_payment',
                'refund',
                'bonus',
                'fee',
                'adjustment'
            ]);
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('NGN');
            $table->string('description');
            $table->enum('status', ['pending', 'completed', 'failed', 'cancelled'])->default('completed');
            
            // Related entities (optional)
            $table->foreignId('session_id')->nullable()->constrained('teaching_sessions')->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();
            $table->foreignId('payout_request_id')->nullable()->constrained('payout_requests')->nullOnDelete();
            
            // Transfer tracking (for family transfers)
            $table->string('from_wallet_type')->nullable();
            $table->unsignedBigInteger('from_wallet_id')->nullable();
            $table->string('to_wallet_type')->nullable();
            $table->unsignedBigInteger('to_wallet_id')->nullable();
            
            // Metadata and tracking
            $table->json('metadata')->nullable();
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('transaction_date');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['wallet_type', 'wallet_id']);
            $table->index(['transaction_type', 'status']);
            $table->index(['from_wallet_type', 'from_wallet_id']);
            $table->index(['to_wallet_type', 'to_wallet_id']);
            $table->index('transaction_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unified_transactions');
    }
};
