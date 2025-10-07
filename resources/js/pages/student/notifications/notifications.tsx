import React, { useState } from 'react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { NotificationList } from '@/components/notification/notification-list';
import { NotificationDetail } from '@/components/notification/notification-detail';
import { Notification } from '@/types';
import { useNotifications } from '@/hooks/use-notifications';
import { ArrowLeft } from 'lucide-react';
import { Head } from '@inertiajs/react';

export default function StudentNotificationsPage() {
  const [selectedNotification, setSelectedNotification] = useState<Notification | null>(null);
  const { markAsRead, deleteNotification } = useNotifications();

  const handleNotificationClick = (notification: Notification) => {
    setSelectedNotification(notification);
  };

  const handleMarkAsRead = (notification: Notification) => {
    markAsRead(notification.id);
  };

  const handleDeleteNotification = (notification: Notification) => {
    deleteNotification(notification.id);
    if (selectedNotification?.id === notification.id) {
      setSelectedNotification(null);
    }
  };

  const handleBack = () => {
    setSelectedNotification(null);
  };

  return (
    <StudentLayout pageTitle="Notifications">
      <Head title="Notifications" />
      <div className="container py-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">Notifications</h1>
          {selectedNotification && (
            <Button variant="outline" onClick={handleBack}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back to all notifications
            </Button>
          )}
        </div>

        <div className="grid grid-cols-1 gap-6">
          {selectedNotification ? (
            <NotificationDetail 
              notification={selectedNotification}
              onMarkAsRead={handleMarkAsRead}
              onDelete={handleDeleteNotification}
              onBack={handleBack}
            />
          ) : (
            <NotificationList 
              onNotificationClick={handleNotificationClick}
            />
          )}
        </div>
      </div>
    </StudentLayout>
  );
} 