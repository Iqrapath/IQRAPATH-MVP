import React from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Send, Loader2 } from 'lucide-react';
import { format } from 'date-fns';
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

interface NotificationReplyProps {
  notification: Notification;
  userRole: 'teacher' | 'student' | 'guardian' | 'admin';
  currentUser: User;
  onCancel?: () => void;
  backUrl?: string;
  submitEndpoint: string;
}

export default function NotificationReply({
  notification,
  userRole,
  currentUser,
  onCancel,
  backUrl = '/notifications',
  submitEndpoint
}: NotificationReplyProps) {
  const getInitials = useInitials();
  
  const { data, setData, post, processing, errors } = useForm({
    parent_id: notification.id,
    title: `Re: ${notification.title}`,
    body: '',
    recipient_id: notification.sender?.id || null
  });
  
  // Format the notification date
  const formattedDate = notification.created_at
    ? format(new Date(notification.created_at), 'MMM dd, yyyy – h:mm a')
    : 'Unknown date';
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    post(submitEndpoint);
  };

  return (
    <div className="py-6">
      <div className="mb-6 flex justify-between items-center">
        <div className="flex items-center gap-2">
          {onCancel ? (
            <Button variant="ghost" size="sm" onClick={onCancel} className="mr-2">
              <ArrowLeft className="h-4 w-4 mr-1" /> Back
            </Button>
          ) : (
            <a href={backUrl}>
              <Button variant="ghost" size="sm" className="mr-2">
                <ArrowLeft className="h-4 w-4 mr-1" /> Back
              </Button>
            </a>
          )}
          <h1 className="text-2xl font-bold text-gray-800">Reply to Message</h1>
        </div>
      </div>
      
      {/* Original Message Card */}
      <Card className="mb-6 border-l-4 border-l-gray-300">
        <CardContent className="p-4">
          <div className="flex justify-between items-start mb-2">
            <div className="flex items-center gap-3">
              {notification.sender && (
                <Avatar className="h-8 w-8">
                  {notification.sender.avatar ? (
                    <AvatarImage src={notification.sender.avatar} alt={notification.sender.name} />
                  ) : (
                    <AvatarFallback className="bg-primary text-white">
                      {getInitials(notification.sender.name)}
                    </AvatarFallback>
                  )}
                </Avatar>
              )}
              <div>
                <h3 className="font-medium">{notification.title}</h3>
                <div className="text-xs text-muted-foreground">
                  From: {notification.sender ? notification.sender.name : 'System'} • {formattedDate}
                </div>
              </div>
            </div>
            <Badge variant="outline" className="text-sky-500 border-sky-200 bg-sky-50">Message</Badge>
          </div>
          
          <div className="text-gray-700 whitespace-pre-wrap mt-3 bg-gray-50 p-3 rounded-md border text-sm">
            {notification.body}
          </div>
        </CardContent>
      </Card>
      
      {/* Reply Form */}
      <Card>
        <CardContent className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            <div className="flex items-center gap-3 mb-4">
              <Avatar className="h-8 w-8">
                {currentUser.avatar ? (
                  <AvatarImage src={currentUser.avatar} alt={currentUser.name} />
                ) : (
                  <AvatarFallback className="bg-primary text-white">
                    {getInitials(currentUser.name)}
                  </AvatarFallback>
                )}
              </Avatar>
              <div>
                <div className="font-medium">{currentUser.name}</div>
                <div className="text-xs text-muted-foreground capitalize">{userRole}</div>
              </div>
            </div>
            
            <div className="space-y-2">
              <Textarea 
                id="body" 
                value={data.body} 
                onChange={(e) => setData('body', e.target.value)}
                placeholder="Type your reply here..."
                rows={6}
              />
              {errors.body && (
                <p className="text-sm text-red-500 mt-1">{errors.body}</p>
              )}
            </div>
            
            {/* Submit Button */}
            <div className="flex justify-end gap-3">
              {onCancel && (
                <Button type="button" variant="outline" onClick={onCancel}>
                  Cancel
                </Button>
              )}
              <Button 
                type="submit" 
                disabled={processing || !data.body.trim()} 
                className="bg-teal-600 hover:bg-teal-700"
              >
                {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                <Send className="h-4 w-4 mr-2" /> Send Reply
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
} 