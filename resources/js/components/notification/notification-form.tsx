import React, { useState } from 'react';
import { useForm } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import * as z from 'zod';
import axios from 'axios';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Form,
  FormControl,
  FormDescription,
  FormField,
  FormItem,
  FormLabel,
  FormMessage,
} from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { toast } from 'sonner';
import { Notification } from '@/types';

// Define the form schema
const notificationFormSchema = z.object({
  title: z.string().min(3, {
    message: "Title must be at least 3 characters.",
  }),
  message: z.string().min(10, {
    message: "Message must be at least 10 characters.",
  }),
  type: z.string(),
  level: z.string(),
  action_text: z.string().optional(),
  action_url: z.string().url({
    message: "Please enter a valid URL.",
  }).optional().or(z.literal('')),
  image_url: z.string().url({
    message: "Please enter a valid URL.",
  }).optional().or(z.literal('')),
  recipients: z.array(z.number()).min(1, {
    message: "Please select at least one recipient.",
  }),
});

type NotificationFormValues = z.infer<typeof notificationFormSchema>;

interface NotificationFormProps {
  notification?: Notification;
  onSuccess?: () => void;
  onCancel?: () => void;
  className?: string;
}

export function NotificationForm({ 
  notification, 
  onSuccess, 
  onCancel,
  className 
}: NotificationFormProps) {
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [users, setUsers] = useState<{ id: number; name: string }[]>([]);
  const [isLoadingUsers, setIsLoadingUsers] = useState(false);
  
  // Initialize form with default values or existing notification data
  const form = useForm<NotificationFormValues>({
    resolver: zodResolver(notificationFormSchema),
    defaultValues: notification ? {
      title: notification.data.title,
      message: notification.data.message,
      type: notification.type.split('\\').pop() || 'SystemNotification',
      level: notification.level || 'info',
      action_text: notification.data.action_text || '',
      action_url: notification.data.action_url || '',
      image_url: notification.data.image_url || '',
      recipients: [notification.notifiable_id],
    } : {
      title: '',
      message: '',
      type: 'SystemNotification',
      level: 'info',
      action_text: '',
      action_url: '',
      image_url: '',
      recipients: [],
    },
  });
  
  // Load users for recipient selection
  React.useEffect(() => {
    const fetchUsers = async () => {
      setIsLoadingUsers(true);
      try {
        const response = await axios.get('/api/admin/users');
        setUsers(response.data);
      } catch (error) {
        console.error('Failed to fetch users', error);
        toast.error('Failed to load users');
      } finally {
        setIsLoadingUsers(false);
      }
    };
    
    fetchUsers();
  }, []);
  
  // Handle form submission
  const onSubmit = async (values: NotificationFormValues) => {
    setIsSubmitting(true);
    
    try {
      const endpoint = notification 
        ? `/api/admin/notifications/${notification.id}` 
        : '/api/admin/notifications';
      
      const method = notification ? 'put' : 'post';
      
      const response = await axios[method](endpoint, {
        ...values,
        type: `App\\Notifications\\${values.type}`,
      });
      
      toast.success(notification ? 'Notification updated' : 'Notification sent');
      
      if (onSuccess) {
        onSuccess();
      }
    } catch (error) {
      console.error('Failed to save notification', error);
      toast.error('Failed to save notification');
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <Card className={className}>
      <CardHeader>
        <CardTitle>{notification ? 'Edit Notification' : 'Create Notification'}</CardTitle>
        <CardDescription>
          {notification 
            ? 'Update the notification details below.' 
            : 'Fill in the details to send a new notification to users.'}
        </CardDescription>
      </CardHeader>
      <CardContent>
        <Form {...form}>
          <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
            <FormField
              control={form.control}
              name="title"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Title</FormLabel>
                  <FormControl>
                    <Input placeholder="Notification title" {...field} />
                  </FormControl>
                  <FormDescription>
                    The title of the notification that will be displayed to users.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
            
            <FormField
              control={form.control}
              name="message"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Message</FormLabel>
                  <FormControl>
                    <Textarea 
                      placeholder="Enter the notification message..." 
                      className="min-h-32" 
                      {...field} 
                    />
                  </FormControl>
                  <FormDescription>
                    The main content of the notification.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormField
                control={form.control}
                name="type"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Type</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select notification type" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="SystemNotification">System</SelectItem>
                        <SelectItem value="MessageNotification">Message</SelectItem>
                        <SelectItem value="PaymentNotification">Payment</SelectItem>
                        <SelectItem value="SessionRequestNotification">Session Request</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>
                      The type of notification.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="level"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Priority Level</FormLabel>
                    <Select onValueChange={field.onChange} defaultValue={field.value}>
                      <FormControl>
                        <SelectTrigger>
                          <SelectValue placeholder="Select priority level" />
                        </SelectTrigger>
                      </FormControl>
                      <SelectContent>
                        <SelectItem value="info">Info</SelectItem>
                        <SelectItem value="success">Success</SelectItem>
                        <SelectItem value="warning">Warning</SelectItem>
                        <SelectItem value="error">Error</SelectItem>
                      </SelectContent>
                    </Select>
                    <FormDescription>
                      The importance level of the notification.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              <FormField
                control={form.control}
                name="action_text"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Action Button Text (Optional)</FormLabel>
                    <FormControl>
                      <Input placeholder="View Details" {...field} />
                    </FormControl>
                    <FormDescription>
                      Text for the action button.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
              
              <FormField
                control={form.control}
                name="action_url"
                render={({ field }) => (
                  <FormItem>
                    <FormLabel>Action URL (Optional)</FormLabel>
                    <FormControl>
                      <Input placeholder="https://example.com/action" {...field} />
                    </FormControl>
                    <FormDescription>
                      URL to navigate to when the action button is clicked.
                    </FormDescription>
                    <FormMessage />
                  </FormItem>
                )}
              />
            </div>
            
            <FormField
              control={form.control}
              name="image_url"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Image URL (Optional)</FormLabel>
                  <FormControl>
                    <Input placeholder="https://example.com/image.jpg" {...field} />
                  </FormControl>
                  <FormDescription>
                    URL to an image to display with the notification.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
            
            <FormField
              control={form.control}
              name="recipients"
              render={({ field }) => (
                <FormItem>
                  <FormLabel>Recipients</FormLabel>
                  <FormControl>
                    <div>
                      {/* This is a placeholder - you would implement a proper multi-select component */}
                      <Select 
                        onValueChange={(value) => field.onChange([parseInt(value)])} 
                        defaultValue={field.value[0]?.toString()}
                      >
                        <FormControl>
                          <SelectTrigger>
                            <SelectValue placeholder="Select a recipient" />
                          </SelectTrigger>
                        </FormControl>
                        <SelectContent>
                          {isLoadingUsers ? (
                            <SelectItem value="loading">Loading users...</SelectItem>
                          ) : (
                            users.map(user => (
                              <SelectItem key={user.id} value={user.id.toString()}>
                                {user.name}
                              </SelectItem>
                            ))
                          )}
                        </SelectContent>
                      </Select>
                    </div>
                  </FormControl>
                  <FormDescription>
                    Select users who should receive this notification.
                  </FormDescription>
                  <FormMessage />
                </FormItem>
              )}
            />
          </form>
        </Form>
      </CardContent>
      <CardFooter className="flex justify-between">
        <Button variant="outline" onClick={onCancel}>
          Cancel
        </Button>
        <Button 
          onClick={form.handleSubmit(onSubmit)} 
          disabled={isSubmitting}
        >
          {isSubmitting ? 'Saving...' : notification ? 'Update Notification' : 'Send Notification'}
        </Button>
      </CardFooter>
    </Card>
  );
}

export default NotificationForm; 