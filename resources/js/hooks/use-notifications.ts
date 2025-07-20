import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { usePage } from '@inertiajs/react';
import { Notification } from '@/types';
import { toast } from 'sonner';

// Configure axios to include CSRF token and credentials
axios.defaults.withCredentials = true;
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

interface UseNotificationsOptions {
  pollingInterval?: number | null;
  initialFetch?: boolean;
  useWebSockets?: boolean;
  showToasts?: boolean;
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
  pollingInterval = null, // WebSockets by default, fall back to polling if specified
  initialFetch = true,
  useWebSockets = true,
  showToasts = true,
}: UseNotificationsOptions = {}): UseNotificationsReturn => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<Error | null>(null);
  const { auth } = usePage().props as any;

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

  // Helper function to determine notification icon based on type
  const getNotificationIcon = (type: string) => {
    // You can customize this based on notification types in your system
    switch (type) {
      case 'App\\Notifications\\MessageNotification':
        return '💬';
      case 'App\\Notifications\\PaymentNotification':
        return '💰';
      case 'App\\Notifications\\SessionRequestNotification':
        return '📅';
      default:
        return '🔔';
    }
  };

  // Helper function to show toast notification
  const showNotificationToast = (notification: Notification) => {
    if (!showToasts) return;
    
    const icon = getNotificationIcon(notification.type);
    const title = notification.data.title || 'New notification';
    const message = notification.data.message || '';
    const level = notification.level || 'info';
    const actionText = notification.data.action_text || '';
    const actionUrl = notification.data.action_url || '';
    
    // Show toast based on notification level
    switch (level) {
      case 'success':
        toast.success(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: () => window.location.href = actionUrl,
          } : undefined,
        });
        break;
      case 'error':
        toast.error(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: () => window.location.href = actionUrl,
          } : undefined,
        });
        break;
      case 'warning':
        toast.warning(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: () => window.location.href = actionUrl,
          } : undefined,
        });
        break;
      default:
        toast.info(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: () => window.location.href = actionUrl,
          } : undefined,
        });
        break;
    }
  };

  // Initial fetch
  useEffect(() => {
    if (initialFetch) {
      fetchNotifications();
    }
  }, [fetchNotifications, initialFetch]);

  // Set up WebSockets or polling
  useEffect(() => {
    if (useWebSockets && window.Echo) {
      // Get user ID from Inertia shared data
      const userId = auth?.user?.id;
      
      if (userId) {
        const channel = window.Echo.private(`user.${userId}`);
        
        // Listen for notification events
        channel.listen('.notification', (notification: Notification) => {
          console.log('Received notification event:', notification);
          
          // Add the new notification to the list
          setNotifications(prev => [notification, ...prev]);
          
          // Update unread count
          setUnreadCount(prev => prev + 1);
          
          // Show toast notification
          showNotificationToast(notification);
        });
        
        // Also listen for UserRegistered event (which might contain a welcome notification)
        channel.listen('.App\\Events\\UserRegistered', (data: any) => {
          console.log('Received UserRegistered event:', data);
          
          // Fetch notifications to get the welcome notification
          fetchNotifications();
        });
        
        // Also listen for NotificationCreated event
        channel.listen('.App\\Events\\NotificationCreated', (data: any) => {
          console.log('Received NotificationCreated event:', data);
          
          const notification = data.notification;
          if (notification) {
            // Add the new notification to the list
            setNotifications(prev => [notification, ...prev]);
            
            // Update unread count
            setUnreadCount(prev => prev + 1);
            
            // Show toast notification
            showNotificationToast(notification);
          } else {
            // If we don't have the full notification, fetch all notifications
            fetchNotifications();
          }
        });
        
        return () => {
          channel.stopListening('.notification');
          channel.stopListening('.App\\Events\\UserRegistered');
          channel.stopListening('.App\\Events\\NotificationCreated');
        };
      }
    } else if (pollingInterval && pollingInterval > 0) {
      // Fall back to polling if WebSockets are not available or disabled
      const intervalId = setInterval(fetchNotifications, pollingInterval);
      return () => clearInterval(intervalId);
    }
  }, [fetchNotifications, pollingInterval, useWebSockets, auth?.user?.id]);

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