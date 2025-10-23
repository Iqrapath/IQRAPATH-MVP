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
        Schema::create('notification_triggers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('event');
            $table->string('template_name')->nullable();
            $table->string('title')->nullable();
            $table->text('body')->nullable();
            $table->string('audience_type')->default('all'); // all, role, individual
            $table->json('audience_filter')->nullable();
            
            $table->json('channels')->nullable();

            $table->string('timing_type')->default('immediate'); // immediate, before, after
            $table->integer('timing_value')->nullable();
            $table->string('timing_unit')->nullable(); // minutes, hours, days
            $table->string('level')->default('info'); // info, success, warning, error
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            // Indexes for faster queries
            $table->index('event');
            $table->index('is_enabled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_triggers');
    }
};
