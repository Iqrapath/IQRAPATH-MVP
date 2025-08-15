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

interface Props {
  templates: NotificationTemplate[];
  users: User[];
}

export default function CreateNotificationPage({ templates, users }: Props) {
  return (
    <AdminLayout pageTitle="Create Notification" showRightSidebar={false}>
      <Head title="Create Notification" />
      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Notifications System', href: '/admin/notifications' },
              { title: 'Create Notification', href: '/admin/notifications/create' },
            ]}
          />
        </div>

        {/* Page Header */}
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-900">Create New Notification</h1>
          <p className="text-gray-600 mt-2">
            Send notifications to users or groups of users. You can use templates or create custom notifications.
          </p>
        </div>

        {/* Notification Form */}
        <NotificationForm templates={templates} users={users} />
      </div>
    </AdminLayout>
  );
}
