<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\GuardianProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main guardian if it doesn't exist
        $guardian = User::firstOrCreate(
            ['email' => 'guardian@sch.com'],
            [
                'name' => 'Guardian Ahmad',
                'email' => 'guardian@sch.com',
                'role' => 'guardian',
                'password' => Hash::make('123password'),
                'email_verified_at' => now(),
                'phone' => '+2348012345678',
                'location' => 'Lagos, Nigeria',
                'avatar' => null, // Will use initials system
            ]
        );
        
        // Create guardian profile if it doesn't exist
        if (!$guardian->guardianProfile) {
            GuardianProfile::create([
                'user_id' => $guardian->id,
                'status' => 'active',
                'registration_date' => now(),
                'children_count' => 0, // Will be updated later
                'relationship' => 'Parent',
            ]);
        }
        
        // Create additional guardian users using factory
        User::factory(20)->guardian()->create()->each(function ($user) {
            $relationships = ['Parent', 'Grandparent', 'Uncle', 'Aunt', 'Guardian', 'Sibling'];
            $statuses = ['active', 'active', 'active', 'inactive']; // 75% active, 25% inactive
            
            GuardianProfile::create([
                'user_id' => $user->id,
                'status' => fake()->randomElement($statuses),
                'registration_date' => fake()->dateTimeBetween('-2 years', 'now'),
                'children_count' => 0, // Will be updated later
                'relationship' => fake()->randomElement($relationships),
            ]);
        });
    }
}
