<?php

echo "📋 Laravel Log Viewer\n";
echo "====================\n\n";

$logFile = 'storage/logs/laravel.log';

if (!file_exists($logFile)) {
    echo "❌ Log file not found: {$logFile}\n";
    exit(1);
}

echo "📄 Last 50 lines of Laravel log:\n";
echo "================================\n\n";

$lines = file($logFile);
$lastLines = array_slice($lines, -50);

foreach ($lastLines as $line) {
    echo $line;
}

echo "\n🎉 Log viewing completed!\n";
