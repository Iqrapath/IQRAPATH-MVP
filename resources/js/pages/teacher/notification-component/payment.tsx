import React from 'react';
import { PaymentIcon } from '@/components/icons/payment-icon';

interface PaymentNotification {
  id: string;
  title: string;
  message: string;
  time: string;
  type: 'payment' | 'withdrawal';
}

export default function PaymentNotifications() {
  const notifications: PaymentNotification[] = [
    {
      id: '1',
      type: 'payment',
      title: 'New Payment Received',
      message: 'Payment received: #50,000 added to your wallet',
      time: '1 hours ago'
    },
    {
      id: '2',
      type: 'withdrawal',
      title: 'Withdrawal Request Approved',
      message: '#38,000 sent to your account',
      time: '3 hours ago'
    }
  ];

  return (
    <div className="space-y-4">
      {notifications.map((notification) => (
        <div key={notification.id} className="bg-white rounded-lg shadow-sm p-4 flex items-start gap-4">
          <div className="flex-shrink-0">
            <PaymentIcon className="h-6 w-6 text-teal-600" />
          </div>
          
          <div className="flex-1">
            <h3 className="font-semibold text-gray-900">{notification.title}</h3>
            <p className="text-sm text-gray-600">{notification.message}</p>
            <p className="text-xs text-gray-500 mt-1">{notification.time}</p>
          </div>
        </div>
      ))}
    </div>
  );
}
