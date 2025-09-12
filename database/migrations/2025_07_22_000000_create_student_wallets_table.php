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
        Schema::create('student_wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->text('payment_id')->unique();
            $table->decimal('balance', 10, 2)->default(0);
            $table->decimal('total_spent', 10, 2)->default(0);
            $table->decimal('total_refunded', 10, 2)->default(0);
            $table->foreignId('default_payment_method_id')->nullable()->constrained('payment_methods')->onDelete('set null');
            $table->boolean('auto_renew_enabled')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_wallets');
    }
}; 