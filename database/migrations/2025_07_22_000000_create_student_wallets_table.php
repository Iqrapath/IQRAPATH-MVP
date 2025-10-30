<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('student_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('payment_id', 191)->unique();
            $table->decimal('balance', 10, 2)->default(0);
            $table->string('currency', 3)->default('NGN'); // Multi-currency support
            $table->decimal('locked_balance', 10, 2)->default(0); // For pending transactions
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->decimal('total_refunded', 10, 2)->default(0);
            $table->foreignId('default_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->boolean('auto_renew_enabled')->default(false);
            $table->timestamps();
            
            // Index for low balance queries
            $table->index('balance', 'idx_student_wallet_balance');
        });
        
        // Add check constraints (MySQL 8.0.16+)
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE student_wallets ADD CONSTRAINT chk_student_balance CHECK (balance >= 0)');
            DB::statement('ALTER TABLE student_wallets ADD CONSTRAINT chk_student_locked_balance CHECK (locked_balance >= 0)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_wallets');
    }
}; 