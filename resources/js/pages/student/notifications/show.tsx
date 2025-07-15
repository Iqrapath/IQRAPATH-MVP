import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
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

export default function StudentNotificationShowPage({ notification, currentUser }: NotificationShowPageProps) {
  const [isReplying, setIsReplying] = useState(false);
  
  const handleReply = (notification: Notification) => {
    setIsReplying(true);
  };
  
  const handleDelete = (id: number) => {
    if (confirm('Are you sure you want to delete this notification?')) {
      router.delete(`/student/notifications/${id}`);
    }
  };
  
  const handleCancelReply = () => {
    setIsReplying(false);
  };

  return (
    <StudentLayout pageTitle="Notification Details">
      <Head title="Notification Details" />
      
      {!isReplying ? (
        <NotificationShow
          notification={notification}
          userRole="student"
          onReply={handleReply}
          onDelete={handleDelete}
          backUrl="/student/notifications"
        />
      ) : (
        <NotificationReply
          notification={notification}
          userRole="student"
          currentUser={currentUser}
          onCancel={handleCancelReply}
          backUrl="/student/notifications"
          submitEndpoint="/student/notifications/reply"
        />
      )}
    </StudentLayout>
  );
} 