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
  showToastsOnInitialFetch?: boolean;
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
  showToastsOnInitialFetch = true,
}: UseNotificationsOptions = {}): UseNotificationsReturn => {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<Error | null>(null);
  const [initialFetchDone, setInitialFetchDone] = useState<boolean>(false);
  const { auth } = usePage().props as any;

  const fetchNotifications = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await axios.get('/api/user/notifications');
      const fetchedNotifications = response.data.data;
      
      // Show toast notifications for unread notifications on initial fetch if enabled
      if (showToasts && showToastsOnInitialFetch && !initialFetchDone) {
        // Only show toasts for the most recent 3 unread notifications to avoid overwhelming the user
        const unreadNotifications = fetchedNotifications
          .filter((notification: Notification) => !notification.read_at)
          .slice(0, 3);
          
        unreadNotifications.forEach((notification: Notification) => {
          showNotificationToast(notification);
        });
        
        setInitialFetchDone(true);
      }
      
      setNotifications(fetchedNotifications);
      
      // Get unread count
      const countResponse = await axios.get('/api/user/notifications/count');
      setUnreadCount(countResponse.data.count);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to fetch notifications'));
    } finally {
      setIsLoading(false);
    }
  }, [showToasts, showToastsOnInitialFetch, initialFetchDone]);

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
      case 'App\\Notifications\\BookingNotification':
        return '📚';
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
    
    // Special handling for verification call notifications
    if (notification.type === 'App\\Notifications\\VerificationCallScheduledNotification') {
      const verificationData = notification.data as any;
      const scheduledTime = verificationData.scheduled_at_human || 'the scheduled time';
      const platform = verificationData.platform_label || 'the video platform';
      const meetingLink = verificationData.meeting_link;
      
      // Enhanced message for verification calls
      const enhancedMessage = `${message}\n\n📅 Scheduled: ${scheduledTime}\n📹 Platform: ${platform}${meetingLink ? '\n🔗 Meeting link available' : ''}\n\n📧 Please check your email for detailed verification instructions.`;
      
      // Helper to handle action URL clicks
      const handleActionClick = () => {
        if (actionUrl) {
          if (actionUrl.startsWith('/')) {
            window.location.href = actionUrl;
          } else {
            window.open(actionUrl, '_blank');
          }
        }
      };
      
      // Show special verification call toast
      toast.success(`📞 ${title}`, {
        description: enhancedMessage,
        duration: 10000, // Show for 10 seconds
        action: actionText ? {
          label: actionText,
          onClick: handleActionClick,
        } : {
          label: 'Check Email',
          onClick: () => {
            // Open email client or show email reminder
            toast.info('📧 Check your email for verification details and meeting link!');
          }
        },
      });
      return;
    }
    
    // Helper to handle action URL clicks
    const handleActionClick = () => {
      if (!actionUrl) return;
      
      // If it's an internal URL (starts with /) use window.location
      // Otherwise open in a new tab for external URLs
      if (actionUrl.startsWith('/')) {
        window.location.href = actionUrl;
      } else {
        window.open(actionUrl, '_blank');
      }
    };
    
    // Show toast based on notification level
    switch (level) {
      case 'success':
        toast.success(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: handleActionClick,
          } : undefined,
        });
        break;
      case 'error':
        toast.error(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: handleActionClick,
          } : undefined,
        });
        break;
      case 'warning':
        toast.warning(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: handleActionClick,
          } : undefined,
        });
        break;
      default:
        toast.info(`${icon} ${title}`, {
          description: message,
          action: actionText ? {
            label: actionText,
            onClick: handleActionClick,
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
        
        // Listen for notification events - Laravel broadcasts with the notification class name
        channel.listen('.App\\Notifications\\VerificationCallScheduledNotification', (data: any) => {
          
          // Format the notification properly
          const notification: Notification = {
            id: data.id || '',
            type: 'App\\Notifications\\VerificationCallScheduledNotification',
            notifiable_type: 'App\\Models\\User',
            notifiable_id: userId,
            data: data.data || {},
            read_at: null,
            created_at: data.created_at || new Date().toISOString(),
            level: data.level || 'info'
          };
          
          // Check if notification already exists in the list to prevent duplicates
          setNotifications(prev => {
            // Check if this notification already exists
            const exists = prev.some(n => n.id === notification.id);
            if (exists) {
              return prev;
            }
            
            // Only increment unread count if notification is new
            setUnreadCount(prev => prev + 1);
            
            // Show toast notification only if it's new
            showNotificationToast(notification);
            
            // Add the new notification to the list
            return [notification, ...prev];
          });
        });
        
        // Listen for general notification events (for other notification types)
        channel.listen('.notification', (data: any) => {
          
          // Format the notification properly
          const notification: Notification = {
            id: data.id || '',
            type: data.type || '',
            notifiable_type: 'App\\Models\\User',
            notifiable_id: userId,
            data: data.data || {},
            read_at: null,
            created_at: data.created_at || new Date().toISOString(),
            level: data.level || 'info'
          };
          
          // Check if notification already exists in the list to prevent duplicates
          setNotifications(prev => {
            // Check if this notification already exists
            const exists = prev.some(n => n.id === notification.id);
            if (exists) {
              return prev;
            }
            
            // Only increment unread count if notification is new
            setUnreadCount(prev => prev + 1);
            
            // Show toast notification only if it's new
            showNotificationToast(notification);
            
            // Add the new notification to the list
            return [notification, ...prev];
          });
        });
        
        // Also listen for UserRegistered event (which might contain a welcome notification)
        channel.listen('.App\\Events\\UserRegistered', (data: any) => {
          
          // Fetch notifications to get the welcome notification
          // Use a short delay to ensure the notification is created in the database
          setTimeout(() => {
            fetchNotifications();
          }, 1000);
        });
        
        // Also listen for UserLoggedIn event (which might contain a login notification)
        channel.listen('.App\\Events\\UserLoggedIn', (data: any) => {
          
          // Fetch notifications to get the login notification
          // Use a short delay to ensure the notification is created in the database
          setTimeout(() => {
            fetchNotifications();
          }, 1000);
        });
        
        // Also listen for NotificationCreated event
        channel.listen('.App\\Events\\NotificationCreated', (data: any) => {
          
          if (data.notification) {
            // Format the notification properly
            const notification: Notification = {
              id: data.notification.id || '',
              type: data.notification.type || '',
              notifiable_type: 'App\\Models\\User',
              notifiable_id: userId,
              data: data.notification.data || {},
              read_at: null,
              created_at: data.notification.created_at || new Date().toISOString(),
              level: data.notification.level || 'info'
            };
            
            // Check if notification already exists in the list to prevent duplicates
            setNotifications(prev => {
              // Check if this notification already exists
              const exists = prev.some(n => n.id === notification.id);
              if (exists) {
                return prev;
              }
              
              // Only increment unread count if notification is new
              setUnreadCount(prev => prev + 1);
              
              // Show toast notification only if it's new
              showNotificationToast(notification);
              
              // Add the new notification to the list
              return [notification, ...prev];
            });
          } else {
            // If we don't have the full notification, fetch all notifications
            // Use a short delay to ensure the notification is created in the database
            setTimeout(() => {
              fetchNotifications();
            }, 1000);
          }
        });
        
        return () => {
          channel.stopListening('.App\\Notifications\\VerificationCallScheduledNotification');
          channel.stopListening('.notification');
          channel.stopListening('.App\\Events\\UserRegistered');
          channel.stopListening('.App\\Events\\UserLoggedIn');
          channel.stopListening('.App\\Events\\NotificationCreated');
        };
      }
    } else if (pollingInterval && pollingInterval > 0) {
      // Fall back to polling if WebSockets are not available or disabled
      const intervalId = setInterval(fetchNotifications, pollingInterval);
      return () => clearInterval(intervalId);
    }
  }, [fetchNotifications, pollingInterval, useWebSockets, auth?.user?.id, showToasts]);

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