# Teacher "Upcoming Earning Due" - Why It's Empty

## Problem

The "Upcoming Earning Due" section in `resources/js/pages/teacher/earnings/components/Earnings.tsx` shows "No upcoming earnings at the moment" even when there might be scheduled sessions.

## Root Cause

Located in: `app/Http/Controllers/Teacher/FinancialController.php` (lines 73-108)

The section is empty because it requires **ALL** of these conditions:

### Condition 1: Teacher Must Have Rates Set ⚠️

```php
$upcomingEarnings = [];
if ($hourlyRateUSD > 0 || $hourlyRateNGN > 0) {
    // Only populate if teacher has rates
}
```

**Check**: Does the teacher have `hourly_rate_usd` or `hourly_rate_ngn` set in their profile?

### Condition 2: Teaching Sessions Must Exist

```php
$upcomingEarnings = \App\Models\TeachingSession::where('teacher_id', $teacher->id)
    ->where('status', 'scheduled')  // Must be 'scheduled' status
    ->where('session_date', '>=', now())  // Must be future date
    ->with(['student', 'subject'])
    ->orderBy('session_date', 'asc')
    ->take(5)
    ->get()
```

**Requirements**:
- `teaching_sessions` table must have records
- `teacher_id` matches logged-in teacher
- `status` = `'scheduled'` (not 'pending', 'completed', 'cancelled')
- `session_date` >= today
- Must have valid `student` and `subject` relationships

### Condition 3: Session Must Have Valid Times

```php
$startTime = \Carbon\Carbon::parse($session->start_time);
$endTime = \Carbon\Carbon::parse($session->end_time);
$durationHours = $startTime->diffInHours($endTime);
```

**Requirements**:
- `start_time` must be valid time
- `end_time` must be valid time
- Duration is calculated from these times

## Why It's Likely Empty

### Most Common Reason: No Hourly Rates Set

The teacher profile doesn't have `hourly_rate_usd` or `hourly_rate_ngn` set. This causes the entire query to be skipped:

```php
// If both rates are 0 or null, upcomingEarnings stays empty []
if ($hourlyRateUSD > 0 || $hourlyRateNGN > 0) {
    // This block never executes
}
```

### Second Most Common: Wrong Session Status

Teaching sessions might exist but have wrong status:
- `'pending'` - Booking not yet approved
- `'completed'` - Session already finished
- `'cancelled'` - Session was cancelled
- `'confirmed'` - Might be used instead of 'scheduled'

Only `'scheduled'` status sessions appear in upcoming earnings.

### Third Most Common: Past Dates

Sessions exist but `session_date < now()` (in the past).

## How to Fix

### Fix 1: Set Teacher Hourly Rates

Update the teacher's profile with hourly rates:

```php
// In database or via admin panel
$teacher->teacherProfile->update([
    'hourly_rate_usd' => 25,  // $25 per hour
    'hourly_rate_ngn' => 37500,  // ₦37,500 per hour
]);
```

Or via SQL:
```sql
UPDATE teacher_profiles 
SET hourly_rate_usd = 25, 
    hourly_rate_ngn = 37500 
WHERE user_id = [teacher_user_id];
```

### Fix 2: Create Scheduled Teaching Sessions

Ensure teaching sessions have correct status:

```php
// When booking is approved, create teaching session
$session = TeachingSession::create([
    'teacher_id' => $teacher->id,
    'student_id' => $student->id,
    'subject_id' => $subject->id,
    'booking_id' => $booking->id,
    'session_date' => now()->addDays(3),  // Future date
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'status' => 'scheduled',  // MUST be 'scheduled'
    'session_type' => 'online',
]);
```

Or update existing sessions:
```sql
UPDATE teaching_sessions 
SET status = 'scheduled' 
WHERE teacher_id = [teacher_id] 
  AND session_date >= CURDATE()
  AND status = 'confirmed';  -- or whatever current status is
```

### Fix 3: Check Session Workflow

The typical workflow should be:

1. **Student books session** → Creates `Booking` with status 'pending'
2. **Teacher approves** → Booking status → 'approved'
3. **System creates TeachingSession** → Status 'scheduled'
4. **Session happens** → Status → 'in_progress'
5. **Session ends** → Status → 'completed'

Make sure step 3 creates the `TeachingSession` with status `'scheduled'`.

## Debugging Steps

### Step 1: Check Teacher Rates
```php
php artisan tinker
```
```php
$teacher = User::where('role', 'teacher')->first();
$profile = $teacher->teacherProfile;
echo "USD Rate: " . $profile->hourly_rate_usd . "\n";
echo "NGN Rate: " . $profile->hourly_rate_ngn . "\n";
```

**Expected**: Both should be > 0

### Step 2: Check Teaching Sessions
```php
$sessions = TeachingSession::where('teacher_id', $teacher->id)
    ->where('session_date', '>=', now())
    ->get();
    
echo "Total future sessions: " . $sessions->count() . "\n";

foreach ($sessions as $session) {
    echo "Session {$session->id}: Status={$session->status}, Date={$session->session_date}\n";
}
```

**Expected**: Should see sessions with status 'scheduled'

### Step 3: Check What Controller Returns
```php
$controller = new \App\Http\Controllers\Teacher\FinancialController();
// Check the upcomingEarnings array in the response
```

## Data Structure Expected

The frontend expects this structure:

```typescript
interface UpcomingEarning {
    id: number;
    amount: number;  // Primary currency amount
    amountSecondary: number;  // Secondary currency amount
    currency: string;  // e.g., 'NGN'
    secondaryCurrency: string;  // e.g., 'USD'
    studentName: string;
    dueDate: string;  // Formatted date
    status: 'pending' | 'completed' | 'cancelled';
}
```

## Quick Test Data

To quickly test with sample data:

```php
// 1. Set teacher rates
DB::table('teacher_profiles')
    ->where('user_id', $teacherId)
    ->update([
        'hourly_rate_usd' => 25,
        'hourly_rate_ngn' => 37500
    ]);

// 2. Create a scheduled session
DB::table('teaching_sessions')->insert([
    'teacher_id' => $teacherId,
    'student_id' => $studentId,
    'subject_id' => $subjectId,
    'session_date' => now()->addDays(2),
    'start_time' => '10:00:00',
    'end_time' => '11:00:00',
    'status' => 'scheduled',
    'session_type' => 'online',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

## Summary

**"Upcoming Earning Due" is empty because:**

1. ❌ Teacher doesn't have hourly rates set (most likely)
2. ❌ No teaching sessions with status 'scheduled'
3. ❌ All sessions are in the past
4. ❌ Sessions have wrong status ('pending', 'confirmed', etc.)

**To fix:**
1. ✅ Set `hourly_rate_usd` and/or `hourly_rate_ngn` in teacher profile
2. ✅ Create teaching sessions with status `'scheduled'`
3. ✅ Ensure session dates are in the future
4. ✅ Verify student and subject relationships exist

The section will populate automatically once these conditions are met.
