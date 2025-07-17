import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Notification } from '@/types';

// Configure axios to include CSRF token and credentials
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

interface UseNotificationsOptions {
  pollingInterval?: number;
  initialFetch?: boolean;
}

interface UseNotificationsReturn {
  notifications: Notification[];
  unreadCount: number;
  isLoading: boolean;
  error: Error | null;
  fetchNotifications: () => Promise<void>;
  markAsRead: (notificationId: string) => Promise<void>;
  markAllAsRead: () => Promise<void>;
  deleteNotification: (notificationId: string) => Promise<void>;
}

export const useNotifications = ({
  pollingInterval = 30000, // 30 seconds by default
  initialFetch = true,
}: UseNotificationsOptions = {}): UseNotificationsReturn => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<Error | null>(null);

  const fetchNotifications = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await axios.get('/api/user/notifications');
      setNotifications(response.data.data);
      
      // Get unread count
      const countResponse = await axios.get('/api/user/notifications/count');
      setUnreadCount(countResponse.data.count);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to fetch notifications'));
    } finally {
      setIsLoading(false);
    }
  }, []);

  const markAsRead = useCallback(async (notificationId: string) => {
    try {
      await axios.post(`/api/notifications/${notificationId}/read`);
      
      // Update local state
      setNotifications(prev => 
        prev.map(notification => 
          notification.id === notificationId 
            ? { ...notification, read_at: new Date().toISOString() } 
            : notification
        )
      );
      
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to mark notification as read'));
    }
  }, []);

  const markAllAsRead = useCallback(async () => {
    try {
      await axios.post('/api/notifications/read-all');
      
      // Update local state
      setNotifications(prev => 
        prev.map(notification => ({ 
          ...notification, 
          read_at: notification.read_at || new Date().toISOString() 
        }))
      );
      
      setUnreadCount(0);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to mark all notifications as read'));
    }
  }, []);

  const deleteNotification = useCallback(async (notificationId: string) => {
    try {
      await axios.delete(`/api/notifications/${notificationId}`);
      
      // Update local state
      const deletedNotification = notifications.find(n => n.id === notificationId);
      setNotifications(prev => prev.filter(notification => notification.id !== notificationId));
      
      // Update unread count if the deleted notification was unread
      if (deletedNotification && !deletedNotification.read_at) {
        setUnreadCount(prev => Math.max(0, prev - 1));
      }
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to delete notification'));
    }
  }, [notifications]);

  // Initial fetch
  useEffect(() => {
    if (initialFetch) {
      fetchNotifications();
    }
  }, [fetchNotifications, initialFetch]);

  // Polling for new notifications
  useEffect(() => {
    if (pollingInterval > 0) {
      const intervalId = setInterval(fetchNotifications, pollingInterval);
      return () => clearInterval(intervalId);
    }
  }, [fetchNotifications, pollingInterval]);

  return {
    notifications,
    unreadCount,
    isLoading,
    error,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  };
}; 