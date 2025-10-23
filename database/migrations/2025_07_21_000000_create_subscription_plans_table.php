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
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price_naira', 10, 2);
            $table->decimal('price_dollar', 10, 2);
            $table->enum('billing_cycle', ['monthly', 'annual']);
            $table->integer('duration_months');
            $table->json('features')->nullable();
            $table->json('tags')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Add indexes for performance
            $table->index(['is_active', 'billing_cycle']);
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
}; 