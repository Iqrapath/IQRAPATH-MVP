<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Debugging Teacher Earnings Props ===\n\n";

// Get teacher ID 23 (the one we know has sessions)
$teacherId = 23;
$teacher = \App\Models\User::find($teacherId);

echo "Teacher: {$teacher->name} (ID: {$teacher->id})\n\n";

// Simulate what the controller does
Auth::login($teacher);

$controller = new \App\Http\Controllers\Teacher\FinancialController(
    app(\App\Services\FinancialService::class),
    app(\App\Services\CurrencyService::class),
    app(\App\Services\WalletSyncService::class)
);

// Call the index method
$response = $controller->index();

// Get the view data from Inertia response
$viewData = $response->viewData;
$props = $viewData['page']['props'] ?? [];

echo "=== Inertia Props ===\n\n";

// Check if upcomingEarnings exists
if (isset($props['upcomingEarnings'])) {
    $upcomingEarnings = $props['upcomingEarnings'];
    echo "upcomingEarnings exists: YES\n";
    echo "Type: " . gettype($upcomingEarnings) . "\n";
    
    if (is_array($upcomingEarnings) || is_object($upcomingEarnings)) {
        $count = is_array($upcomingEarnings) ? count($upcomingEarnings) : count((array)$upcomingEarnings);
        echo "Count: {$count}\n\n";
        
        if ($count > 0) {
            echo "✓ upcomingEarnings HAS DATA!\n\n";
            echo "First item:\n";
            echo json_encode($upcomingEarnings[0] ?? $upcomingEarnings, JSON_PRETTY_PRINT) . "\n\n";
        } else {
            echo "✗ upcomingEarnings is EMPTY\n\n";
        }
    }
} else {
    echo "✗ upcomingEarnings NOT FOUND in props\n\n";
}

// Check other relevant props
echo "Other props:\n";
echo "- walletBalance: " . ($props['walletBalance'] ?? 'NOT SET') . "\n";
echo "- totalEarned: " . ($props['totalEarned'] ?? 'NOT SET') . "\n";
echo "- pendingPayouts: " . ($props['pendingPayouts'] ?? 'NOT SET') . "\n";

if (isset($props['teacherRates'])) {
    echo "- teacherRates: " . json_encode($props['teacherRates']) . "\n";
}

if (isset($props['earningsSettings'])) {
    echo "- earningsSettings: " . json_encode($props['earningsSettings']) . "\n";
}

echo "\n=== Done ===\n";
