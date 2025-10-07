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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('avatar')->nullable();
            $table->string('location')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->enum('role', ['super-admin', 'admin', 'teacher', 'student', 'guardian', 'unassigned'])->nullable();
            $table->json('additional_roles')->nullable()->comment('Additional roles user can have (e.g., guardian who is also a student)');
            $table->enum('account_status', ['active', 'inactive', 'suspended', 'pending'])->default('active');
            $table->string('suspension_reason')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->foreignId('suspended_by')->nullable()->constrained('users');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('provider')->nullable();
            $table->string('provider_id')->nullable();
            $table->enum('status_type', ['online', 'away', 'busy', 'offline'])->default('offline');
            $table->string('status_message')->nullable();
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('registration_date')->useCurrent();
            $table->rememberToken();
            $table->softDeletes(); // Add soft delete support
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
