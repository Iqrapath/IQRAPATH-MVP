# Permanent Meeting Links & Session Access Control System

## Overview
This document describes the new system for managing teacher meeting links and controlling student/admin access to teaching sessions.

## Key Features Implemented

### 1. **Permanent Meeting Links for Teachers**
- Teachers now have a **single permanent meeting link** stored in their profile
- This link is reused for all their teaching sessions (no new links generated per session)
- Reduces API calls to Zoom/Google Meet
- Makes it easier for admins to join any teacher's class for monitoring

#### Database Changes
**Migration**: `2025_10_23_000000_add_permanent_meeting_link_to_teacher_profiles.php`

New fields added to `teacher_profiles` table:
- `permanent_meeting_link` - The meeting URL (Zoom, Google Meet, etc.)
- `permanent_meeting_platform` - Platform type (zoom, google_meet, other)
- `permanent_meeting_id` - Extracted meeting ID
- `permanent_meeting_password` - Meeting password (if applicable)
- `permanent_meeting_created_at` - When the link was created

#### How It Works
1. **During Teacher Verification**: When an admin schedules a verification call, the meeting link is automatically saved as the teacher's permanent link
2. **For Teaching Sessions**: When a booking is approved and a session is created, the system uses the teacher's permanent link instead of generating a new one
3. **Fallback**: If a teacher doesn't have a permanent link, the system falls back to creating a new Zoom/Google Meet link per session (legacy behavior)

### 2. **Time-Based Access Control**
Students and teachers can only access class links at specific times:

#### Access Rules

**For Students:**
- ✅ Can join **15 minutes before** session start time
- ✅ Can stay until **30 minutes after** session end time
- ❌ **Cannot access** before the 15-minute window
- ❌ **Cannot access** after the 30-minute grace period

**For Teachers:**
- ✅ Can join **30 minutes before** session start time (for preparation)
- ✅ Can stay until **30 minutes after** session end time
- ❌ **Cannot access** before the 30-minute window
- ❌ **Cannot access** after the 30-minute grace period

**For Admins:**
- ✅ **Can access ANY session at ANY time** (for monitoring purposes)
- ✅ Access is logged for audit trails
- ✅ Marked as "admin monitoring" in logs

#### Implementation Details
**Service**: `app/Services/SessionAccessControlService.php`

Key methods:
- `canAccessSession()` - Checks if user can access a session right now
- `getMeetingLink()` - Returns meeting link if access is granted
- `getActiveSessionsForMonitoring()` - Lists all ongoing sessions for admins

### 3. **Admin Monitoring Dashboard**
Admins can view and join any active teaching session to monitor quality.

#### Monitoring Features
- **Real-time view** of all active sessions
- Shows sessions that are:
  - Currently in progress
  - Starting soon (within 30 minutes)
  - Recently ended (within 30 minutes)
- **One-click access** to join any session
- **Attendance tracking**: See when teacher/student joined
- **Session status**: Scheduled, In Progress, Completed, etc.

#### Routes
- `/admin/monitoring/sessions` - Monitoring dashboard UI
- `/admin/monitoring/sessions/active` - API endpoint for active sessions

### 4. **Enhanced Notifications**
Notifications already exist for:
- ✅ **Booking Created** - Student and Teacher receive notifications
- ✅ **Booking Approved** - Student and Teacher receive notifications with meeting link
- ✅ **Session Reminders** - 15-minute reminder before session starts
- ✅ **Email Notifications** - All events trigger email notifications

**Service**: `app/Services/BookingNotificationService.php`

## API Endpoints

### Session Access Control
```
GET /sessions/{sessionId}/check-access
GET /sessions/{sessionId}/meeting-link
GET /sessions/{sessionId}/join
GET /sessions/{sessionId}/waiting-room
```

### Admin Monitoring
```
GET /admin/monitoring/sessions
GET /admin/monitoring/sessions/active
```

## Usage Examples

### For Students/Teachers - Join a Session
```php
// Check if can access
$result = $sessionAccessService->canAccessSession($session, $user);

if ($result['can_access']) {
    // Get meeting link and join
    $link = $sessionAccessService->getMeetingLink($session, $user);
    return redirect()->away($link['meeting_link']);
} else {
    // Show waiting room with countdown
    return redirect()->route('sessions.waiting-room', $session->id);
}
```

### For Admins - Monitor Active Sessions
```php
// Get all active sessions
$sessions = $sessionAccessService->getActiveSessionsForMonitoring();

// Join any session for monitoring
foreach ($sessions as $session) {
    // Admin can click to join immediately
    $monitoringAccess = $sessionAccessService->getMeetingLink($session, $admin);
    // $monitoringAccess['is_admin_monitoring'] will be true
}
```

