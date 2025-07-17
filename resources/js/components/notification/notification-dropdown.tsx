import React from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useNotifications } from '@/hooks/use-notifications';
import { BellNotificationIcon } from '@/components/icons/bell-notification-icon';
import { formatDistanceToNow } from 'date-fns';
import { Check, Trash2, Bell, BellOff } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

interface NotificationDropdownProps {
  className?: string;
  iconSize?: number;
}

export function NotificationDropdown({ className, iconSize = 24 }: NotificationDropdownProps) {
  const {
    notifications,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotifications();

  const handleMarkAsRead = (notificationId: string) => {
    markAsRead(notificationId);
  };

  const handleDeleteNotification = (
    e: React.MouseEvent,
    notificationId: string
  ) => {
    e.preventDefault();
    e.stopPropagation();
    deleteNotification(notificationId);
  };

  const getNotificationIcon = (type: string, level?: string) => {
    // You can customize this based on notification types in your system
    switch (type) {
      case 'App\\Notifications\\MessageNotification':
        return <Bell className="h-4 w-4 text-blue-500" />;
      case 'App\\Notifications\\PaymentNotification':
        return <Bell className="h-4 w-4 text-green-500" />;
      case 'App\\Notifications\\SessionRequestNotification':
        return <Bell className="h-4 w-4 text-amber-500" />;
      default:
        return <Bell className="h-4 w-4 text-gray-500" />;
    }
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className={cn("relative", className)}>
          <BellNotificationIcon style={{ width: iconSize, height: iconSize }} />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-destructive text-[10px] font-medium text-white">
              {unreadCount > 99 ? '99+' : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex items-center justify-between">
          <span>Notifications</span>
          {unreadCount > 0 && (
            <Button
              variant="ghost"
              size="sm"
              className="h-8 text-xs"
              onClick={() => markAllAsRead()}
            >
              <Check className="mr-1 h-3 w-3" />
              Mark all as read
            </Button>
          )}
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        {isLoading && (
          <div className="flex justify-center py-4">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
          </div>
        )}
        
        {!isLoading && notifications.length === 0 && (
          <div className="flex flex-col items-center justify-center py-6 px-4 text-center">
            <BellOff className="mb-2 h-8 w-8 text-muted-foreground" />
            <p className="text-sm font-medium">No notifications</p>
            <p className="text-xs text-muted-foreground">
              You're all caught up! We'll notify you when something new arrives.
            </p>
          </div>
        )}
        
        {!isLoading && notifications.length > 0 && (
          <ScrollArea className="h-[300px]">
            <DropdownMenuGroup>
              {notifications.map((notification) => {
                const isUnread = !notification.read_at;
                return (
                  <DropdownMenuItem
                    key={notification.id}
                    className={cn(
                      "flex items-start gap-2 p-3 cursor-pointer",
                      isUnread && "bg-muted/50"
                    )}
                    onClick={() => {
                      if (isUnread) {
                        handleMarkAsRead(notification.id);
                      }
                      
                      // If there's an action URL, Inertia will handle navigation
                      if (notification.data.action_url) {
                        // Navigation will be handled by Link component
                      }
                    }}
                  >
                    <div className="mt-1">
                      {getNotificationIcon(notification.type, notification.level)}
                    </div>
                    
                    <div className="flex-1 space-y-1">
                      {notification.data.action_url ? (
                        <Link href={notification.data.action_url} className="block">
                          <p className="text-sm font-medium leading-none">
                            {notification.data.title}
                          </p>
                          <p className="text-xs text-muted-foreground line-clamp-2 mt-1">
                            {notification.data.message}
                          </p>
                        </Link>
                      ) : (
                        <>
                          <p className="text-sm font-medium leading-none">
                            {notification.data.title}
                          </p>
                          <p className="text-xs text-muted-foreground line-clamp-2 mt-1">
                            {notification.data.message}
                          </p>
                        </>
                      )}
                      
                      <div className="flex items-center justify-between mt-1">
                        <p className="text-[10px] text-muted-foreground">
                          {formatDistanceToNow(new Date(notification.created_at), {
                            addSuffix: true,
                          })}
                        </p>
                        
                        <div className="flex items-center gap-1">
                          {isUnread && (
                            <Button
                              variant="ghost"
                              size="icon"
                              className="h-6 w-6"
                              onClick={(e) => {
                                e.stopPropagation();
                                e.preventDefault();
                                handleMarkAsRead(notification.id);
                              }}
                            >
                              <Check className="h-3 w-3" />
                            </Button>
                          )}
                          
                          <Button
                            variant="ghost"
                            size="icon"
                            className="h-6 w-6"
                            onClick={(e) => handleDeleteNotification(e, notification.id)}
                          >
                            <Trash2 className="h-3 w-3" />
                          </Button>
                        </div>
                      </div>
                    </div>
                  </DropdownMenuItem>
                );
              })}
            </DropdownMenuGroup>
          </ScrollArea>
        )}
        
        <DropdownMenuSeparator />
        <Link
          href="/notifications"
          className="block w-full rounded-sm px-3 py-2 text-center text-xs font-medium hover:bg-muted"
        >
          View all notifications
        </Link>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export default NotificationDropdown; 