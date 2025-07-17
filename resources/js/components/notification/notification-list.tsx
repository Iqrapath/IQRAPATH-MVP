import React, { useState } from 'react';
import { useNotifications } from '@/hooks/use-notifications';
import { formatDistanceToNow } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Check, Trash2, Bell, Search } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Input } from '@/components/ui/input';
import { Notification } from '@/types';

interface NotificationListProps {
  showControls?: boolean;
  maxHeight?: string;
  className?: string;
  onNotificationClick?: (notification: Notification) => void;
}

export function NotificationList({ 
  showControls = true, 
  maxHeight = "600px",
  className,
  onNotificationClick
}: NotificationListProps) {
  const {
    notifications,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotifications();
  
  const [activeTab, setActiveTab] = useState('all');
  const [searchQuery, setSearchQuery] = useState('');
  
  // Filter notifications based on tab and search query
  const filteredNotifications = notifications
    .filter(notification => {
      if (activeTab === 'all') return true;
      if (activeTab === 'unread') return !notification.read_at;
      if (activeTab === 'read') return !!notification.read_at;
      return true;
    })
    .filter(notification => {
      if (!searchQuery) return true;
      
      const searchLower = searchQuery.toLowerCase();
      return (
        notification.data.title.toLowerCase().includes(searchLower) ||
        notification.data.message.toLowerCase().includes(searchLower)
      );
    });

  const handleMarkAsRead = (e: React.MouseEvent, notification: Notification) => {
    e.preventDefault();
    e.stopPropagation();
    markAsRead(notification.id);
  };

  const handleDeleteNotification = (e: React.MouseEvent, notification: Notification) => {
    e.preventDefault();
    e.stopPropagation();
    deleteNotification(notification.id);
  };

  const getNotificationIcon = (type: string, level?: string) => {
    // You can customize this based on notification types in your system
    switch (type) {
      case 'App\\Notifications\\MessageNotification':
        return <Bell className="h-5 w-5 text-blue-500" />;
      case 'App\\Notifications\\PaymentNotification':
        return <Bell className="h-5 w-5 text-green-500" />;
      case 'App\\Notifications\\SessionRequestNotification':
        return <Bell className="h-5 w-5 text-amber-500" />;
      default:
        return <Bell className="h-5 w-5 text-gray-500" />;
    }
  };

  return (
    <div className={cn("space-y-4", className)}>
      {showControls && (
        <>
          <div className="flex items-center justify-between">
            <Tabs defaultValue="all" value={activeTab} onValueChange={setActiveTab}>
              <TabsList>
                <TabsTrigger value="all">
                  All
                  <span className="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                    {notifications.length}
                  </span>
                </TabsTrigger>
                <TabsTrigger value="unread">
                  Unread
                  <span className="ml-2 rounded-full bg-primary px-2 py-0.5 text-xs font-medium text-white">
                    {unreadCount}
                  </span>
                </TabsTrigger>
                <TabsTrigger value="read">
                  Read
                  <span className="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs font-medium">
                    {notifications.length - unreadCount}
                  </span>
                </TabsTrigger>
              </TabsList>
            </Tabs>

            {unreadCount > 0 && (
              <Button variant="outline" onClick={() => markAllAsRead()}>
                <Check className="mr-2 h-4 w-4" />
                Mark all as read
              </Button>
            )}
          </div>

          <div className="relative">
            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input
              placeholder="Search notifications..."
              className="pl-10"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
            />
          </div>
        </>
      )}

      {isLoading ? (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
        </div>
      ) : filteredNotifications.length === 0 ? (
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <Bell className="mb-3 h-12 w-12 text-muted-foreground" />
          <h3 className="text-lg font-medium">No notifications</h3>
          <p className="text-sm text-muted-foreground mt-1">
            {searchQuery 
              ? "No notifications match your search" 
              : activeTab === 'all' 
                ? "You don't have any notifications yet" 
                : activeTab === 'unread' 
                  ? "You've read all your notifications" 
                  : "You haven't read any notifications yet"}
          </p>
          {searchQuery && (
            <Button variant="outline" className="mt-4" onClick={() => setSearchQuery('')}>
              Clear search
            </Button>
          )}
        </div>
      ) : (
        <div className={cn("space-y-4 overflow-auto", maxHeight && `max-h-[${maxHeight}]`)}>
          {filteredNotifications.map((notification) => {
            const isUnread = !notification.read_at;
            return (
              <Card 
                key={notification.id} 
                className={cn(isUnread && "bg-muted/30")}
                onClick={() => onNotificationClick && onNotificationClick(notification)}
              >
                <CardHeader className="pb-2">
                  <div className="flex items-start justify-between">
                    <div className="flex items-center gap-2">
                      <div className="rounded-full p-1 bg-muted">
                        {getNotificationIcon(notification.type, notification.level)}
                      </div>
                      <CardTitle className="text-base">{notification.data.title}</CardTitle>
                    </div>
                    <div className="flex items-center gap-1">
                      {isUnread && (
                        <Button
                          variant="ghost"
                          size="sm"
                          className="h-8 w-8 p-0"
                          onClick={(e) => handleMarkAsRead(e, notification)}
                        >
                          <Check className="h-4 w-4" />
                        </Button>
                      )}
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-8 w-8 p-0 text-destructive"
                        onClick={(e) => handleDeleteNotification(e, notification)}
                      >
                        <Trash2 className="h-4 w-4" />
                      </Button>
                    </div>
                  </div>
                  <CardDescription className="text-xs">
                    {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <p className="text-sm">{notification.data.message}</p>
                </CardContent>
                {notification.data.action_url && notification.data.action_text && (
                  <CardFooter className="pt-0">
                    <Link href={notification.data.action_url}>
                      <Button variant="outline" size="sm">
                        {notification.data.action_text}
                      </Button>
                    </Link>
                  </CardFooter>
                )}
              </Card>
            );
          })}
        </div>
      )}
    </div>
  );
}

export default NotificationList; 