### Setting a Teacher's Permanent Link (Manual)
```php
$teacher->teacherProfile->update([
    'permanent_meeting_link' => 'https://zoom.us/j/1234567890',
    'permanent_meeting_platform' => 'zoom',
    'permanent_meeting_id' => '1234567890',
    'permanent_meeting_password' => 'abc123',
    'permanent_meeting_created_at' => now(),
]);
```

## Access Control Flow

```
Student clicks "Join Class"
        ↓
Check access (SessionAccessControlService)
        ↓
    Too Early?
        ↓ Yes
    Waiting Room → "Available in X minutes"
        ↓ No
    Too Late?
        ↓ Yes
    Error → "Session has ended"
        ↓ No
    Access Granted
        ↓
    Get Meeting Link (teacher's permanent link)
        ↓
    Update Attendance (teacher_joined_at / student_joined_at)
        ↓
    Redirect to Zoom/Google Meet
```

## Security & Audit

### Logging
All session access attempts are logged:
```php
Log::info('Session meeting link accessed', [
    'session_id' => $session->id,
    'user_id' => $user->id,
    'user_role' => $user->role,
    'is_admin_monitoring' => $isAdmin,
    'access_time' => now(),
]);
```

### Attendance Tracking
- `teacher_joined_at` - Timestamp when teacher first joins
- `student_joined_at` - Timestamp when student first joins
- `teacher_left_at` - Timestamp when teacher leaves
- `student_left_at` - Timestamp when student leaves
- `teacher_marked_present` - Boolean flag
- `student_marked_present` - Boolean flag

### Session Status Auto-Update
When first participant joins:
- Status automatically changes from `scheduled` to `in_progress`
- Logged for audit trail

## Migration Instructions

### Run the Migration
```bash
php artisan migrate
```

This will add the permanent meeting link fields to the `teacher_profiles` table.

### Update Existing Teachers (Optional)
If you want to set permanent links for existing teachers who already have verification calls:

```php
// Artisan command (create if needed)
php artisan teachers:setup-permanent-links
```

Or manually in tinker:
```php
php artisan tinker

$teacher = User::find(123);
$teacher->teacherProfile->update([
    'permanent_meeting_link' => 'https://zoom.us/j/your-meeting-id',
    'permanent_meeting_platform' => 'zoom',
    'permanent_meeting_id' => 'your-meeting-id',
]);
```

## Testing

### Test Time-Based Access
1. Create a teaching session for tomorrow at 10:00 AM
2. Try to join as student at 9:00 AM → Should show "Available in 45 minutes"
3. Try to join as student at 9:46 AM → Should grant access (15 min early)
4. Try to join as teacher at 9:31 AM → Should grant access (30 min early)
5. Try to join as admin anytime → Should grant immediate access

### Test Permanent Links
1. Schedule a verification call for a teacher
2. Check `teacher_profiles` table → permanent_meeting_link should be populated
3. Approve a booking for that teacher
4. Check `teaching_sessions` table → should use the same permanent link

### Test Admin Monitoring
1. Login as admin
2. Navigate to `/admin/monitoring/sessions`
3. Should see all active/upcoming sessions
4. Click "Join Session" → Should join immediately without time restrictions

## Benefits

### For Teachers
- ✅ **One consistent link** for all classes
- ✅ Easy to share with students
- ✅ No setup needed before each class
- ✅ Students can bookmark the link

### For Students
- ✅ **Cannot join too early** (prevents waiting in empty rooms)
- ✅ **Cannot join too late** (session already ended)
- ✅ Clear countdown to when they can join
- ✅ Automatic redirect when time comes

### For Admins
- ✅ **Monitor any class** at any time
- ✅ Quick oversight of teaching quality
- ✅ Identify issues in real-time
- ✅ No need to ask teachers for links
- ✅ Audit trail of all monitoring activities

### For Platform
- ✅ **Reduced API calls** to Zoom/Google Meet
- ✅ Cost savings on API quotas
- ✅ Faster session creation
- ✅ Better user experience
- ✅ Enhanced security and control

## Troubleshooting

### "Meeting link not found" error
- Check if teacher has `permanent_meeting_link` set
- If not, the system should fall back to creating a new link
- Check logs for any errors during link creation

### Student can't join even though it's time
- Check server timezone vs user timezone
- Verify `session_date` and `start_time` are correct
- Check if session status is not 'cancelled' or 'completed'

### Admin monitoring shows no sessions
- Check if there are any sessions scheduled for today
- Verify sessions are in `scheduled` or `in_progress` status
- Check time range (only shows sessions ±30 min from now)

## Future Enhancements

Potential improvements:
1. **Teacher dashboard** to manage their permanent link
2. **Email reminders** with countdown timer
3. **Auto-complete sessions** 30 minutes after end time
4. **Recording management** for permanent meeting rooms
5. **Analytics** on attendance patterns
6. **Waiting room UI** improvements with real-time countdown

---

**Last Updated**: October 23, 2025  
**Version**: 1.0  
**Author**: IQRAPATH Development Team

