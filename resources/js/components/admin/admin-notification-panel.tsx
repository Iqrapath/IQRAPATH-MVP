import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { Bell, CheckCircle, RefreshCw } from 'lucide-react';
import axios from 'axios';
import { PaymentIcon } from '@/components/icons/payment-icon';
import { NotificationIcon } from '@/components/icons/notification-icon';
import { MessageIcon } from '@/components/icons/message-icon';
import { AlertIcon } from '@/components/icons/alert-icon';
import { toast } from 'sonner';

interface Notification {
  id: number;
  title: string;
  body: string;
  created_at: string;
  type: string;
  status: string;
  is_read: boolean;
}

interface AdminNotificationPanelProps {
  className?: string;
  limit?: number;
}

export default function AdminNotificationPanel({
  className = '',
  limit = 5
}: AdminNotificationPanelProps) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [loading, setLoading] = useState<boolean>(false);
  const [refreshing, setRefreshing] = useState<boolean>(false);

  const fetchNotifications = async () => {
    setLoading(true);
    try {
      const response = await axios.get('/api/admin/notifications', {
        params: { limit }
      });
      
      setNotifications(response.data.notifications);
      setUnreadCount(response.data.unread_count);
    } catch (error) {
      console.error('Error fetching admin notifications:', error);
      // Try the alternative endpoint if the first one fails
      try {
        const response = await axios.get('/admin/notifications', {
          params: { limit }
        });
        
        setNotifications(response.data.notifications);
        setUnreadCount(response.data.unread_count);
      } catch (secondError) {
        console.error('Error fetching admin notifications from alternative endpoint:', secondError);
      }
    } finally {
      setLoading(false);
    }
  };

  const refreshNotifications = async () => {
    setRefreshing(true);
    try {
      await fetchNotifications();
      toast.success('Notifications refreshed');
    } catch (error) {
      console.error('Error refreshing notifications:', error);
    } finally {
      setRefreshing(false);
    }
  };

  useEffect(() => {
    fetchNotifications();
    
    // Set up real-time notification listeners
    const userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    
    if (userId && window.Echo) {
      // Listen for new notifications
      const channel = window.Echo.private(`notifications.${userId}`);
      
      channel.listen('.notification.received', (data: { notification: Notification }) => {
        console.log('New admin notification received:', data);
        
        // Add the new notification to the top of the list
        setNotifications(prev => [data.notification, ...prev.slice(0, limit - 1)]);
        
        // Increment unread count
        setUnreadCount(prev => prev + 1);
        
        // Show toast notification
        toast.info(data.notification.title, {
          description: data.notification.body,
          duration: 5000,
        });
      });
      
      // Clean up listeners when component unmounts
      return () => {
        channel.stopListening('.notification.received');
        if (window.Echo) {
          window.Echo.leave(`notifications.${userId}`);
        }
      };
    }
  }, [limit]);

  const handleMarkAsRead = async (id: number) => {
    try {
      await axios.post(`/api/notifications/${id}/read`);
      
      // Update the notification in the local state
      setNotifications(prev => 
        prev.map(notification => 
          notification.id === id 
            ? { ...notification, is_read: true } 
            : notification
        )
      );
      
      // Decrement unread count
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Error marking notification as read:', error);
    }
  };

  const handleMarkAllAsRead = async () => {
    try {
      await axios.post('/api/notifications/read-all');
      
      // Update all notifications in the local state
      setNotifications(prev => 
        prev.map(notification => ({ ...notification, is_read: true }))
      );
      
      // Reset unread count
      setUnreadCount(0);
      
      toast.success('All notifications marked as read');
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  };

  const getNotificationIcon = (type: string) => {
    switch (type.toLowerCase()) {
      case 'payment':
        return <PaymentIcon className="h-5 w-5 text-green-500" />;
      case 'session':
      case 'request':
        return <NotificationIcon className="h-5 w-5 text-blue-500" />;
      case 'message':
        return <MessageIcon className="h-5 w-5 text-blue-500" />;
      case 'alert':
      case 'admin':
        return <AlertIcon className="h-5 w-5 text-amber-500" />;
      default:
        return <NotificationIcon className="h-5 w-5 text-gray-500" />;
    }
  };

  const getNotificationTypeColor = (type: string) => {
    switch (type.toLowerCase()) {
      case 'payment':
      case 'withdrawal':
        return 'bg-green-100 text-green-800';
      case 'session':
      case 'request':
        return 'bg-blue-100 text-blue-800';
      case 'message':
        return 'bg-sky-100 text-sky-800';
      case 'alert':
      case 'admin':
        return 'bg-amber-100 text-amber-800';
      case 'system':
        return 'bg-purple-100 text-purple-800';
      default:
        return 'bg-gray-100 text-gray-800';
    }
  };

  return (
    <Card className={className}>
      <CardHeader className="pb-3">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Bell className="h-5 w-5" />
            <CardTitle>Recent Notifications</CardTitle>
            {unreadCount > 0 && (
              <Badge variant="destructive" className="ml-2">
                {unreadCount} new
              </Badge>
            )}
          </div>
          <div className="flex items-center gap-2">
            <Button 
              variant="ghost" 
              size="sm" 
              onClick={refreshNotifications}
              disabled={refreshing}
              className="h-8 w-8 p-0"
            >
              <RefreshCw className={`h-4 w-4 ${refreshing ? 'animate-spin' : ''}`} />
              <span className="sr-only">Refresh</span>
            </Button>
            {unreadCount > 0 && (
              <Button 
                variant="outline" 
                size="sm" 
                onClick={handleMarkAllAsRead}
                className="text-xs h-8"
              >
                <CheckCircle className="mr-1 h-3 w-3" />
                Mark all as read
              </Button>
            )}
          </div>
        </div>
        <CardDescription>
          System notifications and alerts
        </CardDescription>
      </CardHeader>
      <CardContent className="p-0">
        <ScrollArea className="h-[400px] px-6">
          {loading ? (
            <div className="space-y-4 py-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="flex flex-col space-y-2">
                  <Skeleton className="h-4 w-3/4" />
                  <Skeleton className="h-3 w-full" />
                  <Skeleton className="h-3 w-1/2" />
                </div>
              ))}
            </div>
          ) : notifications.length > 0 ? (
            <div className="divide-y">
              {notifications.map((notification) => (
                <div 
                  key={notification.id}
                  className={`py-4 ${!notification.is_read ? 'bg-muted/30' : ''}`}
                >
                  <div className="flex justify-between items-start mb-1">
                    <div className="flex items-center gap-2">
                      <div className="flex-shrink-0">
                        {getNotificationIcon(notification.type)}
                      </div>
                      <Link 
                        href={`/admin/notification/${notification.id}`}
                        className="font-medium text-sm line-clamp-1"
                        onClick={() => !notification.is_read && handleMarkAsRead(notification.id)}
                      >
                        {notification.title}
                      </Link>
                    </div>
                    <Badge 
                      variant="outline" 
                      className={`ml-2 text-[10px] py-0 h-5 ${getNotificationTypeColor(notification.type)}`}
                    >
                      {notification.type}
                    </Badge>
                  </div>
                  <p className="text-xs text-muted-foreground line-clamp-2 mb-1 ml-7">
                    {notification.body}
                  </p>
                  <div className="flex justify-between items-center ml-7">
                    <span className="text-[10px] text-muted-foreground">
                      {notification.created_at}
                    </span>
                    {!notification.is_read && (
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        onClick={() => handleMarkAsRead(notification.id)}
                        className="h-6 text-[10px] py-0 px-2"
                      >
                        Mark as read
                      </Button>
                    )}
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="py-8 text-center text-muted-foreground">
              No notifications to display
            </div>
          )}
        </ScrollArea>
        <div className="p-4 border-t">
          <Link href="/admin/notification">
            <Button variant="outline" className="w-full">
              View All Notifications
            </Button>
          </Link>
        </div>
      </CardContent>
    </Card>
  );
} 