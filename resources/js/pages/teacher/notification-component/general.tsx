import React from 'react';
import { Button } from '@/components/ui/button';
import { MessageIcon } from '@/components/icons/message-icon';
import { AlertIcon } from '@/components/icons/alert-icon';
import { MessageCircleIcon } from 'lucide-react';
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

interface GeneralNotificationsProps {
  notifications: Notification[];
  onMarkAsRead?: (id: string) => void;
}

export default function GeneralNotifications({ notifications, onMarkAsRead }: GeneralNotificationsProps) {
  const getIcon = (type: string) => {
    switch (type) {
      case 'message':
        return <MessageIcon className="h-6 w-6 text-blue-500" />;
      case 'alert':
        return <AlertIcon className="h-6 w-6 text-amber-500" />;
      case 'system':
        return <PaymentIcon className="h-6 w-6 text-teal-600" />;
      case 'request':
        return <MessageCircleIcon className="h-6 w-6 text-blue-500" />;
      default:
        return null;
    }
  };

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
            {getIcon(notification.type)}
          </div>
          
          <div className="flex-1">
            <h3 className="font-semibold">{notification.title}</h3>
            <p className="text-sm text-gray-600">{notification.message}</p>
            <p className="text-xs text-gray-500 mt-1">{notification.time}</p>
          </div>
          
          {notification.hasAction && notification.type === 'message' && (
            <Button size="lg" variant="outline" className="text-teal-600 border-teal-600 hover:bg-teal-50 rounded-full px-2 bg-teal-50">
              <MessageCircleIcon className="h-6 w-6 text-teal-600" />
              Reply
            </Button>
          )}
          
          {notification.hasAction && notification.type === 'request' && (
            <div className="flex gap-2">
              <Button size="sm" className="bg-teal-600 hover:bg-teal-700 text-white rounded-full px-4">
                Accept
              </Button>
              <Button size="sm" variant="outline" className="text-red-500 border-red-500 hover:bg-red-50 rounded-full px-4">
                Decline
              </Button>
            </div>
          )}
        </div>
      ))}
    </div>
  );
}
