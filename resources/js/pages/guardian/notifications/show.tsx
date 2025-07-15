import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import NotificationShow from '@/components/notification/notification-show';
import NotificationReply from '@/components/notification/notification-reply';

interface User {
  id: number;
  name: string;
  email?: string;
  role?: string;
  avatar?: string;
}

interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  created_at: string;
  sender?: User;
}

interface NotificationShowPageProps {
  notification: Notification;
  currentUser: User;
}

export default function GuardianNotificationShowPage({ notification, currentUser }: NotificationShowPageProps) {
  const [isReplying, setIsReplying] = useState(false);
  
  const handleReply = (notification: Notification) => {
    setIsReplying(true);
  };
  
  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this notification?')) {
      router.delete(`/guardian/notifications/${id}`);
    }
  };
  
  const handleCancelReply = () => {
    setIsReplying(false);
  };

  return (
    <GuardianLayout pageTitle="Notification Details">
      <Head title="Notification Details" />
      
      {!isReplying ? (
        <NotificationShow
          notification={notification}
          userRole="guardian"
          onReply={handleReply}
          onDelete={handleDelete}
          backUrl="/guardian/notifications"
        />
      ) : (
        <NotificationReply
          notification={notification}
          userRole="guardian"
          currentUser={currentUser}
          onCancel={handleCancelReply}
          backUrl="/guardian/notifications"
          submitEndpoint="/guardian/notifications/reply"
        />
      )}
    </GuardianLayout>
  );
} 