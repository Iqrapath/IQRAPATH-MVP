<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\StudentProfile;
use App\Models\GuardianProfile;
use App\Services\GuardianService;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // This seeder now only handles relationships between users
        // Individual role seeders handle user creation
        
        // Assign guardians to students after all users are created
        $this->assignGuardiansToStudents();
    }
    
    /**
     * Assign guardians to students.
     */
    private function assignGuardiansToStudents(): void
    {
        $guardianService = new GuardianService();
        
        $guardians = User::where('role', 'guardian')->get();
        $students = User::where('role', 'student')->get();
        
        if ($guardians->count() > 0 && $students->count() > 0) {
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
            
            // Update all guardian children counts using the service
            $guardianService->updateAllGuardiansChildrenCount();
        }
    }
} 