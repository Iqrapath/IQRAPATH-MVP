/**
 * Admin Notifications Module
 * 
 * This file exports all components and pages for the admin notifications system.
 * 
 * Main Pages:
 * - AdminNotificationsPage: Main notifications dashboard with tabs
 * - CreateNotificationPage: Page for creating new notifications
 * - EditNotificationPage: Page for editing existing notifications
 * 
 * Components:
 * - All components from ./components (tab components, forms, previews)
 * 
 * Usage:
 * import { AdminNotificationsPage, CreateNotificationPage, NotificationForm } from '@/pages/admin/notifications';
 */

// Main notifications page
export { default as AdminNotificationsPage } from './notifications';

// Form pages
export { default as CreateNotificationPage } from './create';
export { default as EditNotificationPage } from './edit';

// Components
export * from './components';
