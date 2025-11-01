<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Teaching Sessions ===\n\n";

// Check scheduled sessions
$scheduledSessions = DB::table('teaching_sessions')
    ->where('status', 'scheduled')
    ->where('session_date', '>=', now()->toDateString())
    ->get();

echo "Total scheduled future sessions: " . $scheduledSessions->count() . "\n\n";

if ($scheduledSessions->count() > 0) {
    echo "Sample sessions:\n";
    foreach ($scheduledSessions->take(5) as $session) {
        echo "- Session ID: {$session->id}, Teacher: {$session->teacher_id}, Student: {$session->student_id}, Date: {$session->session_date}, Status: {$session->status}\n";
    }
} else {
    echo "No scheduled future sessions found.\n";
}

echo "\n=== Checking All Future Sessions (any status) ===\n\n";

$allFutureSessions = DB::table('teaching_sessions')
    ->where('session_date', '>=', now()->toDateString())
    ->get();

echo "Total future sessions (any status): " . $allFutureSessions->count() . "\n\n";

if ($allFutureSessions->count() > 0) {
    $statusCounts = $allFutureSessions->groupBy('status')->map(fn($group) => $group->count());
    echo "Breakdown by status:\n";
    foreach ($statusCounts as $status => $count) {
        echo "- {$status}: {$count}\n";
    }
}

echo "\n=== Checking Teacher Hourly Rates ===\n\n";

$teachers = DB::table('users')
    ->where('role', 'teacher')
    ->join('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
    ->select('users.id', 'users.name', 'teacher_profiles.hourly_rate_usd', 'teacher_profiles.hourly_rate_ngn')
    ->get();

echo "Total teachers: " . $teachers->count() . "\n\n";

$teachersWithRates = $teachers->filter(fn($t) => $t->hourly_rate_usd > 0 || $t->hourly_rate_ngn > 0);
$teachersWithoutRates = $teachers->filter(fn($t) => ($t->hourly_rate_usd ?? 0) == 0 && ($t->hourly_rate_ngn ?? 0) == 0);

echo "Teachers with rates set: " . $teachersWithRates->count() . "\n";
echo "Teachers without rates: " . $teachersWithoutRates->count() . "\n\n";

if ($teachersWithRates->count() > 0) {
    echo "Sample teachers with rates:\n";
    foreach ($teachersWithRates->take(5) as $teacher) {
        echo "- {$teacher->name} (ID: {$teacher->id}): USD \${$teacher->hourly_rate_usd}, NGN ₦{$teacher->hourly_rate_ngn}\n";
    }
}

if ($teachersWithoutRates->count() > 0) {
    echo "\nTeachers without rates:\n";
    foreach ($teachersWithoutRates->take(5) as $teacher) {
        echo "- {$teacher->name} (ID: {$teacher->id})\n";
    }
}

echo "\n=== Cross-Check: Teachers with sessions but no rates ===\n\n";

$teachersWithSessions = DB::table('teaching_sessions')
    ->where('session_date', '>=', now()->toDateString())
    ->distinct()
    ->pluck('teacher_id');

$teachersWithSessionsButNoRates = DB::table('users')
    ->whereIn('users.id', $teachersWithSessions)
    ->join('teacher_profiles', 'users.id', '=', 'teacher_profiles.user_id')
    ->where(function($query) {
        $query->whereNull('teacher_profiles.hourly_rate_usd')
              ->orWhere('teacher_profiles.hourly_rate_usd', 0);
    })
    ->where(function($query) {
        $query->whereNull('teacher_profiles.hourly_rate_ngn')
              ->orWhere('teacher_profiles.hourly_rate_ngn', 0);
    })
    ->select('users.id', 'users.name')
    ->get();

if ($teachersWithSessionsButNoRates->count() > 0) {
    echo "⚠️ Found " . $teachersWithSessionsButNoRates->count() . " teachers with future sessions but NO rates set:\n";
    foreach ($teachersWithSessionsButNoRates as $teacher) {
        echo "- {$teacher->name} (ID: {$teacher->id})\n";
    }
    echo "\nThis is why 'Upcoming Earning Due' is empty!\n";
} else {
    echo "✓ All teachers with future sessions have rates set.\n";
}

echo "\n=== Done ===\n";
