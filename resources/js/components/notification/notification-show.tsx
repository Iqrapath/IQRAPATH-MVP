import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { Reply, Trash2, ArrowLeft } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';

interface User {
  id: number;
  name: string;
  email?: string;
  role?: string;
  avatar?: string;
}

interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  created_at: string;
  sender?: User;
}

interface NotificationShowProps {
  notification: Notification;
  userRole: 'teacher' | 'student' | 'guardian' | 'admin';
  onReply?: (notification: Notification) => void;
  onDelete?: (id: number) => void;
  onBack?: () => void;
  backUrl?: string;
}

export default function NotificationShow({ 
  notification, 
  userRole,
  onReply,
  onDelete,
  onBack,
  backUrl = '/notifications'
}: NotificationShowProps) {
  const getInitials = useInitials();
  
  // Format the notification date
  const formattedDate = notification.created_at
    ? format(new Date(notification.created_at), 'MMM dd, yyyy – h:mm a')
    : 'Unknown date';
  
  // Helper function to get status badge
  const getStatusBadge = (status: string) => {
    switch (status.toLowerCase()) {
      case 'delivered':
        return <Badge className="bg-green-500 hover:bg-green-600">Delivered</Badge>;
      case 'read':
        return <Badge className="bg-blue-500 hover:bg-blue-600">Read</Badge>;
      case 'failed':
        return <Badge className="bg-red-500 hover:bg-red-600">Failed</Badge>;
      case 'pending':
        return <Badge className="bg-yellow-500 hover:bg-yellow-600">Pending</Badge>;
      case 'scheduled':
        return <Badge className="bg-purple-500 hover:bg-purple-600">Scheduled</Badge>;
      case 'draft':
        return <Badge variant="outline" className="text-gray-500 border-gray-300">Draft</Badge>;
      case 'sent':
        return <Badge className="bg-teal-500 hover:bg-teal-600">Sent</Badge>;
      default:
        return <Badge className="bg-gray-500 hover:bg-gray-600">{status}</Badge>;
    }
  };
  
  // Helper function to get type badge
  const getTypeBadge = (type: string) => {
    switch (type.toLowerCase()) {
      case 'payment':
        return <Badge variant="outline" className="text-green-500 border-green-200 bg-green-50">Payment</Badge>;
      case 'session':
        return <Badge variant="outline" className="text-blue-500 border-blue-200 bg-blue-50">Session</Badge>;
      case 'message':
        return <Badge variant="outline" className="text-sky-500 border-sky-200 bg-sky-50">Message</Badge>;
      case 'alert':
      case 'admin':
        return <Badge variant="outline" className="text-amber-500 border-amber-200 bg-amber-50">Admin</Badge>;
      case 'system':
        return <Badge variant="outline" className="text-purple-500 border-purple-200 bg-purple-50">System</Badge>;
      default:
        return <Badge variant="outline">{type}</Badge>;
    }
  };

  return (
    <div className="py-6">
      <div className="mb-6 flex justify-between items-center">
        <div className="flex items-center gap-2">
          {onBack ? (
            <Button variant="ghost" size="sm" onClick={onBack} className="mr-2">
              <ArrowLeft className="h-4 w-4 mr-1" /> Back
            </Button>
          ) : (
            <Link href={backUrl}>
              <Button variant="ghost" size="sm" className="mr-2">
                <ArrowLeft className="h-4 w-4 mr-1" /> Back
              </Button>
            </Link>
          )}
          <h1 className="text-2xl font-bold text-gray-800">Notification Details</h1>
        </div>
        <div className="flex gap-2">
          {onReply && notification.type.toLowerCase() === 'message' && (
            <Button onClick={() => onReply(notification)} className="bg-teal-600 hover:bg-teal-700">
              <Reply className="h-4 w-4 mr-2" /> Reply
            </Button>
          )}
          {onDelete && (
            <Button 
              variant="outline" 
              className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
              onClick={() => onDelete(notification.id)}
            >
              <Trash2 className="h-4 w-4 mr-2" /> Delete
            </Button>
          )}
        </div>
      </div>
      
      <Card className="mb-6 border-t-4 border-t-teal-500">
        <CardContent className="p-6">
          <div className="flex justify-between items-start mb-4">
            <div className="flex items-center gap-3">
              <h3 className="text-xl font-medium">{notification.title}</h3>
              {getTypeBadge(notification.type)}
            </div>
            {getStatusBadge(notification.status)}
          </div>
          
          <div className="text-gray-700 whitespace-pre-wrap mb-6 bg-gray-50 p-4 rounded-md border">
            {notification.body}
          </div>
          
          <div className="flex justify-between items-center text-sm text-gray-600">
            <div className="flex items-center gap-3">
              {notification.sender && (
                <>
                  <Avatar className="h-8 w-8">
                    {notification.sender.avatar ? (
                      <AvatarImage src={notification.sender.avatar} alt={notification.sender.name} />
                    ) : (
                      <AvatarFallback className="bg-primary text-white">
                        {getInitials(notification.sender.name)}
                      </AvatarFallback>
                    )}
                  </Avatar>
                  <span>From: <span className="font-medium">{notification.sender.name}</span></span>
                </>
              )}
              {!notification.sender && <span>From: System</span>}
            </div>
            <span>{formattedDate}</span>
          </div>
        </CardContent>
      </Card>
    </div>
  );
} 