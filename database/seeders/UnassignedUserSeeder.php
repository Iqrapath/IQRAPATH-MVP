<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UnassignedUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main unassigned user if it doesn't exist
        $unassignedUser = User::firstOrCreate(
            ['email' => 'unassigned@sch.com'],
            [
                'name' => 'Unassigned User',
                'email' => 'unassigned@sch.com',
                'role' => null, // No role assigned
                'password' => Hash::make('123password'),
                'email_verified_at' => now(),
                'phone' => '+2348012345678',
                'location' => 'Lagos, Nigeria',
                'avatar' => null, // Will use initials system
            ]
        );
        
        // Create additional unassigned users using factory
        User::factory(15)->unassigned()->create()->each(function ($user) {
            // These users have no role and no profile
            // They will be redirected to the unassigned page
        });
    }
}
