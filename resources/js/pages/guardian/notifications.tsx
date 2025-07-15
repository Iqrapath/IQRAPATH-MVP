import React, { useState, useEffect } from 'react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Head } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import GeneralNotifications from '../teacher/notification-component/general';
import PaymentNotifications from '../teacher/notification-component/payment';
import axios from 'axios';
import { toast } from 'sonner';

interface Notification {
  id: string;
  type: 'message' | 'alert' | 'system' | 'request' | 'payment';
  title: string;
  message?: string;
  time: string;
  sender?: string;
  hasAction?: boolean;
}

export default function GuardianNotifications() {
  const [activeTab, setActiveTab] = useState('general');
  const [notifications, setNotifications] = useState<Notification[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchNotifications();
  }, []);

  const fetchNotifications = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/notifications');
      
      // Transform API response to match our component's expected format
      const transformedNotifications = response.data.notifications.map((notification: any) => ({
        id: notification.id,
        type: mapNotificationType(notification.type),
        title: notification.title,
        message: notification.body,
        time: formatTimeAgo(notification.created_at),
        hasAction: notification.type === 'message' || notification.type === 'request',
        read: notification.read_at !== null
      }));
      
      setNotifications(transformedNotifications);
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
      toast.error('Failed to load notifications');
    } finally {
      setLoading(false);
    }
  };

  const mapNotificationType = (apiType: string): 'message' | 'alert' | 'system' | 'request' | 'payment' => {
    switch (apiType) {
      case 'message':
        return 'message';
      case 'session':
        return 'request';
      case 'payment':
        return 'payment';
      case 'system':
        return 'system';
      default:
        return 'alert';
    }
  };

  const formatTimeAgo = (dateString: string) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffInSeconds = Math.floor((now.getTime() - date.getTime()) / 1000);
    
    if (diffInSeconds < 60) {
      return 'just now';
    } else if (diffInSeconds < 3600) {
      const minutes = Math.floor(diffInSeconds / 60);
      return `${minutes} ${minutes === 1 ? 'min' : 'mins'} ago`;
    } else if (diffInSeconds < 86400) {
      const hours = Math.floor(diffInSeconds / 3600);
      return `${hours} ${hours === 1 ? 'hour' : 'hours'} ago`;
    } else {
      const days = Math.floor(diffInSeconds / 86400);
      return `${days} ${days === 1 ? 'day' : 'days'} ago`;
    }
  };

  const markAsRead = async (id: string) => {
    try {
      await axios.post(`/api/notifications/${id}/read`);
      // Update the local state to mark this notification as read
      setNotifications(notifications.map(notif => 
        notif.id === id ? { ...notif, read: true } : notif
      ));
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  };

  const markAllAsRead = async () => {
    try {
      await axios.post('/api/notifications/read-all');
      // Update all notifications in the local state as read
      setNotifications(notifications.map(notif => ({ ...notif, read: true })));
      toast.success('All notifications marked as read');
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error);
      toast.error('Failed to mark all as read');
    }
  };

  // Filter notifications by type for each tab
  const generalNotifications = notifications.filter(
    n => n.type === 'message' || n.type === 'alert' || n.type === 'system' || n.type === 'request'
  );
  
  const paymentNotifications = notifications.filter(
    n => n.type === 'payment'
  );

  return (
    <GuardianLayout pageTitle="Notifications">
      <Head title="Notifications" />
      
      <div className="container max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold">Notifications & Messages</h1>
          
          {notifications.length > 0 && (
            <button 
              onClick={markAllAsRead}
              className="text-sm text-teal-600 hover:text-teal-800"
            >
              Mark all as read
            </button>
          )}
        </div>
        
        <Tabs defaultValue="general" className="w-full">
          <TabsList className="bg-white rounded-full p-4 mb-6 border">
            <TabsTrigger 
              value="general" 
              className="rounded-full data-[state=active]:bg-teal-600 data-[state=active]:text-white px-6"
              onClick={() => setActiveTab('general')}
            >
              General Alerts
              {generalNotifications.length > 0 && (
                <span className="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                  {generalNotifications.length}
                </span>
              )}
            </TabsTrigger>
            <TabsTrigger 
              value="payment" 
              className="rounded-full data-[state=active]:bg-teal-600 data-[state=active]:text-white px-6"
              onClick={() => setActiveTab('payment')}
            >
              Payment & Wallet Updates
              {paymentNotifications.length > 0 && (
                <span className="ml-2 bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                  {paymentNotifications.length}
                </span>
              )}
            </TabsTrigger>
          </TabsList>
          
          {loading ? (
            <div className="flex justify-center items-center py-12">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600"></div>
            </div>
          ) : (
            <>
              <TabsContent value="general">
                {generalNotifications.length > 0 ? (
                  <GeneralNotifications 
                    notifications={generalNotifications} 
                    onMarkAsRead={markAsRead}
                  />
                ) : (
                  <div className="text-center py-12 text-gray-500">
                    No notifications to display
                  </div>
                )}
              </TabsContent>
              
              <TabsContent value="payment">
                {paymentNotifications.length > 0 ? (
                  <PaymentNotifications 
                    notifications={paymentNotifications}
                    onMarkAsRead={markAsRead}
                  />
                ) : (
                  <div className="text-center py-12 text-gray-500">
                    No payment notifications to display
                  </div>
                )}
              </TabsContent>
            </>
          )}
        </Tabs>
      </div>
    </GuardianLayout>
  );
} 