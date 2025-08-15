# Admin Notification System Implementation

## Overview

The IQRAPATH platform now includes a comprehensive notification system that automatically notifies super-admin and assigned admin users when new users create accounts. This system is built on Laravel's event-driven architecture and provides real-time notifications through multiple channels.

## How It Works

### 1. Event Flow

When a new user registers:

1. **User Registration**: User fills out registration form and submits
2. **Event Dispatch**: `UserRegistered` event is dispatched with user data
3. **Listener Execution**: Multiple listeners are triggered:
   - `SendWelcomeNotification` - Sends welcome notification to new user
   - `NotifyAdminsOfNewUser` - Notifies all admin users
   - `ProcessNotificationTrigger` - Processes any configured notification triggers
4. **Notification Creation**: Admin notifications are created and stored
5. **Real-time Delivery**: Notifications are broadcast to admin users via WebSockets

### 2. Admin Notification Details

Each admin notification includes:
- **Title**: "New User Registration: [User Name]"
- **Message**: Brief description of the new registration
- **Action Button**: "View User" linking to user management page
- **Detailed Information**:
  - New user's name, email, phone
  - Registration timestamp
  - Direct link to user profile

## Implementation Components

### 1. Event Listener: `NotifyAdminsOfNewUser`

**Location**: `app/Listeners/NotifyAdminsOfNewUser.php`

**Purpose**: Automatically notifies all super-admin and admin users when new users register

**Key Features**:
- Filters users by admin roles (`super-admin`, `admin`)
- Excludes the new user from notifications (if they're an admin)
- Creates detailed notifications with user information
- Handles errors gracefully with logging

**Code Example**:
```php
public function handle(UserRegistered $event): void
{
    $newUser = $event->user;
    
    // Get all super-admin and admin users
    $adminUsers = User::whereIn('role', ['super-admin', 'admin'])
        ->where('id', '!=', $newUser->id)
        ->get();

    foreach ($adminUsers as $admin) {
        $notification = $this->notificationService->createNotification(
            $admin,
            'new_user_registration',
            [
                'title' => 'New User Registration',
                'message' => "A new user '{$newUser->name}' has registered on the platform.",
                'action_text' => 'View User',
                'action_url' => route('admin.user-management.show', $newUser->id),
                // ... additional user details
            ],
            'info'
        );
    }
}
```

### 2. Notification Template

**Location**: `database/seeders/NotificationTemplateSeeder.php`

**Template Name**: `new_user_registration`

**Placeholders**:
- `{User_Name}` - New user's name
- `{User_Email}` - New user's email
- `{User_Phone}` - New user's phone number
- `{Registration_Time}` - Registration timestamp

### 3. Notification Trigger

**Location**: `database/seeders/NotificationTriggerSeeder.php`

**Configuration**:
- **Event**: `UserRegistered`
- **Audience**: Role-based (`super-admin`, `admin`)
- **Channels**: In-app and email
- **Timing**: Immediate
- **Level**: Info

### 4. React Component: `AdminNotifications`

**Location**: `resources/js/components/admin/admin-notifications.tsx`

**Features**:
- Real-time notification display
- Unread count badge
- Mark as read functionality
- Special handling for new user registrations
- Responsive dropdown interface
- Action button integration

**Usage in Admin Dashboard**:
```tsx
<AdminNotifications 
    notifications={adminNotifications} 
    unreadCount={unreadCount} 
/>
```

## Database Structure

### Notifications Table
```sql
CREATE TABLE notifications (
    id UUID PRIMARY KEY,
    type VARCHAR(255),
    notifiable_type VARCHAR(255),
    notifiable_id BIGINT,
    data JSON,
    read_at TIMESTAMP NULL,
    channel VARCHAR(255) DEFAULT 'database',
    level VARCHAR(255) DEFAULT 'info',
    action_text VARCHAR(255) NULL,
    action_url VARCHAR(255) NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Key Fields for Admin Notifications:
- **type**: `new_user_registration`
- **notifiable_type**: `App\Models\User`
- **notifiable_id**: Admin user's ID
- **data**: JSON containing notification details
- **level**: `info`
- **action_text**: "View User"
- **action_url**: Link to user management page

## Configuration

### 1. Event Service Provider

**Location**: `app/Providers/EventServiceProvider.php`

**Registration**:
```php
protected $listen = [
    UserRegistered::class => [
        SendWelcomeNotification::class,
        ProcessNotificationTrigger::class,
        \App\Listeners\NotifyAdminsOfNewUser::class, // New listener
    ],
];
```

### 2. Broadcasting Channels

**Location**: `routes/channels.php`

**Admin Channel**:
```php
Broadcast::channel('admin.notifications', function ($user) {
    return $user->hasRole('super-admin') || $user->hasRole('admin');
});
```

## Testing

### 1. Test Command

**Command**: `php artisan test:notifications`

**Options**:
- `--user-id=123` - Test with existing user
- No options - Creates test user automatically

**Example Usage**:
```bash
# Test with existing user
php artisan test:notifications --user-id=5

# Test with auto-created user
php artisan test:notifications
```

### 2. Manual Testing

1. **Register New User**: Use the registration form
2. **Check Admin Dashboard**: Look for notification bell with unread count
3. **View Notifications**: Click bell to see notification dropdown
4. **Verify Details**: Check that new user information is displayed
5. **Test Action**: Click "View User" to navigate to user management

## Troubleshooting

### Common Issues

1. **No Notifications Appearing**
   - Check if admin users exist with correct roles
   - Verify event listener is registered
   - Check logs for errors

2. **Notifications Not Broadcasting**
   - Verify broadcasting configuration
   - Check WebSocket server status
   - Verify channel authorization

3. **Template Not Found**
   - Run `php artisan db:seed --class=NotificationTemplateSeeder`
   - Check template name matches exactly

### Debug Commands

```bash
# Check registered events
php artisan event:list

# Check notification templates
php artisan tinker
>>> App\Models\NotificationTemplate::all()

# Check notification triggers
>>> App\Models\NotificationTrigger::all()

# Test notification service
>>> app('App\Services\NotificationService')->createNotification(...)
```

## Future Enhancements

### 1. Additional Notification Types
- User role changes
- Payment confirmations
- System maintenance alerts
- Security alerts

### 2. Advanced Filtering
- Department-based notifications
- Custom notification preferences
- Notification scheduling

### 3. Enhanced UI
- Notification categories
- Bulk actions
- Advanced search
- Notification history

## Security Considerations

1. **Authorization**: Only admin users receive notifications
2. **Data Privacy**: Sensitive user information is handled securely
3. **Rate Limiting**: Prevents notification spam
4. **Audit Logging**: All notification activities are logged

## Performance Optimization

1. **Queue Processing**: Notifications are processed asynchronously
2. **Database Indexing**: Optimized queries for notification retrieval
3. **Caching**: Notification counts are cached for performance
4. **Batch Processing**: Multiple notifications are processed efficiently

## Monitoring and Analytics

1. **Notification Metrics**: Track delivery rates and read rates
2. **User Engagement**: Monitor admin response to notifications
3. **System Health**: Monitor notification processing performance
4. **Error Tracking**: Log and alert on notification failures

## Conclusion

The admin notification system provides a robust, scalable solution for keeping administrators informed about new user registrations. The event-driven architecture ensures reliable delivery while the comprehensive UI components provide an excellent user experience.

For questions or issues, refer to the troubleshooting section or contact the development team.
