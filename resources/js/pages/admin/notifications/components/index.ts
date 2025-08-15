/**
 * Admin Notifications Components
 * 
 * This file exports all the components used in the admin notifications system.
 * 
 * Tab Components:
 * - NotificationHistory: Displays notification history with search and filters
 * - ScheduledNotifications: Shows scheduled notifications table
 * - CompletedClasses: Displays completed teaching sessions
 * - UrgentActions: Shows urgent actions that require attention
 * 
 * Form Components:
 * - NotificationForm: Reusable form for creating/editing notifications
 * - NotificationPreview: Live preview of how notifications will appear
 * 
 * Page Components:
 * - CreateNotificationPage: Full page for creating new notifications
 * - EditNotificationPage: Full page for editing existing notifications
 */

// Tab components
export { default as NotificationHistory } from './notification-history';
export { default as ScheduledNotifications } from './scheduled-notifications';
export { default as CompletedClasses } from './completed-classes';
export { default as UrgentActions } from './urgent-actions';

// Form and preview components
export { default as NotificationForm } from './notification-form';
export { default as NotificationPreview } from './notification-preview';

// Page components
export { default as CreateNotificationPage } from '../create';
export { default as EditNotificationPage } from '../edit';
