<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This table tracks daily payment reconciliation to ensure
     * our records match with payment gateway records.
     */
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->date('reconciliation_date');
            $table->string('gateway'); // stripe, paystack
            
            // Summary counts
            $table->integer('total_transactions')->default(0);
            $table->integer('matched_transactions')->default(0);
            $table->integer('unmatched_local')->default(0); // In our DB but not in gateway
            $table->integer('unmatched_gateway')->default(0); // In gateway but not in our DB
            
            // Summary amounts
            $table->decimal('total_amount_local', 12, 2)->default(0);
            $table->decimal('total_amount_gateway', 12, 2)->default(0);
            $table->decimal('difference', 12, 2)->default(0);
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
            $table->text('notes')->nullable();
            
            // Discrepancies
            $table->json('discrepancies')->nullable(); // Array of mismatched transactions
            
            // Reconciliation metadata
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->unique(['reconciliation_date', 'gateway']);
            $table->index('status');
            $table->index('completed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};
