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
        Schema::create('guardian_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_spent_on_children', 10, 2)->default(0);
            $table->decimal('total_refunded', 10, 2)->default(0);
            $table->json('payment_methods')->nullable();
            $table->string('default_payment_method')->nullable();
            $table->boolean('auto_fund_children')->default(false);
            $table->decimal('auto_fund_threshold', 10, 2)->nullable();
            $table->json('family_spending_limits')->nullable();
            $table->json('child_allowances')->nullable(); // Individual child spending limits
            $table->timestamps();
            
            // Ensure one wallet per guardian
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guardian_wallets');
    }
};
