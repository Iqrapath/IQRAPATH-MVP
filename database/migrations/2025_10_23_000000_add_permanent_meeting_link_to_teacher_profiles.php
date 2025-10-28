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
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // Permanent meeting link that will be reused for all sessions
            $table->string('permanent_meeting_link')->nullable()->after('intro_video_url');
            $table->string('permanent_meeting_platform')->nullable()->after('permanent_meeting_link'); // zoom, google_meet, etc.
            $table->string('permanent_meeting_id')->nullable()->after('permanent_meeting_platform'); // Meeting ID for reference
            $table->string('permanent_meeting_password')->nullable()->after('permanent_meeting_id'); // Meeting password if applicable
            $table->timestamp('permanent_meeting_created_at')->nullable()->after('permanent_meeting_password');
            
            // Add index for faster lookups
            $table->index('permanent_meeting_link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropIndex(['permanent_meeting_link']);
            $table->dropColumn([
                'permanent_meeting_link',
                'permanent_meeting_platform',
                'permanent_meeting_id',
                'permanent_meeting_password',
                'permanent_meeting_created_at'
            ]);
        });
    }
};

