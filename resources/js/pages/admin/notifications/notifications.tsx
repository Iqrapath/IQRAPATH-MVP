import React, { useState } from 'react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { NotificationList } from '@/components/notification/notification-list';
import { NotificationDetail } from '@/components/notification/notification-detail';
import { NotificationForm } from '@/components/notification/notification-form';
import { Notification } from '@/types';
import { useNotifications } from '@/hooks/use-notifications';
import { Plus, ArrowLeft } from 'lucide-react';

export default function AdminNotificationsPage() {
  const [selectedNotification, setSelectedNotification] = useState<Notification | null>(null);
  const [isCreating, setIsCreating] = useState(false);
  const [isEditing, setIsEditing] = useState(false);
  const { markAsRead, deleteNotification, fetchNotifications } = useNotifications();

  const handleNotificationClick = (notification: Notification) => {
    setSelectedNotification(notification);
    setIsCreating(false);
    setIsEditing(false);
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

  const handleCreateClick = () => {
    setIsCreating(true);
    setSelectedNotification(null);
    setIsEditing(false);
  };

  const handleEditClick = () => {
    setIsEditing(true);
  };

  const handleFormSuccess = () => {
    fetchNotifications();
    setIsCreating(false);
    setIsEditing(false);
    setSelectedNotification(null);
  };

  const handleBack = () => {
    if (isEditing) {
      setIsEditing(false);
    } else {
      setSelectedNotification(null);
    }
  };

  return (
    <AdminLayout pageTitle="Notifications">
      <div className="container py-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">Notifications</h1>
          {!isCreating && !selectedNotification && (
            <Button onClick={handleCreateClick}>
              <Plus className="mr-2 h-4 w-4" />
              Create Notification
            </Button>
          )}
          {selectedNotification && !isEditing && (
            <Button variant="outline" onClick={handleEditClick}>
              Edit Notification
            </Button>
          )}
          {(isCreating || isEditing || selectedNotification) && (
            <Button variant="outline" onClick={handleBack}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Button>
          )}
        </div>

        <div className="grid grid-cols-1 gap-6">
          {isCreating ? (
            <NotificationForm 
              onSuccess={handleFormSuccess}
              onCancel={() => setIsCreating(false)}
            />
          ) : isEditing && selectedNotification ? (
            <NotificationForm 
              notification={selectedNotification}
              onSuccess={handleFormSuccess}
              onCancel={() => setIsEditing(false)}
            />
          ) : selectedNotification ? (
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
    </AdminLayout>
  );
} 