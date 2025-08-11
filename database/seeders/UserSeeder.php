<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AdminProfile;
use App\Models\StudentProfile;
use App\Models\GuardianProfile;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin users
        $this->createAdminUsers();
        
        // Create student users
        $this->createStudentUsers();
        
        // Create guardian users
        $this->createGuardianUsers();
    }
    
    /**
     * Create admin users.
     */
    private function createAdminUsers(): void
    {
        // Create main admin if it doesn't exist
        $admin = User::firstOrCreate(
            ['email' => 'admin@sch.com'],
            [
                'name' => 'Admin User',
                'email' => 'admin@sch.com',
                'role' => 'super-admin',
                'password' => bcrypt('123password'),
                'email_verified_at' => now(),
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
                ]),
                'bio' => 'Main system administrator for IQRAPATH platform.',
            ]);
        }
        
        // Create additional admin users
        User::factory(2)->create([
            'role' => 'super-admin',
        ])->each(function ($user) {
            AdminProfile::create([
                'user_id' => $user->id,
                'department' => fake()->randomElement(['Support', 'Content', 'Finance']),
                'admin_level' => fake()->randomElement(['Content Manager', 'Support Manager', 'Financial Manager']),
                'permissions' => json_encode([
                    'users' => ['read'],
                    'roles' => ['read'],
                    'settings' => ['read'],
                ]),
                'bio' => fake()->paragraph(),
            ]);
        });
    }
    
    /**
     * Create student users.
     */
    private function createStudentUsers(): void
    {
        // Create main student if it doesn't exist
        $student = User::firstOrCreate(
            ['email' => 'student@sch.com'],
            [
                'name' => 'Student',
                'email' => 'student@sch.com',
                'role' => 'student',
                'password' => bcrypt('123password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Create student profile if it doesn't exist
        if (!$student->studentProfile) {
            StudentProfile::create([
                'user_id' => $student->id,
                'grade_level' => 'Intermediate',
                'learning_goals' => 'Complete Quran recitation with proper Tajweed',
                'guardian_id' => null, // Will be updated later if needed
            ]);
        }
        
        // Create additional student users
        User::factory(5)->create([
            'role' => 'student',
        ])->each(function ($user) {
            StudentProfile::create([
                'user_id' => $user->id,
                'grade_level' => fake()->randomElement(['Beginner', 'Intermediate', 'Advanced']),
                'learning_goals' => fake()->sentence(),
                'guardian_id' => null, // Will be updated later
            ]);
        });
    }
    
    /**
     * Create guardian users.
     */
    private function createGuardianUsers(): void
    {
        // Create main guardian if it doesn't exist
        $guardian = User::firstOrCreate(
            ['email' => 'guardian@sch.com'],
            [
                'name' => 'Guardian',
                'email' => 'guardian@sch.com',
                'role' => 'guardian',
                'password' => bcrypt('123password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Create guardian profile if it doesn't exist
        if (!$guardian->guardianProfile) {
            GuardianProfile::create([
                'user_id' => $guardian->id,
                'relationship' => 'Parent',
            ]);
        }
        
        // Create additional guardian users
        User::factory(3)->create([
            'role' => 'guardian',
        ])->each(function ($user) {
            GuardianProfile::create([
                'user_id' => $user->id,
                'relationship' => fake()->randomElement(['Parent', 'Grandparent', 'Uncle', 'Aunt', 'Guardian']),
            ]);
        });
        
        // Assign guardians to students
        $guardians = User::where('role', 'guardian')->get();
        $students = User::where('role', 'student')->get();
        
        foreach ($students as $index => $student) {
            $guardianIndex = $index % $guardians->count();
            $guardian = $guardians[$guardianIndex];
            
            // Update student profile with guardian
            if ($student->studentProfile) {
                $student->studentProfile->update([
                    'guardian_id' => $guardian->id,
                ]);
            }
        }
    }
} 