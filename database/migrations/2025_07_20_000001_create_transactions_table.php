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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_uuid')->unique();
            $table->foreignId('teacher_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('teaching_sessions')->nullOnDelete();
            $table->enum('transaction_type', [
                'session_payment', 
                'referral_bonus', 
                'withdrawal',
                'system_adjustment',
                'refund',
                'wallet_funding'
            ]);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['completed', 'in_progress', 'canceled', 'failed'])->default('in_progress');
            $table->date('transaction_date');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('gateway')->nullable();
            $table->string('gateway_reference')->nullable();
            $table->json('metadata')->nullable();
            
            // Multi-currency support
            $table->string('currency', 3)->default('NGN');
            $table->decimal('exchange_rate_used', 10, 6)->nullable();
            $table->timestamp('exchange_rate_date')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
}; 