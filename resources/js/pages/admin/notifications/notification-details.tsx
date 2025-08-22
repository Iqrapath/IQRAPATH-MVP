import React from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import NotificationPreview from './components/notification-preview';
import { Button } from '@/components/ui/button';
import { ArrowLeft } from 'lucide-react';

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
  notifiable?: {
    id: string;
    name?: string;
    email?: string;
  };
  // Add actual database fields for delivery analytics
  recipient_count?: number;
  delivery_status?: 'delivered' | 'pending' | 'failed';
  open_rate?: number;
  click_through_rate?: number;
  recipients?: Array<{
    id: string;
    name: string;
    email: string;
    status: 'delivered' | 'read' | 'failed';
  }>;
}

interface Props {
  notification: Notification;
}

export default function NotificationDetailsPage({ notification }: Props) {
  const previewData = {
    title: notification.data?.title || notification.type,
    body: notification.data?.message || '',
    level: (notification.level as any) || 'info',
    actionText: notification.data?.action_text || undefined,
    actionUrl: notification.data?.action_url || undefined,
    channels: [notification.channel || 'database'],
    recipientCount: notification.recipient_count || 0,
    deliveryDate: new Date(notification.created_at).toLocaleString(),
    deliveryStatus: notification.delivery_status || 'delivered',
    openRate: notification.open_rate || 0,
    clickThroughRate: notification.click_through_rate || 0,
    recipients: notification.recipients || [],
  };

  return (
    <AdminLayout pageTitle="Notification Details" showRightSidebar={false}>
      <Head title="Notification Details" />
      <div className="container py-6">
        {/* Back Button */}
        <div className="mb-6">
          <Button
            variant="ghost"
            onClick={() => router.visit('/admin/notifications')}
            className="flex items-center gap-2 text-gray-600 hover:text-gray-900"
          >
            <ArrowLeft className="w-4 h-4" />
            Back to Notifications
          </Button>
        </div>

        {/* Notification Details Content */}
        <div className="max-w-4xl mx-auto">
          <NotificationPreview {...previewData} notificationId={notification.id} />
        </div>
      </div>
    </AdminLayout>
  );
}
