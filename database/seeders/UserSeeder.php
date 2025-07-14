<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\AdminProfile;
use App\Models\TeacherProfile;
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
        
        // Create teacher users
        $this->createTeacherUsers();
        
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
     * Create teacher users.
     */
    private function createTeacherUsers(): void
    {
        // Create main teacher if it doesn't exist
        $teacher = User::firstOrCreate(
            ['email' => 'teacher@sch.com'],
            [
                'name' => 'Teacher',
                'email' => 'teacher@sch.com',
                'role' => 'teacher',
                'password' => bcrypt('123password'),
                'email_verified_at' => now(),
            ]
        );
        
        // Create teacher profile if it doesn't exist
        if (!$teacher->teacherProfile) {
            TeacherProfile::create([
                'user_id' => $teacher->id,
                'bio' => 'Experienced Quran teacher with focus on Tajweed.',
                'experience_years' => '5',
                'verified' => true,
                'languages' => json_encode(['English', 'Arabic']),
                'teaching_type' => 'Online',
                'teaching_mode' => 'One-to-One',
            ]);
        }
        
        // Create additional teacher users
        User::factory(3)->create([
            'role' => 'teacher',
        ])->each(function ($user) {
            TeacherProfile::create([
                'user_id' => $user->id,
                'bio' => fake()->paragraph(),
                'experience_years' => (string)fake()->numberBetween(1, 15),
                'verified' => fake()->boolean(70),
                'languages' => json_encode(fake()->randomElements(['English', 'Arabic', 'Urdu', 'French', 'Spanish'], fake()->numberBetween(1, 3))),
                'teaching_type' => fake()->randomElement(['Online', 'In-person', 'Both']),
                'teaching_mode' => fake()->randomElement(['One-to-One', 'Group', 'Both']),
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