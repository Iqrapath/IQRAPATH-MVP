import React from 'react';
import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format } from 'date-fns';
import { Reply, Trash2, ArrowLeft, UserCheck } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';

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
  status?: string;
  created_at: string;
  sender?: User;
  is_personalized?: boolean;
  metadata?: Record<string, any>;
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
  const getStatusBadge = (status?: string) => {
    // If status is undefined or null, return a default badge
    if (!status) {
      return <Badge className="bg-gray-500 hover:bg-gray-600">Unknown</Badge>;
    }
    
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
    // Add null check for type as well
    if (!type) {
      return <Badge variant="outline">Unknown</Badge>;
    }
    
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

  // Function to highlight placeholders in text
  const highlightPlaceholders = (text: string) => {
    if (!text) return '';
    
    // Regular expression to find placeholders like [placeholder_name]
    const regex = /\[([\w_]+)\]/g;
    
    // Split the text by placeholders and create an array of parts
    const parts = text.split(regex);
    
    if (parts.length <= 1) {
      return text;
    }
    
    // Create an array to hold JSX elements
    const elements: React.ReactNode[] = [];
    
    // Process each part
    for (let i = 0; i < parts.length; i++) {
      // Even indices are regular text
      if (i % 2 === 0) {
        elements.push(<span key={`text-${i}`}>{parts[i]}</span>);
      } 
      // Odd indices are placeholders
      else {
        elements.push(
          <span 
            key={`placeholder-${i}`}
            className="bg-teal-100 text-teal-800 px-1 rounded"
          >
            [{parts[i]}]
          </span>
        );
      }
    }
    
    return <>{elements}</>;
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
          {onReply && notification.type && notification.type.toLowerCase() === 'message' && (
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
              <h3 className="text-xl font-medium">
                {notification.title}
                {notification.is_personalized && (
                  <TooltipProvider>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <span className="inline-block ml-2">
                          <UserCheck className="h-4 w-4 text-teal-500 inline" />
                        </span>
                      </TooltipTrigger>
                      <TooltipContent>
                        <p>Personalized content</p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                )}
              </h3>
              {notification.type && getTypeBadge(notification.type)}
            </div>
            {getStatusBadge(notification.status)}
          </div>
          
          <div className="text-gray-700 whitespace-pre-wrap mb-6 bg-gray-50 p-4 rounded-md border">
            {userRole === 'admin' && notification.body.includes('[') && notification.body.includes(']')
              ? highlightPlaceholders(notification.body)
              : notification.body}
          </div>
          
          {notification.metadata && Object.keys(notification.metadata).length > 0 && userRole === 'admin' && (
            <div className="mb-6">
              <h4 className="text-sm font-semibold text-gray-600 mb-2">Metadata / Placeholders</h4>
              <div className="bg-gray-50 p-3 rounded-md border text-sm">
                <div className="grid grid-cols-2 md:grid-cols-3 gap-2">
                  {Object.entries(notification.metadata).map(([key, value]) => (
                    <div key={key} className="flex gap-2">
                      <span className="font-medium text-gray-700">[{key}]:</span>
                      <span className="text-gray-600">{String(value)}</span>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
          
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