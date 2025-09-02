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
        Schema::create('teacher_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_earned', 10, 2)->default(0);
            $table->decimal('total_withdrawn', 10, 2)->default(0);
            $table->decimal('pending_payouts', 10, 2)->default(0);
            $table->foreignId('default_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->boolean('auto_withdrawal_enabled')->default(false);
            $table->decimal('auto_withdrawal_threshold', 10, 2)->nullable();
            $table->json('withdrawal_settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();
            
            // Ensure one wallet per teacher
            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_wallets');
    }
};
