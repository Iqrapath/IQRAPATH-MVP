import React, { useState } from 'react';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import { useNotifications } from '@/hooks/use-notifications';
import { formatDistanceToNow } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Check, Trash2, Bell } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';

export default function NotificationsPage() {
  const {
    notifications,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead,
    deleteNotification,
  } = useNotifications();
  
  const [activeTab, setActiveTab] = useState('all');
  
  const filteredNotifications = activeTab === 'all' 
    ? notifications 
    : activeTab === 'unread' 
      ? notifications.filter(n => !n.read_at) 
      : notifications.filter(n => n.read_at);

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
    <AppHeaderLayout breadcrumbs={[{ title: 'Notifications', href: '/notifications' }]}>
      <div className="container py-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">Notifications</h1>
          {unreadCount > 0 && (
            <Button variant="outline" onClick={() => markAllAsRead()}>
              <Check className="mr-2 h-4 w-4" />
              Mark all as read
            </Button>
          )}
        </div>

        <Tabs defaultValue="all" value={activeTab} onValueChange={setActiveTab}>
          <div className="flex items-center justify-between mb-4">
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
          </div>

          <TabsContent value="all" className="mt-0">
            {renderNotificationList(filteredNotifications)}
          </TabsContent>
          
          <TabsContent value="unread" className="mt-0">
            {renderNotificationList(filteredNotifications)}
          </TabsContent>
          
          <TabsContent value="read" className="mt-0">
            {renderNotificationList(filteredNotifications)}
          </TabsContent>
        </Tabs>
      </div>
    </AppHeaderLayout>
  );

  function renderNotificationList(notificationList: any[]) {
    if (isLoading) {
      return (
        <div className="flex justify-center py-12">
          <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
        </div>
      );
    }

    if (notificationList.length === 0) {
      return (
        <div className="flex flex-col items-center justify-center py-12 text-center">
          <Bell className="mb-3 h-12 w-12 text-muted-foreground" />
          <h3 className="text-lg font-medium">No notifications</h3>
          <p className="text-sm text-muted-foreground mt-1">
            {activeTab === 'all' 
              ? "You don't have any notifications yet" 
              : activeTab === 'unread' 
                ? "You've read all your notifications" 
                : "You haven't read any notifications yet"}
          </p>
        </div>
      );
    }

    return (
      <div className="space-y-4">
        {notificationList.map((notification) => {
          const isUnread = !notification.read_at;
          return (
            <Card key={notification.id} className={cn(isUnread && "bg-muted/30")}>
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
                        onClick={() => markAsRead(notification.id)}
                      >
                        <Check className="h-4 w-4" />
                      </Button>
                    )}
                    <Button
                      variant="ghost"
                      size="sm"
                      className="h-8 w-8 p-0 text-destructive"
                      onClick={() => deleteNotification(notification.id)}
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
    );
  }
} 