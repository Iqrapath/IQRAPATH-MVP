<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Checking Specific Teacher's Upcoming Earnings ===\n\n";

// Get a teacher with sessions
$teacherId = 23; // From the sessions we saw

$teacher = DB::table('users')->where('id', $teacherId)->first();
echo "Teacher: {$teacher->name} (ID: {$teacher->id})\n";
echo "Email: {$teacher->email}\n\n";

// Get teacher profile with rates
$profile = DB::table('teacher_profiles')->where('user_id', $teacherId)->first();
echo "Hourly Rate USD: \${$profile->hourly_rate_usd}\n";
echo "Hourly Rate NGN: ₦{$profile->hourly_rate_ngn}\n\n";

// Check if rates are > 0
$hourlyRateUSD = $profile->hourly_rate_usd ?? 0;
$hourlyRateNGN = $profile->hourly_rate_ngn ?? 0;

echo "Rate check: USD > 0? " . ($hourlyRateUSD > 0 ? 'YES' : 'NO') . "\n";
echo "Rate check: NGN > 0? " . ($hourlyRateNGN > 0 ? 'YES' : 'NO') . "\n";
echo "Will query run? " . (($hourlyRateUSD > 0 || $hourlyRateNGN > 0) ? 'YES' : 'NO') . "\n\n";

if ($hourlyRateUSD > 0 || $hourlyRateNGN > 0) {
    echo "=== Fetching Upcoming Earnings (as controller does) ===\n\n";
    
    $sessions = DB::table('teaching_sessions')
        ->where('teacher_id', $teacherId)
        ->where('status', 'scheduled')
        ->where('session_date', '>=', now()->toDateString())
        ->get();
    
    echo "Found {$sessions->count()} scheduled future sessions\n\n";
    
    if ($sessions->count() > 0) {
        echo "Sessions:\n";
        foreach ($sessions as $session) {
            echo "- Session ID: {$session->id}\n";
            echo "  Date: {$session->session_date}\n";
            echo "  Time: {$session->start_time} - {$session->end_time}\n";
            echo "  Student ID: {$session->student_id}\n";
            echo "  Subject ID: {$session->subject_id}\n";
            echo "  Status: {$session->status}\n";
            
            // Calculate earnings
            $startTime = \Carbon\Carbon::parse($session->start_time);
            $endTime = \Carbon\Carbon::parse($session->end_time);
            $durationHours = $startTime->diffInHours($endTime);
            
            $amountUSD = $hourlyRateUSD * $durationHours;
            $amountNGN = $hourlyRateNGN * $durationHours;
            
            echo "  Duration: {$durationHours} hours\n";
            echo "  Earning USD: \${$amountUSD}\n";
            echo "  Earning NGN: ₦{$amountNGN}\n\n";
        }
        
        echo "✓ This teacher SHOULD see upcoming earnings!\n";
    } else {
        echo "✗ No sessions found for this teacher\n";
    }
} else {
    echo "✗ Teacher has no rates set - query will be skipped!\n";
}

echo "\n=== Checking Controller Logic ===\n\n";

// Simulate what the controller does
$preferredCurrency = 'NGN';

if ($hourlyRateUSD > 0 || $hourlyRateNGN > 0) {
    $upcomingEarnings = DB::table('teaching_sessions')
        ->where('teacher_id', $teacherId)
        ->where('status', 'scheduled')
        ->where('session_date', '>=', now()->toDateString())
        ->orderBy('session_date', 'asc')
        ->limit(5)
        ->get()
        ->map(function ($session) use ($hourlyRateUSD, $hourlyRateNGN, $preferredCurrency) {
            $startTime = \Carbon\Carbon::parse($session->start_time);
            $endTime = \Carbon\Carbon::parse($session->end_time);
            $durationHours = $startTime->diffInHours($endTime);
            
            $amountUSD = $hourlyRateUSD * $durationHours;
            $amountNGN = $hourlyRateNGN * $durationHours;
            
            $primaryAmount = $preferredCurrency === 'USD' ? $amountUSD : $amountNGN;
            $secondaryAmount = $preferredCurrency === 'USD' ? $amountNGN : $amountUSD;
            $secondaryCurrency = $preferredCurrency === 'USD' ? 'NGN' : 'USD';
            
            // Get student name
            $student = DB::table('users')->where('id', $session->student_id)->first();
            
            // Get subject name
            $subject = DB::table('subjects')->where('id', $session->subject_id)->first();
            
            return [
                'id' => $session->id,
                'amount' => round($primaryAmount, 2),
                'amountSecondary' => round($secondaryAmount, 2),
                'currency' => $preferredCurrency,
                'secondaryCurrency' => $secondaryCurrency,
                'studentName' => $student->name ?? 'Unknown',
                'subject' => $subject->name ?? 'General Class',
                'dueDate' => \Carbon\Carbon::parse($session->session_date)->format('jS F Y'),
                'status' => 'pending'
            ];
        });
    
    echo "Upcoming Earnings Array Count: " . $upcomingEarnings->count() . "\n\n";
    
    if ($upcomingEarnings->count() > 0) {
        echo "Sample upcoming earning:\n";
        $first = $upcomingEarnings->first();
        echo json_encode($first, JSON_PRETTY_PRINT) . "\n\n";
        
        echo "✓ Data is being generated correctly!\n";
        echo "✓ Frontend should receive this data in 'upcomingEarnings' prop\n";
    }
} else {
    echo "✗ upcomingEarnings will be empty array []\n";
}

echo "\n=== Done ===\n";
