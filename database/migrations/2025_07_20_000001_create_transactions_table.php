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
            $table->foreignId('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('session_id')->nullable()->constrained('teaching_sessions')->nullOnDelete();
            $table->enum('transaction_type', [
                'session_payment', 
                'referral_bonus', 
                'withdrawal',
                'system_adjustment',
                'refund'
            ]);
            $table->string('description');
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['completed', 'in_progress', 'canceled', 'failed'])->default('in_progress');
            $table->date('transaction_date');
            $table->foreignId('created_by_id')->nullable()->constrained('users')->nullOnDelete();
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