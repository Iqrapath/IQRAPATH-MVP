<?php
/**
 * Test Script for Database Seeders
 * 
 * This script can be run to test individual seeders or verify the complete setup.
 * Run this from the Laravel project root directory.
 */

// Load Laravel
require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

echo "🧪 Testing Database Seeders\n";
echo "============================\n\n";

// Test 1: Check if database is accessible
try {
    DB::connection()->getPdo();
    echo "✅ Database connection: OK\n";
} catch (\Exception $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check current user counts
echo "\n📊 Current User Counts:\n";
echo "------------------------\n";

$adminCount = User::where('role', 'super-admin')->count();
$teacherCount = User::where('role', 'teacher')->count();
$studentCount = User::where('role', 'student')->count();
$guardianCount = User::where('role', 'guardian')->count();
$unassignedCount = User::whereNull('role')->count();
$totalCount = User::count();

echo "Admins: {$adminCount}\n";
echo "Teachers: {$teacherCount}\n";
echo "Students: {$studentCount}\n";
echo "Guardians: {$guardianCount}\n";
echo "Unassigned: {$unassignedCount}\n";
echo "Total: {$totalCount}\n";

// Test 3: Check if seeders need to be run
if ($totalCount === 0) {
    echo "\n🚀 No users found. You need to run the seeders first:\n";
    echo "   php artisan db:seed\n";
} elseif ($totalCount < 100) {
    echo "\n⚠️  Some users found but seeders may not be complete.\n";
    echo "   Run: php artisan db:seed\n";
} else {
    echo "\n✅ Seeder appears to be complete!\n";
}

// Test 4: Check specific test users
echo "\n🔑 Test User Credentials:\n";
echo "---------------------------\n";

$testUsers = [
    'admin@sch.com' => 'super-admin',
    'teacher@sch.com' => 'teacher',
    'student@sch.com' => 'student',
    'guardian@sch.com' => 'guardian',
    'unassigned@sch.com' => null,
];

foreach ($testUsers as $email => $expectedRole) {
    $user = User::where('email', $email)->first();
    if ($user) {
        $status = $user->role === $expectedRole ? '✅' : '❌';
        echo "{$status} {$email} - Role: " . ($user->role ?? 'unassigned') . "\n";
    } else {
        echo "❌ {$email} - Not found\n";
    }
}

// Test 5: Check profile relationships
echo "\n🔗 Profile Relationships:\n";
echo "-------------------------\n";

$usersWithProfiles = User::whereNotNull('role')->get();
$profileCounts = [
    'super-admin' => 0,
    'teacher' => 0,
    'student' => 0,
    'guardian' => 0,
];

foreach ($usersWithProfiles as $user) {
    $profile = $user->profile();
    if ($profile) {
        $profileCounts[$user->role]++;
    }
}

echo "Users with admin profiles: {$profileCounts['super-admin']}\n";
echo "Users with teacher profiles: {$profileCounts['teacher']}\n";
echo "Users with student profiles: {$profileCounts['student']}\n";
echo "Users with guardian profiles: {$profileCounts['guardian']}\n";

// Test 6: Check guardian-student relationships
echo "\n👨‍👩‍👧‍👦 Guardian-Student Relationships:\n";
echo "----------------------------------------\n";

$guardiansWithChildren = User::where('role', 'guardian')->get();
$totalChildren = 0;

foreach ($guardiansWithChildren as $guardian) {
    if ($guardian->guardianProfile) {
        $childrenCount = $guardian->guardianProfile->children_count;
        $totalChildren += $childrenCount;
        if ($childrenCount > 0) {
            echo "✅ Guardian {$guardian->name} has {$childrenCount} children\n";
        }
    }
}

echo "Total children assigned: {$totalChildren}\n";

echo "\n🎉 Testing Complete!\n";
echo "====================\n\n";

echo "To run the seeders:\n";
echo "  php artisan db:seed                    # Run all seeders\n";
echo "  php artisan db:seed --class=AdminSeeder    # Run specific seeder\n";
echo "  php artisan db:seed --class=TeacherSeeder  # Run specific seeder\n";
echo "  php artisan db:seed --class=StudentSeeder  # Run specific seeder\n";
echo "  php artisan db:seed --class=GuardianSeeder # Run specific seeder\n";
echo "  php artisan db:seed --class=UnassignedUserSeeder # Run specific seeder\n\n";

echo "To reset and reseed:\n";
echo "  php artisan migrate:fresh --seed\n\n";
