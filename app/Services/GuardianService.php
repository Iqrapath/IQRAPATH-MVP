<?php

namespace App\Services;

use App\Models\User;
use App\Models\GuardianProfile;
use App\Models\StudentProfile;
use Illuminate\Support\Facades\DB;

class GuardianService
{
    /**
     * Update children count for a specific guardian
     */
    public function updateChildrenCount(int $guardianId): void
    {
        $childrenCount = StudentProfile::where('guardian_id', $guardianId)->count();
        
        GuardianProfile::where('user_id', $guardianId)->update([
            'children_count' => $childrenCount
        ]);
    }
    
    /**
     * Update children count for all guardians
     */
    public function updateAllGuardiansChildrenCount(): void
    {
        $guardians = GuardianProfile::all();
        
        foreach ($guardians as $guardian) {
            $this->updateChildrenCount($guardian->user_id);
        }
    }
    
    /**
     * Assign a guardian to a student and update counts
     */
    public function assignGuardianToStudent(int $studentId, int $guardianId): void
    {
        // Update student profile
        StudentProfile::where('user_id', $studentId)->update([
            'guardian_id' => $guardianId
        ]);
        
        // Update guardian children count
        $this->updateChildrenCount($guardianId);
    }
    
    /**
     * Remove guardian from a student and update counts
     */
    public function removeGuardianFromStudent(int $studentId): void
    {
        $student = StudentProfile::where('user_id', $studentId)->first();
        
        if ($student && $student->guardian_id) {
            $oldGuardianId = $student->guardian_id;
            
            // Remove guardian
            $student->update(['guardian_id' => null]);
            
            // Update old guardian's children count
            $this->updateChildrenCount($oldGuardianId);
        }
    }
    
    /**
     * Create a new guardian profile with proper initialization
     */
    public function createGuardianProfile(array $data): GuardianProfile
    {
        $guardianProfile = GuardianProfile::create($data);
        
        // Initialize children count
        $this->updateChildrenCount($guardianProfile->user_id);
        
        return $guardianProfile;
    }
    
    /**
     * Get guardian statistics
     */
    public function getGuardianStats(): array
    {
        $totalGuardians = GuardianProfile::count();
        $activeGuardians = GuardianProfile::where('status', 'active')->count();
        $guardiansWithChildren = GuardianProfile::where('children_count', '>', 0)->count();
        
        return [
            'total' => $totalGuardians,
            'active' => $activeGuardians,
            'with_children' => $guardiansWithChildren,
            'average_children' => $totalGuardians > 0 ? GuardianProfile::avg('children_count') : 0
        ];
    }
}
