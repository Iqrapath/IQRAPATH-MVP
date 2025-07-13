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
        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->text('description')->nullable();
            $table->json('permissions');
            $table->timestamps();
        });

        // Insert default admin roles
        $this->seedDefaultAdminRoles();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_roles');
    }

    /**
     * Seed default admin roles.
     */
    private function seedDefaultAdminRoles(): void
    {
        $roles = [
            [
                'name' => 'Super Admin',
                'description' => 'Full access to all features',
                'permissions' => json_encode([
                    'full_access' => true,
                    'manage_bookings' => true,
                    'handle_payouts' => true,
                    'change_platform_settings' => true,
                ]),
            ],
            [
                'name' => 'Support Staff',
                'description' => 'Access to support and user management',
                'permissions' => json_encode([
                    'full_access' => false,
                    'manage_bookings' => true,
                    'handle_payouts' => false,
                    'change_platform_settings' => false,
                ]),
            ],
            [
                'name' => 'Finance Admin',
                'description' => 'Access to financial operations',
                'permissions' => json_encode([
                    'full_access' => false,
                    'manage_bookings' => false,
                    'handle_payouts' => true,
                    'change_platform_settings' => false,
                ]),
            ],
        ];

        $now = now();

        foreach ($roles as $role) {
            DB::table('admin_roles')->insert([
                'name' => $role['name'],
                'description' => $role['description'],
                'permissions' => $role['permissions'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
