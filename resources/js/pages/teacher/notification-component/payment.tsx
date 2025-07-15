import React from 'react';
import { Button } from '@/components/ui/button';
import { PaymentIcon } from '@/components/icons/payment-icon';

interface Notification {
  id: string;
  type: 'message' | 'alert' | 'system' | 'request' | 'payment';
  title: string;
  message?: string;
  time: string;
  sender?: string;
  hasAction?: boolean;
  read?: boolean;
}

interface PaymentNotificationsProps {
  notifications: Notification[];
  onMarkAsRead?: (id: string) => void;
}

export default function PaymentNotifications({ notifications, onMarkAsRead }: PaymentNotificationsProps) {
  const handleAction = (id: string) => {
    if (onMarkAsRead) {
      onMarkAsRead(id);
    }
  };

  return (
    <div className="space-y-4">
      {notifications.map((notification) => (
        <div 
          key={notification.id} 
          className={`bg-white rounded-lg shadow-sm p-4 flex items-start gap-4 ${!notification.read ? 'border-l-4 border-teal-600' : ''}`}
          onClick={() => handleAction(notification.id)}
        >
          <div className="flex-shrink-0">
            <PaymentIcon className="h-6 w-6 text-green-600" />
          </div>
          
          <div className="flex-1">
            <h3 className="font-semibold">{notification.title}</h3>
            <p className="text-sm text-gray-600">{notification.message}</p>
            <p className="text-xs text-gray-500 mt-1">{notification.time}</p>
          </div>
          
          {notification.hasAction && (
            <Button size="sm" className="bg-teal-600 hover:bg-teal-700 text-white rounded-full px-4">
              View Details
            </Button>
          )}
        </div>
      ))}
    </div>
  );
}
