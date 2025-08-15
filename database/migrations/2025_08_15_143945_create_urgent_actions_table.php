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
        Schema::create('urgent_actions', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // withdrawal_requests, teacher_applications, etc.
            $table->string('title'); // "Withdrawal Requests Pending Approval"
            $table->string('action_text'); // "View Requests"
            $table->string('action_url'); // "/admin/withdrawals"
            $table->integer('count')->default(0);
            $table->integer('cached_count')->default(0);
            $table->timestamp('last_updated')->nullable();
            $table->integer('priority_level')->default(1); // 1=low, 2=medium, 3=high, 4=critical
            $table->json('business_rules')->nullable(); // {"min_amount": 100, "max_days": 3, "user_types": ["premium"]}
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_admin_override')->default(false);
            $table->timestamp('admin_override_at')->nullable();
            $table->unsignedBigInteger('admin_override_by')->nullable();
            $table->json('permissions')->nullable(); // {"roles": ["super-admin", "admin"], "permissions": ["view_withdrawals"]}
            $table->timestamps();
            
            $table->index(['type', 'is_active']);
            $table->index(['priority_level', 'is_active']);
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('urgent_actions');
    }
};
