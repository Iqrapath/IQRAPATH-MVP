# Admin Notifications System

This directory contains all the components and pages for the admin notifications system.

## Structure

```
admin/notifications/
├── components/           # Reusable components
│   ├── index.ts         # Component exports
│   ├── notification-history.tsx
│   ├── scheduled-notifications.tsx
│   ├── completed-classes.tsx
│   ├── urgent-actions.tsx
│   ├── notification-form.tsx
│   └── notification-preview.tsx
├── create.tsx           # Create notification page
├── edit.tsx             # Edit notification page
├── notifications.tsx    # Main notifications dashboard
├── auto-triggers.tsx    # Auto-notification triggers page
└── index.ts             # Main module exports
```

## Usage

### Importing Components

```typescript
// Import specific components
import { NotificationForm, NotificationPreview } from '@/pages/admin/notifications/components';

// Import pages
import { CreateNotificationPage, EditNotificationPage } from '@/pages/admin/notifications';

// Import everything
import * as AdminNotifications from '@/pages/admin/notifications';
```

### Available Components

#### Tab Components
- `NotificationHistory` - Displays notification history with search and filters
- `ScheduledNotifications` - Shows scheduled notifications table
- `CompletedClasses` - Displays completed teaching sessions
- `UrgentActions` - Shows urgent actions that require attention

#### Form Components
- `NotificationForm` - Reusable form for creating/editing notifications
- `NotificationPreview` - Live preview of how notifications will appear

#### Page Components
- `CreateNotificationPage` - Full page for creating new notifications
- `EditNotificationPage` - Full page for editing existing notifications
- `AdminNotificationsPage` - Main notifications dashboard with tabs

## Features

- **Notification Creation**: Create custom notifications or use templates
- **Template System**: Pre-defined notification templates with placeholders
- **Audience Selection**: Send to all users, specific roles, or individual users
- **Multi-channel Delivery**: In-app, email, and SMS notifications
- **Scheduling**: Schedule notifications for future delivery
- **Live Preview**: See how notifications will appear before sending
- **Notification History**: View and manage sent notifications
- **Urgent Actions**: Monitor system alerts and required actions

## Routes

- `/admin/notifications` - Main notifications dashboard
- `/admin/notifications/create` - Create new notification
- `/admin/notifications/{id}/edit` - Edit existing notification
- `/admin/notifications/auto-triggers` - Manage auto-notification triggers
