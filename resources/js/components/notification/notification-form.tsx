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
import { Checkbox } from '@/components/ui/checkbox';
import { toast } from 'sonner';
import { Notification } from '@/types';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Check, ChevronsUpDown, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

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
  const [users, setUsers] = useState<{ id: number; name: string; role?: string; email?: string; phone?: string }[]>([]);
  const [isLoadingUsers, setIsLoadingUsers] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [selectedRole, setSelectedRole] = useState<string | null>(null);
  
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
        // Fetch users from API
        const response = await axios.get('/api/users/list');
        
        if (response.data && Array.isArray(response.data) && response.data.length > 0) {
          setUsers(response.data);
          
          // If only one user is returned, automatically select it
          if (response.data.length === 1) {
            form.setValue('recipients', [response.data[0].id]);
          }
        } else {
          toast.error('No users found. Please contact an administrator.');
        }
      } catch (error) {
        console.error('Failed to fetch users', error);
        toast.error('Failed to load users. Please refresh and try again.');
      } finally {
        setIsLoadingUsers(false);
      }
    };
    
    fetchUsers();
  }, [form]);
  
  // Handle form submission
  const onSubmit = async (values: NotificationFormValues) => {
    setIsSubmitting(true);
    
    try {
      // Create a notification for each recipient
      const promises = values.recipients.map(async (recipientId) => {
        const notificationData = {
          title: values.title,
          message: values.message,
          type: values.type,
          level: values.level,
          action_text: values.action_text || '',
          action_url: values.action_url || '',
          image_url: values.image_url || '',
          recipient_id: recipientId
        };
        
        // Use the existing notification endpoint
        return await axios.post('/api/notifications', notificationData);
      });
      
      await Promise.all(promises);
      
      toast.success(`Notification sent to ${values.recipients.length} recipient(s)`);
      
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
                <FormItem className="flex flex-col">
                  <FormLabel>Recipients</FormLabel>
                  <div className="flex items-center gap-2 mb-2">
                    <Select
                      value={selectedRole || ""}
                      onValueChange={(value) => setSelectedRole(value === "all" ? null : value)}
                    >
                      <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="Filter by role" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Roles</SelectItem>
                        <SelectItem value="super-admin">Super Admin</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                        <SelectItem value="teacher">Teacher</SelectItem>
                        <SelectItem value="student">Student</SelectItem>
                        <SelectItem value="guardian">Guardian</SelectItem>
                      </SelectContent>
                    </Select>
                    <Input
                      placeholder="Search users..."
                      value={searchTerm}
                      onChange={(e) => setSearchTerm(e.target.value)}
                      className="flex-1"
                    />
                  </div>
                  <FormControl>
                    <Popover>
                      <PopoverTrigger asChild>
                        <Button
                          variant="outline"
                          role="combobox"
                          className={cn(
                            "w-full justify-between",
                            !field.value.length && "text-muted-foreground"
                          )}
                        >
                          {field.value.length > 0
                            ? `${field.value.length} user${field.value.length > 1 ? "s" : ""} selected`
                            : "Select users"}
                          <ChevronsUpDown className="ml-2 h-4 w-4 shrink-0 opacity-50" />
                        </Button>
                      </PopoverTrigger>
                      <PopoverContent className="w-full p-0" align="start">
                        <Command>
                          <CommandInput 
                            placeholder="Search users..." 
                            value={searchTerm}
                            onValueChange={setSearchTerm}
                          />
                          <CommandList>
                            <CommandEmpty>No users found.</CommandEmpty>
                            <CommandGroup>
                              {users
                                .filter(user => 
                                  (!selectedRole || user.role === selectedRole) && 
                                  (user.name?.toLowerCase().includes(searchTerm.toLowerCase()) || 
                                   user.email?.toLowerCase().includes(searchTerm.toLowerCase()))
                                )
                                .map(user => (
                                  <CommandItem
                                    key={user.id}
                                    onSelect={() => {
                                      const updatedValue = field.value.includes(user.id)
                                        ? field.value.filter(id => id !== user.id)
                                        : [...field.value, user.id];
                                      field.onChange(updatedValue);
                                    }}
                                  >
                                    <Check
                                      className={cn(
                                        "mr-2 h-4 w-4",
                                        field.value.includes(user.id) ? "opacity-100" : "opacity-0"
                                      )}
                                    />
                                    <div className="flex flex-col">
                                      <span>{user.name}</span>
                                      <span className="text-xs text-muted-foreground">
                                        {user.role && <span className="capitalize">{user.role}</span>}
                                        {user.email && <span> - {user.email}</span>}
                                        {user.phone && <span> - {user.phone}</span>}
                                      </span>
                                    </div>
                                  </CommandItem>
                                ))}
                            </CommandGroup>
                          </CommandList>
                        </Command>
                      </PopoverContent>
                    </Popover>
                  </FormControl>
                  
                  {field.value.length > 0 && (
                    <div className="flex flex-wrap gap-1 mt-2">
                      {field.value.map(userId => {
                        const user = users.find(u => u.id === userId);
                        return user ? (
                          <Badge key={user.id} variant="secondary" className="text-xs py-0">
                            {user.name}
                            <X
                              className="ml-1 h-3 w-3 cursor-pointer"
                              onClick={() => {
                                field.onChange(field.value.filter(id => id !== user.id));
                              }}
                            />
                          </Badge>
                        ) : null;
                      })}
                    </div>
                  )}
                  
                  <FormDescription>
                    Select one or more users to receive this notification.
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