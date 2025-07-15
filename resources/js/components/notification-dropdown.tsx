import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Link } from '@inertiajs/react';
import { Bell, CheckCircle, ChevronLeft, ChevronRight } from 'lucide-react';
import axios from 'axios';
import { PaymentIcon } from '@/components/icons/payment-icon';
import { NotificationIcon } from '@/components/icons/notification-icon';
import { MessageIcon } from '@/components/icons/message-icon';
import { AlertIcon } from '@/components/icons/alert-icon';

interface Notification {
  id: number;
  title: string;
  body: string;
  created_at: string;
  type: string;
  status: string;
  is_read: boolean;
}

interface PaginationInfo {
  total: number;
  per_page: number;
  current_page: number;
  last_page: number;
  from: number;
  to: number;
}

interface NotificationDropdownProps {
  userRole: 'admin' | 'teacher' | 'student' | 'guardian';
  viewAllLink?: string;
  notificationDetailBaseUrl?: string;
  className?: string;
  triggerClassName?: string;
}

export default function NotificationDropdown({
  userRole = 'teacher',
  viewAllLink = '/notifications',
  notificationDetailBaseUrl = '/notifications',
  className = '',
  triggerClassName = ''
}: NotificationDropdownProps) {
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [pagination, setPagination] = useState<PaginationInfo | null>(null);
  const [loading, setLoading] = useState<boolean>(false);
  const [isOpen, setIsOpen] = useState<boolean>(false);
  const [currentPage, setCurrentPage] = useState<number>(1);

  const fetchNotifications = async (page = 1) => {
    setLoading(true);
    try {
      const response = await axios.get('/api/user-notifications', {
        params: { page, per_page: 5, role: userRole }
      });
      
      setNotifications(response.data.notifications);
      setUnreadCount(response.data.unread_count);
      setPagination(response.data.pagination);
    } catch (error) {
      console.error('Error fetching notifications:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isOpen) {
      fetchNotifications(currentPage);
    }
  }, [isOpen, currentPage]);

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
    } catch (error) {
      console.error('Error marking all notifications as read:', error);
    }
  };

  const handlePrevPage = () => {
    if (pagination && currentPage > 1) {
      setCurrentPage(prev => prev - 1);
    }
  };

  const handleNextPage = () => {
    if (pagination && currentPage < pagination.last_page) {
      setCurrentPage(prev => prev + 1);
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
    <DropdownMenu open={isOpen} onOpenChange={setIsOpen}>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className={`relative ${triggerClassName}`}>
          <Bell className="h-5 w-5" />
          {unreadCount > 0 && (
            <span className="absolute -right-1 -top-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white">
              {unreadCount > 9 ? '9+' : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className={`w-80 ${className}`}>
        <div className="flex items-center justify-between p-4 border-b">
          <h3 className="font-medium">Notifications</h3>
          {unreadCount > 0 && (
            <Button 
              variant="ghost" 
              size="sm" 
              onClick={handleMarkAllAsRead}
              className="text-xs h-8"
            >
              <CheckCircle className="mr-1 h-3 w-3" />
              Mark all as read
            </Button>
          )}
        </div>
        
        <ScrollArea className="h-[300px]">
          {loading ? (
            <div className="p-4 space-y-4">
              {[1, 2, 3].map((i) => (
                <div key={i} className="flex flex-col space-y-2">
                  <Skeleton className="h-4 w-3/4" />
                  <Skeleton className="h-3 w-full" />
                  <Skeleton className="h-3 w-1/2" />
                </div>
              ))}
            </div>
          ) : notifications.length > 0 ? (
            <div>
              {notifications.map((notification) => (
                <div 
                  key={notification.id}
                  className={`p-3 border-b last:border-0 hover:bg-muted/50 ${!notification.is_read ? 'bg-muted/30' : ''}`}
                >
                  <div className="flex justify-between items-start mb-1">
                    <div className="flex items-center gap-2">
                      <div className="flex-shrink-0">
                        {getNotificationIcon(notification.type)}
                      </div>
                      <Link 
                        href={`${notificationDetailBaseUrl}/${notification.id}`}
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
            <div className="flex flex-col items-center justify-center h-full p-4">
              <p className="text-sm text-muted-foreground">No notifications</p>
            </div>
          )}
        </ScrollArea>
        
        {pagination && pagination.last_page > 1 && (
          <div className="flex items-center justify-between p-2 border-t">
            <Button
              variant="ghost"
              size="sm"
              onClick={handlePrevPage}
              disabled={currentPage === 1 || loading}
              className="h-8 px-2"
            >
              <ChevronLeft className="h-4 w-4" />
              Prev
            </Button>
            <span className="text-xs text-muted-foreground">
              {pagination.current_page} of {pagination.last_page}
            </span>
            <Button
              variant="ghost"
              size="sm"
              onClick={handleNextPage}
              disabled={currentPage === pagination.last_page || loading}
              className="h-8 px-2"
            >
              Next
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>
        )}
        
        <div className="p-2 border-t">
          <Link 
            href={viewAllLink} 
            className="block w-full text-center text-sm py-2 bg-muted/50 hover:bg-muted rounded-md"
          >
            View all notifications
          </Link>
        </div>
      </DropdownMenuContent>
    </DropdownMenu>
  );
} 