import React, { useState } from 'react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Head } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import GeneralNotifications from './notification-component/general';
import PaymentNotifications from './notification-component/payment';

interface Notification {
  id: string;
  type: 'message' | 'alert' | 'system' | 'request';
  title: string;
  message?: string;
  time: string;
  sender?: string;
  hasAction?: boolean;
}

export default function TeacherNotifications() {
  const [activeTab, setActiveTab] = useState('general');

  const notifications: Notification[] = [
    {
      id: '1',
      type: 'message',
      title: 'Ahmed Yusuf',
      message: 'When can we schedule next week?',
      time: '10 mins ago',
      hasAction: true
    },
    {
      id: '2',
      type: 'alert',
      title: 'Admin Notice',
      message: 'Profile verification is scheduled for March 5.',
      time: '3 hours ago'
    },
    {
      id: '3',
      type: 'system',
      title: 'System Notification',
      message: 'Payment received: $50.00 added to your wallet',
      time: '2 hours ago'
    },
    {
      id: '4',
      type: 'request',
      title: 'Fatima Ibrahim',
      message: 'Requested a Tajweed Class on March 10,5pm',
      time: '3 hours ago',
      hasAction: true
    }
  ];

  return (
    <TeacherLayout pageTitle="Notifications">
      <Head title="Notifications" />
      
      <div className="container max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 className="text-2xl font-bold mb-6">Notifications & Messages</h1>
        
        <Tabs defaultValue="general" className="w-full">
          <TabsList className="bg-white rounded-full p-3 mb-6 border">
            <TabsTrigger 
              value="general" 
              className="rounded-full data-[state=active]:bg-teal-600 data-[state=active]:text-white px-6"
              onClick={() => setActiveTab('general')}
            >
              General Alerts
            </TabsTrigger>
            <TabsTrigger 
              value="payment" 
              className="rounded-full data-[state=active]:bg-teal-600 data-[state=active]:text-white px-6"
              onClick={() => setActiveTab('payment')}
            >
              Payment & Wallet Updates
            </TabsTrigger>
          </TabsList>
          
          <TabsContent value="general">
            <GeneralNotifications notifications={notifications} />
          </TabsContent>
          
          <TabsContent value="payment">
            <PaymentNotifications />
          </TabsContent>
        </Tabs>
      </div>
    </TeacherLayout>
  );
} 