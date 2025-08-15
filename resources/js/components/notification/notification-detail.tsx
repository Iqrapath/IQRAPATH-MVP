import React, { useEffect } from 'react';
import { formatDistanceToNow, format } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Check, Trash2, Bell, ArrowLeft } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Notification } from '@/types';
import { useInitials } from '@/hooks/use-initials';
import { Badge } from '@/components/ui/badge';

interface NotificationDetailProps {
  notification: Notification;
  onMarkAsRead?: (notification: Notification) => void;
  onDelete?: (notification: Notification) => void;
  onBack?: () => void;
  className?: string;
}

export function NotificationDetail({ 
  notification, 
  onMarkAsRead, 
  onDelete,
  onBack,
  className 
}: NotificationDetailProps) {
  const getInitials = useInitials();
  const isUnread = !notification.read_at;
  
  // Mark as read when viewed
  useEffect(() => {
    if (isUnread && onMarkAsRead) {
      onMarkAsRead(notification);
    }
  }, [notification, isUnread, onMarkAsRead]);
  
  const getNotificationIcon = (type: string, level?: string) => {
    // You can customize this based on notification types in your system
    switch (type) {
      case 'App\\Notifications\\MessageNotification':
        return <Bell className="h-5 w-5 text-blue-500" />;
      case 'App\\Notifications\\PaymentNotification':
        return <Bell className="h-5 w-5 text-green-500" />;
      case 'App\\Notifications\\SessionRequestNotification':
        return <Bell className="h-5 w-5 text-amber-500" />;
      case 'new_user_registration':
        return <Bell className="h-5 w-5 text-teal-500" />;
      default:
        return <Bell className="h-5 w-5 text-gray-500" />;
    }
  };
  
  const getNotificationType = (type: string): string => {
    if (type.includes('MessageNotification')) return 'Message';
    if (type.includes('PaymentNotification')) return 'Payment';
    if (type.includes('SessionRequestNotification')) return 'Session Request';
    if (type.includes('SystemNotification')) return 'System';
    if (type === 'new_user_registration') return 'New User Registration';
    return 'Notification';
  };
  
  const getNotificationLevelColor = (level?: string): string => {
    switch (level) {
      case 'info': return 'bg-blue-500';
      case 'success': return 'bg-green-500';
      case 'warning': return 'bg-amber-500';
      case 'error': return 'bg-red-500';
      default: return 'bg-gray-500';
    }
  };

  // Helper function to handle action URL
  const handleActionClick = (e: React.MouseEvent) => {
    // If the URL is not a route (e.g., external URL), open in new tab
    if (notification.data.action_url && !notification.data.action_url.startsWith('/')) {
      e.preventDefault();
      window.open(notification.data.action_url, '_blank');
    }
  };

  return (
    <Card className={cn("shadow-md", className)}>
      <CardHeader>
        <div className="flex items-center justify-between">
          {onBack ? (
            <Button variant="ghost" size="sm" onClick={onBack}>
              <ArrowLeft className="mr-2 h-4 w-4" />
              Back
            </Button>
          ) : (
            <div />
          )}
          
          <div className="flex items-center gap-2">
            {isUnread && onMarkAsRead && (
              <Button
                variant="outline"
                size="sm"
                onClick={() => onMarkAsRead(notification)}
              >
                <Check className="mr-2 h-4 w-4" />
                Mark as read
              </Button>
            )}
            
            {onDelete && (
              <Button
                variant="outline"
                size="sm"
                className="text-destructive"
                onClick={() => onDelete(notification)}
              >
                <Trash2 className="mr-2 h-4 w-4" />
                Delete
              </Button>
            )}
          </div>
        </div>
        
        <div className="flex items-center gap-3 mt-4">
          <div className="rounded-full p-2 bg-muted">
            {getNotificationIcon(notification.type, notification.level)}
          </div>
          
          <div>
            <CardTitle className="text-xl">{notification.data.title}</CardTitle>
            <div className="flex items-center gap-2 mt-1">
              <Badge variant="outline">
                {getNotificationType(notification.type)}
              </Badge>
              
              {notification.level && (
                <Badge className={getNotificationLevelColor(notification.level)}>
                  {notification.level.charAt(0).toUpperCase() + notification.level.slice(1)}
                </Badge>
              )}
              
              <CardDescription>
                {format(new Date(notification.created_at), 'PPpp')}
              </CardDescription>
            </div>
          </div>
        </div>
      </CardHeader>
      
      <CardContent className="space-y-4">
        {notification.data.sender_id && notification.data.sender_name && (
          <div className="flex items-center gap-3 p-3 bg-muted/30 rounded-md">
            <Avatar className="h-10 w-10">
              {notification.data.sender_avatar && (
                <AvatarImage src={notification.data.sender_avatar} alt={notification.data.sender_name} />
              )}
              <AvatarFallback className="bg-primary text-white">
                {getInitials(notification.data.sender_name)}
              </AvatarFallback>
            </Avatar>
            <div>
              <p className="text-sm font-medium">{notification.data.sender_name}</p>
              <p className="text-xs text-muted-foreground">Sent {formatDistanceToNow(new Date(notification.created_at), { addSuffix: true })}</p>
            </div>
          </div>
        )}
        
        <div className="p-4 bg-muted/20 rounded-md">
          <p className="text-base whitespace-pre-wrap">{notification.data.message}</p>
        </div>
        
        {/* Special handling for new user registrations */}
        {notification.type === 'new_user_registration' && (
          <div className="p-4 bg-teal-50 rounded-md border border-teal-200">
            <h4 className="text-sm font-medium text-teal-800 mb-3">New User Details</h4>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
              {notification.data.new_user_name && (
                <div>
                  <span className="font-medium text-teal-700">Name:</span>
                  <span className="ml-2 text-teal-800">{notification.data.new_user_name}</span>
                </div>
              )}
              {notification.data.new_user_email && notification.data.new_user_email !== 'No email provided' && (
                <div>
                  <span className="font-medium text-teal-700">Email:</span>
                  <span className="ml-2 text-teal-800">{notification.data.new_user_email}</span>
                </div>
              )}
              {notification.data.new_user_phone && notification.data.new_user_phone !== 'No phone provided' && (
                <div>
                  <span className="font-medium text-teal-700">Phone:</span>
                  <span className="ml-2 text-teal-800">{notification.data.new_user_phone}</span>
                </div>
              )}
              {notification.data.registration_time && (
                <div>
                  <span className="font-medium text-teal-700">Registration Time:</span>
                  <span className="ml-2 text-teal-800">{notification.data.registration_time}</span>
                </div>
              )}
            </div>
          </div>
        )}
        
        {notification.data.image_url && (
          <div className="mt-4">
            <img 
              src={notification.data.image_url} 
              alt="Notification image" 
              className="rounded-md max-h-64 object-contain mx-auto"
            />
          </div>
        )}
      </CardContent>
      
      {notification.data.action_url && notification.data.action_text && (
        <CardFooter>
          {notification.data.action_url.startsWith('/') ? (
            // Internal URL - use Inertia Link
            <Link href={notification.data.action_url}>
              <Button>
                {notification.data.action_text}
              </Button>
            </Link>
          ) : (
            // External URL - use regular anchor tag
            <a 
              href={notification.data.action_url} 
              target="_blank" 
              rel="noopener noreferrer"
              className="no-underline"
            >
              <Button>
                {notification.data.action_text}
              </Button>
            </a>
          )}
        </CardFooter>
      )}
    </Card>
  );
}

export default NotificationDetail; 