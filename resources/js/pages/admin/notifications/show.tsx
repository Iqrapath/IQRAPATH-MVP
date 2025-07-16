import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import NotificationShow from '@/components/notification/notification-show';
import NotificationReply from '@/components/notification/notification-reply';

// Define the Notification interface to match the component's expected props
interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  created_at: string;
  sender?: {
    id: number;
    name: string;
    avatar?: string;
  };
}

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar?: string;
}

interface NotificationShowPageProps {
  notification: Notification;
  currentUser: User;
}

export default function AdminNotificationShowPage({ notification, currentUser }: NotificationShowPageProps) {
  const [isReplying, setIsReplying] = useState(false);
  
  const handleReply = () => {
    setIsReplying(true);
  };
  
  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this notification?')) {
      router.delete(`/admin/notifications/${id}`);
    }
  };
  
  const handleCancelReply = () => {
    setIsReplying(false);
  };

  return (
    <AdminLayout pageTitle="Notification Details">
      <Head title="Notification Details" />
      
      {!isReplying ? (
        <NotificationShow
          notification={notification}
          userRole="admin"
          onReply={handleReply}
          onDelete={handleDelete}
          backUrl="/admin/notifications"
        />
      ) : (
        <NotificationReply
          notification={notification}
          userRole="admin"
          currentUser={currentUser}
          onCancel={handleCancelReply}
          backUrl="/admin/notifications"
          submitEndpoint="/admin/notifications/reply"
        />
      )}
    </AdminLayout>
  );
} 