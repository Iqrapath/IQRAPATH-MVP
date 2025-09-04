# Google Meet Integration for IQRAPATH

This document explains how to use the Google Meet integration in the IQRAPATH platform.

## Overview

The Google Meet integration allows the platform to create Google Meet events for teaching sessions using the Google Calendar API. This provides an alternative to Zoom for video conferencing.

## Configuration

### Environment Variables

Add these variables to your `.env` file:

```env
# Google Meet/Calendar API Configuration
GOOGLE_MEET_CLIENT_ID=your_google_client_id
GOOGLE_MEET_CLIENT_SECRET=your_google_client_secret
GOOGLE_MEET_REFRESH_TOKEN=your_refresh_token
GOOGLE_MEET_WEBHOOK_SECRET=your_webhook_secret
GOOGLE_MEET_CALENDAR_ID=primary
```

### Google Cloud Console Setup

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project or select existing one
3. Enable the Google Calendar API
4. Create OAuth 2.0 credentials
5. Generate a refresh token for server-to-server authentication

## Usage Examples

### Creating a Google Meet Event for a Teaching Session

```php
use App\Services\GoogleMeetService;
use App\Models\TeachingSession;
use App\Models\User;

// Get the service
$googleMeetService = app(GoogleMeetService::class);

// Create a meeting for a teaching session
$session = TeachingSession::find(1);
$teacher = User::find($session->teacher_id);

$meetingData = $googleMeetService->createMeeting($session, $teacher);

// The session will be updated with:
// - meeting_platform: 'google_meet'
// - google_meet_id: extracted from meet link
// - google_meet_link: full Google Meet URL
// - google_calendar_event_id: Google Calendar event ID
// - meeting_link: same as google_meet_link
```

### Creating an Ad-hoc Google Meet Event

```php
use App\Services\GoogleMeetService;
use Carbon\Carbon;

$googleMeetService = app(GoogleMeetService::class);

$meetingData = $googleMeetService->createAdhocMeeting(
    topic: 'Verification Call',
    startAt: Carbon::now()->addHour(),
    durationMinutes: 30,
    organizerEmail: 'teacher@example.com'
);

// Returns:
// [
//     'id' => 'google_calendar_event_id',
//     'meet_id' => 'extracted_meet_id',
//     'meet_link' => 'https://meet.google.com/abc-defg-hij',
//     'event_id' => 'google_calendar_event_id'
// ]
```

### Using the Booking Model

```php
use App\Models\Booking;
use App\Models\TeachingSession;

$booking = Booking::find(1);
$session = $booking->createTeachingSession();

// Create Google Meet event
$meetingData = $booking->createGoogleMeetEvent($session);
```

### Managing Events

```php
use App\Services\GoogleMeetService;

$googleMeetService = app(GoogleMeetService::class);

// Get event details
$event = $googleMeetService->getEvent('google_calendar_event_id');

// Update event
$updatedEvent = $googleMeetService->updateEvent('event_id', [
    'summary' => 'Updated Session Title',
    'description' => 'Updated description'
]);

// Delete event
$googleMeetService->deleteEvent('event_id');
```

## Webhook Integration

### Setting up Google Calendar Push Notifications

1. Register your webhook endpoint with Google Calendar API
2. Use the provided webhook routes:

```php
// Routes are automatically registered in routes/sessions.php
POST /api/google-meet/webhook
POST /api/google-meet/push-notification
```

### Webhook Events Handled

- `meeting.started` - Meeting has started
- `meeting.ended` - Meeting has ended
- `meeting.participant_joined` - Participant joined (not available via Calendar API)
- `meeting.participant_left` - Participant left (not available via Calendar API)

### Push Notifications

Google Calendar push notifications are handled for:
- `created` - Event created
- `updated` - Event updated
- `deleted` - Event deleted (marks session as cancelled)

## Important Notes

### Limitations

1. **Participant Tracking**: Google Meet doesn't provide participant join/leave events via the Calendar API. This would require the Google Meet API or manual tracking.

2. **Attendance Data**: The `updateAttendanceData()` method currently marks both teacher and student as present by default. In a production environment, you'd need to implement manual tracking or use the Google Meet API.

3. **Authentication**: The service uses OAuth 2.0 with refresh tokens. Make sure your Google Cloud project is properly configured for server-to-server authentication.

### Database Schema

The following columns were added to the `teaching_sessions` table:

- `google_meet_id` - Extracted Google Meet ID
- `google_meet_link` - Full Google Meet URL
- `google_calendar_event_id` - Google Calendar event ID

### Service Methods

#### GoogleMeetService Methods

- `createMeeting(TeachingSession $session, User $teacher)` - Create meeting for teaching session
- `createAdhocMeeting(string $topic, DateTimeInterface $startAt, int $durationMinutes, ?string $organizerEmail)` - Create ad-hoc meeting
- `getEvent(string $eventId)` - Get event details
- `updateEvent(string $eventId, array $eventData)` - Update event
- `deleteEvent(string $eventId)` - Delete event
- `getMeetingParticipants(string $meetId)` - Get participants (placeholder)
- `updateAttendanceData(TeachingSession $session)` - Update attendance (placeholder)

#### Booking Model Methods

- `createGoogleMeetEvent(TeachingSession $session)` - Create Google Meet event for session

## Testing

### Test Google Meet Creation

```php
// In a controller or command
$session = TeachingSession::first();
$teacher = $session->teacher;

try {
    $googleMeetService = app(GoogleMeetService::class);
    $meetingData = $googleMeetService->createMeeting($session, $teacher);
    
    echo "Meeting created successfully!\n";
    echo "Meet Link: " . $meetingData['conferenceData']['entryPoints'][0]['uri'] . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

### Verify Database Updates

```php
$session = TeachingSession::find(1);
echo "Meeting Platform: " . $session->meeting_platform . "\n";
echo "Google Meet ID: " . $session->google_meet_id . "\n";
echo "Google Meet Link: " . $session->google_meet_link . "\n";
echo "Calendar Event ID: " . $session->google_calendar_event_id . "\n";
```

## Troubleshooting

### Common Issues

1. **Authentication Errors**: Check your Google Cloud Console configuration and refresh token
2. **API Quota Exceeded**: Google Calendar API has daily quotas
3. **Calendar Permissions**: Ensure the service account has access to the calendar
4. **Webhook Verification**: Implement proper signature verification in production

### Logs

Check Laravel logs for detailed error messages:
```bash
tail -f storage/logs/laravel.log
```

## Security Considerations

1. **Webhook Verification**: Implement proper signature verification for production
2. **Token Security**: Store refresh tokens securely
3. **API Keys**: Never expose client secrets in frontend code
4. **Rate Limiting**: Implement rate limiting for API calls

## Next Steps

1. Set up Google Cloud Console project
2. Configure environment variables
3. Test meeting creation
4. Set up webhook endpoints
5. Implement proper attendance tracking (if needed)
6. Add Google Meet option to frontend booking flow
