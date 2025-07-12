<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update registration_date for guardian profiles
        DB::table('guardian_profiles')
            ->whereNull('registration_date')
            ->update([
                'registration_date' => DB::raw('created_at')
            ]);
            
        // Update registration_date for student profiles
        DB::table('student_profiles')
            ->whereNull('registration_date')
            ->update([
                'registration_date' => DB::raw('created_at')
            ]);
            
        // Update children_count for guardian profiles
        $guardians = DB::table('guardian_profiles')->get();
        foreach ($guardians as $guardian) {
            $childrenCount = DB::table('student_profiles')
                ->where('guardian_id', $guardian->user_id)
                ->count();
                
            DB::table('guardian_profiles')
                ->where('id', $guardian->id)
                ->update(['children_count' => $childrenCount]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No need to reverse this migration as it just populates data
    }
}; 