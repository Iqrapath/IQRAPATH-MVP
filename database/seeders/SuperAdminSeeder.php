<?php

namespace Database\Seeders;

use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a super-admin user
        $user = User::create([
            'name' => 'Super Admin',
            'email' => 'admin@sch.com',
            'password' => Hash::make('123password'),
            'role' => 'super-admin',
            'email_verified_at' => now(),
        ]);

        // Create admin profile
        AdminProfile::create([
            'user_id' => $user->id,
            'department' => 'Administration',
            'admin_level' => 'Super Admin',
            'permissions' => json_encode([
                'users' => ['create', 'read', 'update', 'delete'],
                'roles' => ['create', 'read', 'update', 'delete'],
                'settings' => ['read', 'update'],
            ]),
            'bio' => 'System super administrator',
        ]);
    }
}
