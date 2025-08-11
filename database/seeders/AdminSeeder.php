<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AdminProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main admin if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@sch.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@sch.com',
                'role' => 'super-admin',
                'password' => Hash::make('123password'),
                'email_verified_at' => now(),
                'phone' => '+2348012345678',
                'location' => 'Lagos, Nigeria',
                'avatar' => null, // Will use initials system
            ]
        );
        
        // Create admin profile if it doesn't exist
        if (!$admin->adminProfile) {
            AdminProfile::create([
                'user_id' => $admin->id,
                'department' => 'IT',
                'admin_level' => 'System Administrator',
                'permissions' => json_encode([
                    'users' => ['create', 'read', 'update', 'delete'],
                    'roles' => ['create', 'read', 'update', 'delete'],
                    'settings' => ['read', 'update'],
                    'teachers' => ['create', 'read', 'update', 'delete'],
                    'students' => ['create', 'read', 'update', 'delete'],
                    'guardians' => ['create', 'read', 'update', 'delete'],
                    'payments' => ['create', 'read', 'update', 'delete'],
                    'reports' => ['create', 'read', 'update', 'delete'],
                ]),
                'bio' => 'Main system administrator for IQRAPATH platform with full access to all system features.',
            ]);
        }
        
        // Create additional admin users using factory
        User::factory(12)->admin()->create()->each(function ($user) {
            $departments = ['Support', 'Content', 'Finance', 'Operations', 'Marketing', 'HR', 'Quality Assurance', 'Technical'];
            $adminLevels = [
                'Content Manager', 'Support Manager', 'Financial Manager', 'Operations Manager',
                'Marketing Manager', 'HR Manager', 'Quality Manager', 'Technical Manager'
            ];
            
            AdminProfile::create([
                'user_id' => $user->id,
                'department' => fake()->randomElement($departments),
                'admin_level' => fake()->randomElement($adminLevels),
                'permissions' => json_encode([
                    'users' => ['read', 'update'],
                    'roles' => ['read'],
                    'settings' => ['read'],
                    'teachers' => ['read', 'update'],
                    'students' => ['read', 'update'],
                    'guardians' => ['read', 'update'],
                    'payments' => ['read'],
                    'reports' => ['read'],
                ]),
                'bio' => fake()->paragraph(),
            ]);
        });
    }
}
