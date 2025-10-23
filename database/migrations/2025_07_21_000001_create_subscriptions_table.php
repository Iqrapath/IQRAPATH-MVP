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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('subscription_uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('subscription_plan_id')->constrained()->onDelete('cascade');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('amount_paid', 10, 2);
            $table->enum('currency', ['USD', 'NGN']);
            $table->enum('status', ['active', 'expired', 'cancelled', 'pending'])->default('pending');
            $table->date('next_billing_date')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->timestamps();
            
            // Add indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['status', 'end_date']);
            $table->index('subscription_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
}; 