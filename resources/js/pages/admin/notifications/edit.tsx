import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import NotificationForm from './components/notification-form';

interface NotificationTemplate {
  id: number;
  name: string;
  title: string;
  body: string;
  type: string;
  placeholders: string[];
  level: string;
  action_text: string | null;
  action_url: string | null;
  is_active: boolean;
}

interface User {
  id: number;
  name: string;
  email: string;
  role: string | null;
}

interface Notification {
  id: string;
  type: string;
  data: any;
  read_at: string | null;
  channel: string;
  level: string;
  action_text: string | null;
  action_url: string | null;
  created_at: string;
  updated_at: string;
}

interface Props {
  notification: Notification;
  templates: NotificationTemplate[];
  users: User[];
}

export default function EditNotificationPage({ notification, templates, users }: Props) {
  return (
    <AdminLayout pageTitle="Edit Notification" showRightSidebar={false}>
      <Head title="Edit Notification" />
      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Notifications System', href: '/admin/notifications' },
              { title: 'Edit Notification', href: `/admin/notifications/${notification.id}/edit` },
            ]}
          />
        </div>

        {/* Page Header */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900">Edit Notification</h1>
          <p className="text-gray-600 mt-2">
            Modify the notification content and recipients.
          </p>
        </div>

        {/* Notification Form */}
        <NotificationForm 
          templates={templates} 
          users={users} 
          notification={notification}
          isEditing={true}
        />
      </div>
    </AdminLayout>
  );
}
