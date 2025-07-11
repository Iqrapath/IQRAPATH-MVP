<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('teaching_sessions', function (Blueprint $table) {
            // Zoom meeting details
            $table->string('zoom_meeting_id')->nullable()->after('meeting_password');
            $table->string('zoom_host_id')->nullable()->after('zoom_meeting_id');
            $table->string('zoom_join_url')->nullable()->after('zoom_host_id');
            $table->string('zoom_start_url')->nullable()->after('zoom_join_url');
            $table->string('zoom_password')->nullable()->after('zoom_start_url');
            
            // Attendance tracking
            $table->boolean('teacher_marked_present')->default(false)->after('zoom_password');
            $table->boolean('student_marked_present')->default(false)->after('teacher_marked_present');
            $table->json('attendance_data')->nullable()->after('student_marked_present');
            
            // If meeting_link is already populated, we don't want to remove that data
            // so we'll keep it and use zoom_join_url for new records
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teaching_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'zoom_meeting_id',
                'zoom_host_id',
                'zoom_join_url',
                'zoom_start_url',
                'zoom_password',
                'teacher_marked_present',
                'student_marked_present',
                'attendance_data',
            ]);
        });
    }
}; 