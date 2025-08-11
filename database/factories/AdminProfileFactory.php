<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\AdminProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminProfile>
 */
class AdminProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $departments = [
            'IT', 'Support', 'Content', 'Finance', 'Operations', 
            'Marketing', 'HR', 'Quality Assurance', 'Technical', 'Customer Service'
        ];
        
        $adminLevels = [
            'System Administrator', 'Content Manager', 'Support Manager', 'Financial Manager',
            'Operations Manager', 'Marketing Manager', 'HR Manager', 'Quality Manager',
            'Technical Manager', 'Customer Service Manager', 'Senior Administrator'
        ];

        $permissions = [
            'users' => ['read', 'update'],
            'roles' => ['read'],
            'settings' => ['read'],
            'teachers' => ['read', 'update'],
            'students' => ['read', 'update'],
            'guardians' => ['read', 'update'],
            'payments' => ['read'],
            'reports' => ['read'],
        ];

        return [
            'department' => fake()->randomElement($departments),
            'admin_level' => fake()->randomElement($adminLevels),
            'permissions' => json_encode($permissions),
            'bio' => fake()->paragraph(),
        ];
    }

    /**
     * Indicate that the admin profile should be created with a user.
     */
    public function withUser(): static
    {
        return $this->afterCreating(function (AdminProfile $adminProfile) {
            if (!$adminProfile->user_id) {
                $user = User::factory()->admin()->create();
                $adminProfile->update(['user_id' => $user->id]);
            }
        });
    }

    /**
     * Indicate that the admin has full permissions.
     */
    public function fullPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
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
            'admin_level' => 'System Administrator',
        ]);
    }

    /**
     * Indicate that the admin has limited permissions.
     */
    public function limitedPermissions(): static
    {
        return $this->state(fn (array $attributes) => [
            'permissions' => json_encode([
                'users' => ['read'],
                'roles' => ['read'],
                'settings' => ['read'],
                'teachers' => ['read'],
                'students' => ['read'],
                'guardians' => ['read'],
                'payments' => ['read'],
                'reports' => ['read'],
            ]),
            'admin_level' => fake()->randomElement(['Support Staff', 'Content Assistant', 'Data Entry']),
        ]);
    }

    /**
     * Indicate that the admin belongs to a specific department.
     */
    public function inDepartment(string $department): static
    {
        return $this->state(fn (array $attributes) => [
            'department' => $department,
        ]);
    }
}
