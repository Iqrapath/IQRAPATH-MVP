# Notification System Architecture

## Overview

The notification system is designed with a clear separation of concerns to manage different aspects of notifications within the application. All notification-related routes are centralized in the `routes/notifications.php` file for better organization and maintainability.

## Controller Structure

### Admin Controllers

1. **AdminNotificationController**
   - Manages outgoing notifications created by admins
   - Handles sending notifications to users
   - Provides interfaces for admins to view their received notifications
   - Methods for managing notification status (read/unread)

2. **NotificationTemplateController**
   - Manages notification templates
   - CRUD operations for templates
   - Template listing and filtering

3. **NotificationTriggerController**
   - Manages notification triggers (automated notifications)
   - CRUD operations for triggers
   - Trigger listing and filtering

### Role-Specific Controllers

1. **Teacher\NotificationController**
   - Manages notifications for teachers
   - Views, marks as read, and deletes notifications

2. **Student\NotificationController**
   - Manages notifications for students
   - Views, marks as read, and deletes notifications

3. **Guardian\NotificationController**
   - Manages notifications for guardians
   - Views, marks as read, and deletes notifications

### API Controllers

1. **Api\NotificationController**
   - Provides API endpoints for notifications
   - Used by frontend components to fetch notifications

### Utility Controllers

1. **NotificationRedirectController**
   - Universal notification handler that redirects to the appropriate role-specific notification page
   - Used for generic notification links that work for all user types

2. **UserNotificationController**
   - Generic notification controller for handling user notifications
   - Provides role-agnostic methods for viewing and managing notifications
   - Routes requests to the appropriate role-specific controllers based on user role
   - Useful for shared notification functionality across different user roles

## Route Structure (All in routes/notifications.php)

- Admin notification management: `/admin/notification/*`
- Admin templates: `/admin/notification/templates/*`
- Admin triggers: `/admin/notification/triggers/*`
- Admin notification history: `/admin/notification-history`
- Teacher notifications: `/teacher/notifications/*`
- Student notifications: `/student/notifications/*`
- Guardian notifications: `/guardian/notifications/*`
- API endpoints: `/api/notifications/*`
- Universal notification redirect: `/notification/{id}`
- Generic user notifications: `/notifications/*`

## Models

1. **Notification**
   - Represents a notification message
   - Contains title, body, type, status, etc.

2. **NotificationRecipient**
   - Links notifications to recipients
   - Tracks read status for each recipient
   - Manages delivery channels

3. **NotificationTemplate**
   - Reusable notification templates
   - Contains placeholders for dynamic content

4. **NotificationTrigger**
   - Defines conditions for automatic notifications
   - Links to templates for content

## Services

**NotificationService**
- Creates notifications
- Adds recipients
- Sends notifications through various channels

## Future Improvements

1. Add support for more notification channels (SMS, mobile push)
2. Implement notification preferences for users
3. Add notification analytics dashboard
4. Implement notification categories for better filtering 