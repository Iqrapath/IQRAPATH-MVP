import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { toast } from 'sonner';
import NotificationPreview from './notification-preview';

interface NotificationTemplate {
  id: number;
  name: string;
  title: string;
  body: string;
  type: string;
  placeholders: string[];
  level: string;
  action_text: string | null;
  action_url: string | null;
  is_active: boolean;
}

interface User {
  id: number;
  name: string;
  email: string;
  role: string | null;
}

interface Notification {
  id: string;
  type: string;
  data: any;
  read_at: string | null;
  channel: string;
  level: string;
  action_text: string | null;
  action_url: string | null;
  created_at: string;
  updated_at: string;
}

interface Props {
  templates: NotificationTemplate[];
  users: User[];
  notification?: Notification;
  isEditing?: boolean;
}

type NotificationType = 'custom' | 'template';
type AudienceType = 'all' | 'role' | 'individual';
type NotificationLevel = 'info' | 'success' | 'warning' | 'error';
type Channel = 'in-app' | 'email' | 'sms';

export default function NotificationForm({ templates, users, notification, isEditing = false }: Props) {
  const [notificationType, setNotificationType] = useState<NotificationType>('custom');
  const [selectedTemplate, setSelectedTemplate] = useState<string>('');
  const [audienceType, setAudienceType] = useState<AudienceType>('all');
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
  const [selectedUsers, setSelectedUsers] = useState<number[]>([]);
  const [notificationLevel, setNotificationLevel] = useState<NotificationLevel>((notification?.level as NotificationLevel) || 'info');
  const [channels, setChannels] = useState<Channel[]>([notification?.channel as Channel || 'in-app']);
  const [scheduledFor, setScheduledFor] = useState<string>('');
  const [isScheduled, setIsScheduled] = useState(false);

  // Form fields
  const [title, setTitle] = useState(notification?.data?.title || '');
  const [body, setBody] = useState(notification?.data?.body || '');
  const [actionText, setActionText] = useState(notification?.action_text || '');
  const [actionUrl, setActionUrl] = useState(notification?.action_url || '');

  // Template placeholders
  const [placeholders, setPlaceholders] = useState<Record<string, string>>({});

  // Available roles
  const availableRoles = ['super-admin', 'admin', 'teacher', 'student', 'guardian'];

  // Filter users by role
  const usersByRole = users.reduce((acc, user) => {
    const role = user.role || 'unassigned';
    if (!acc[role]) {
      acc[role] = [];
    }
    acc[role].push(user);
    return acc;
  }, {} as Record<string, User[]>);

  // Handle template selection
  useEffect(() => {
    if (selectedTemplate && notificationType === 'template') {
      const template = templates.find(t => t.name === selectedTemplate);
      if (template) {
        setTitle(template.title);
        setBody(template.body);
        setActionText(template.action_text || '');
        setActionUrl(template.action_url || '');
        setNotificationLevel(template.level as NotificationLevel);
        
        // Initialize placeholders
        const initialPlaceholders: Record<string, string> = {};
        template.placeholders.forEach(placeholder => {
          initialPlaceholders[placeholder] = '';
        });
        setPlaceholders(initialPlaceholders);
      }
    }
  }, [selectedTemplate, notificationType, templates]);

  // Handle placeholder changes
  const handlePlaceholderChange = (placeholder: string, value: string) => {
    setPlaceholders(prev => ({
      ...prev,
      [placeholder]: value
    }));

    // Update title and body with placeholder values
    if (selectedTemplate) {
      const template = templates.find(t => t.name === selectedTemplate);
      if (template) {
        let newTitle = template.title;
        let newBody = template.body;
        let newActionText = template.action_text || '';
        let newActionUrl = template.action_url || '';

        // Replace all placeholders
        Object.entries({ ...placeholders, [placeholder]: value }).forEach(([key, val]) => {
          const placeholderRegex = new RegExp(`{${key}}`, 'g');
          newTitle = newTitle.replace(placeholderRegex, val);
          newBody = newBody.replace(placeholderRegex, val);
          newActionText = newActionText.replace(placeholderRegex, val);
          newActionUrl = newActionUrl.replace(placeholderRegex, val);
        });

        setTitle(newTitle);
        setBody(newBody);
        setActionText(newActionText);
        setActionUrl(newActionUrl);
      }
    }
  };

  // Handle role selection
  const handleRoleToggle = (role: string) => {
    setSelectedRoles(prev => 
      prev.includes(role) 
        ? prev.filter(r => r !== role)
        : [...prev, role]
    );
  };

  // Handle user selection
  const handleUserToggle = (userId: number) => {
    setSelectedUsers(prev => 
      prev.includes(userId) 
        ? prev.filter(id => id !== userId)
        : [...prev, userId]
    );
  };

  // Handle channel toggle
  const handleChannelToggle = (channel: Channel) => {
    setChannels(prev => 
      prev.includes(channel) 
        ? prev.filter(c => c !== channel)
        : [...prev, channel]
    );
  };

  // Get selected users based on audience type
  const getSelectedUserIds = (): number[] => {
    switch (audienceType) {
      case 'all':
        return users.map(u => u.id);
      case 'role':
        return users
          .filter(user => selectedRoles.includes(user.role || 'unassigned'))
          .map(u => u.id);
      case 'individual':
        return selectedUsers;
      default:
        return [];
    }
  };

  // Validate form
  const validateForm = (): boolean => {
    if (!title.trim()) {
      toast.error('Title is required');
      return false;
    }
    if (!body.trim()) {
      toast.error('Message body is required');
      return false;
    }
    
    const userIds = getSelectedUserIds();
    if (userIds.length === 0) {
      toast.error('Please select at least one recipient');
      return false;
    }

    if (channels.length === 0) {
      toast.error('Please select at least one delivery channel');
      return false;
    }

    if (isScheduled && !scheduledFor) {
      toast.error('Please select a scheduled time');
      return false;
    }

    return true;
  };

  // Handle form submission
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    if (!validateForm()) {
      return;
    }

    const formData = {
      type: notificationType,
      template_name: notificationType === 'template' ? selectedTemplate : null,
      title,
      body,
      action_text: actionText || null,
      action_url: actionUrl || null,
      level: notificationLevel,
      audience_type: audienceType,
      audience_filter: audienceType === 'role' ? { roles: selectedRoles } : null,
      user_ids: getSelectedUserIds(),
      channels,
      scheduled_for: isScheduled ? scheduledFor : null,
      placeholders: notificationType === 'template' ? placeholders : null,
    };

    const url = isEditing ? `/admin/notifications/${notification?.id}` : '/admin/notifications';
    const method = isEditing ? 'put' : 'post';
    
    router[method](url, formData, {
      onSuccess: () => {
        toast.success(isEditing ? 'Notification updated successfully' : 'Notification created successfully');
      },
      onError: (errors) => {
        Object.values(errors).forEach(error => {
          toast.error(error as string);
        });
      }
    });
  };

  return (
    <div className="max-w-4xl mx-auto">
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Notification Type Selection */}
        <Card>
          <CardHeader>
            <CardTitle>Notification Type</CardTitle>
            <CardDescription>
              Choose whether to create a custom notification or use a template
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex space-x-4">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="custom"
                  checked={notificationType === 'custom'}
                  onCheckedChange={() => setNotificationType('custom')}
                />
                <label htmlFor="custom" className="text-sm font-medium">
                  Custom Notification
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="template"
                  checked={notificationType === 'template'}
                  onCheckedChange={() => setNotificationType('template')}
                />
                <label htmlFor="template" className="text-sm font-medium">
                  Use Template
                </label>
              </div>
            </div>

            {notificationType === 'template' && (
              <div className="mt-4">
                <Select value={selectedTemplate} onValueChange={setSelectedTemplate}>
                  <SelectTrigger>
                    <SelectValue placeholder="Select a template" />
                  </SelectTrigger>
                  <SelectContent>
                    {templates.filter(t => t.is_active).map(template => (
                      <SelectItem key={template.name} value={template.name}>
                        <div className="flex flex-col">
                          <span className="font-medium">{template.title}</span>
                          <span className="text-sm text-gray-500">{template.type}</span>
                        </div>
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Template Placeholders */}
        {notificationType === 'template' && selectedTemplate && placeholders && Object.keys(placeholders).length > 0 && (
          <Card>
            <CardHeader>
              <CardTitle>Template Placeholders</CardTitle>
              <CardDescription>
                Fill in the placeholders for the selected template
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {Object.keys(placeholders).map(placeholder => (
                  <div key={placeholder}>
                    <label className="block text-sm font-medium text-gray-700 mb-1">
                      {placeholder.replace(/_/g, ' ')}
                    </label>
                    <Input
                      value={placeholders[placeholder]}
                      onChange={(e) => handlePlaceholderChange(placeholder, e.target.value)}
                      placeholder={`Enter ${placeholder.replace(/_/g, ' ').toLowerCase()}`}
                    />
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        )}

        {/* Notification Content */}
        <Card>
          <CardHeader>
            <CardTitle>Notification Content</CardTitle>
            <CardDescription>
              Define the notification title, message, and action details
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Title *
              </label>
              <Input
                value={title}
                onChange={(e) => setTitle(e.target.value)}
                placeholder="Enter notification title"
                required
              />
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Message *
              </label>
              <Textarea
                value={body}
                onChange={(e) => setBody(e.target.value)}
                placeholder="Enter notification message"
                rows={4}
                required
              />
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Action Text
                </label>
                <Input
                  value={actionText}
                  onChange={(e) => setActionText(e.target.value)}
                  placeholder="e.g., View Details"
                />
              </div>
              <div>
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Action URL
                </label>
                <Input
                  value={actionUrl}
                  onChange={(e) => setActionUrl(e.target.value)}
                  placeholder="e.g., /dashboard"
                />
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 mb-1">
                Notification Level
              </label>
              <Select value={notificationLevel} onValueChange={(value) => setNotificationLevel(value as NotificationLevel)}>
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="info">
                    <div className="flex items-center space-x-2">
                      <Badge variant="secondary">Info</Badge>
                      <span>Information</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="success">
                    <div className="flex items-center space-x-2">
                      <Badge variant="default" className="bg-green-100 text-green-800">Success</Badge>
                      <span>Success</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="warning">
                    <div className="flex items-center space-x-2">
                      <Badge variant="default" className="bg-yellow-100 text-yellow-800">Warning</Badge>
                      <span>Warning</span>
                    </div>
                  </SelectItem>
                  <SelectItem value="error">
                    <div className="flex items-center space-x-2">
                      <Badge variant="destructive">Error</Badge>
                      <span>Error</span>
                    </div>
                  </SelectItem>
                </SelectContent>
              </Select>
            </div>
          </CardContent>
        </Card>

        {/* Audience Selection */}
        <Card>
          <CardHeader>
            <CardTitle>Recipients</CardTitle>
            <CardDescription>
              Choose who should receive this notification
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex space-x-4">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="all"
                  checked={audienceType === 'all'}
                  onCheckedChange={() => setAudienceType('all')}
                />
                <label htmlFor="all" className="text-sm font-medium">
                  All Users ({users.length})
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="role"
                  checked={audienceType === 'role'}
                  onCheckedChange={() => setAudienceType('role')}
                />
                <label htmlFor="role" className="text-sm font-medium">
                  By Role
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="individual"
                  checked={audienceType === 'individual'}
                  onCheckedChange={() => setAudienceType('individual')}
                />
                <label htmlFor="individual" className="text-sm font-medium">
                  Individual Users
                </label>
              </div>
            </div>

            {audienceType === 'role' && (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Select Roles
                </label>
                <div className="flex flex-wrap gap-2">
                  {availableRoles.map(role => (
                    <div key={role} className="flex items-center space-x-2">
                      <Checkbox
                        id={`role-${role}`}
                        checked={selectedRoles.includes(role)}
                        onCheckedChange={() => handleRoleToggle(role)}
                      />
                      <label htmlFor={`role-${role}`} className="text-sm">
                        {role.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())} 
                        ({usersByRole[role]?.length || 0})
                      </label>
                    </div>
                  ))}
                </div>
              </div>
            )}

            {audienceType === 'individual' && (
              <div className="space-y-2">
                <label className="block text-sm font-medium text-gray-700">
                  Select Users
                </label>
                <div className="max-h-60 overflow-y-auto border rounded-md p-4">
                  {Object.entries(usersByRole).map(([role, roleUsers]) => (
                    <div key={role} className="mb-4">
                      <h4 className="font-medium text-sm text-gray-700 mb-2">
                        {role.replace('-', ' ').replace(/\b\w/g, l => l.toUpperCase())} ({roleUsers.length})
                      </h4>
                      <div className="space-y-1">
                        {roleUsers.map(user => (
                          <div key={user.id} className="flex items-center space-x-2">
                            <Checkbox
                              id={`user-${user.id}`}
                              checked={selectedUsers.includes(user.id)}
                              onCheckedChange={() => handleUserToggle(user.id)}
                            />
                            <label htmlFor={`user-${user.id}`} className="text-sm">
                              {user.name} ({user.email})
                            </label>
                          </div>
                        ))}
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            )}

            <div className="text-sm text-gray-600">
              Total recipients: {getSelectedUserIds().length} users
            </div>
          </CardContent>
        </Card>

        {/* Delivery Channels */}
        <Card>
          <CardHeader>
            <CardTitle>Delivery Channels</CardTitle>
            <CardDescription>
              Choose how the notification should be delivered
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex space-x-4">
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="in-app"
                  checked={channels.includes('in-app')}
                  onCheckedChange={() => handleChannelToggle('in-app')}
                />
                <label htmlFor="in-app" className="text-sm font-medium">
                  In-App Notification
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="email"
                  checked={channels.includes('email')}
                  onCheckedChange={() => handleChannelToggle('email')}
                />
                <label htmlFor="email" className="text-sm font-medium">
                  Email
                </label>
              </div>
              <div className="flex items-center space-x-2">
                <Checkbox
                  id="sms"
                  checked={channels.includes('sms')}
                  onCheckedChange={() => handleChannelToggle('sms')}
                />
                <label htmlFor="sms" className="text-sm font-medium">
                  SMS
                </label>
              </div>
            </div>
          </CardContent>
        </Card>

        {/* Scheduling */}
        <Card>
          <CardHeader>
            <CardTitle>Delivery Timing</CardTitle>
            <CardDescription>
              Choose when to send the notification
            </CardDescription>
          </CardHeader>
          <CardContent>
            <div className="flex items-center space-x-2">
              <Checkbox
                id="scheduled"
                checked={isScheduled}
                onCheckedChange={(checked) => setIsScheduled(checked as boolean)}
              />
              <label htmlFor="scheduled" className="text-sm font-medium">
                Schedule for later
              </label>
            </div>

            {isScheduled && (
              <div className="mt-4">
                <label className="block text-sm font-medium text-gray-700 mb-1">
                  Scheduled Date & Time
                </label>
                <Input
                  type="datetime-local"
                  value={scheduledFor}
                  onChange={(e) => setScheduledFor(e.target.value)}
                  min={new Date().toISOString().slice(0, 16)}
                />
              </div>
            )}
          </CardContent>
        </Card>

        {/* Notification Preview */}
        {(title || body) && (
          <Card>
            <CardHeader>
              <CardTitle>Preview</CardTitle>
              <CardDescription>
                How your notification will appear to recipients
              </CardDescription>
            </CardHeader>
            <CardContent>
              <NotificationPreview
                title={title}
                body={body}
                level={notificationLevel}
                actionText={actionText}
                actionUrl={actionUrl}
                channels={channels}
              />
            </CardContent>
          </Card>
        )}

        {/* Submit Buttons */}
        <div className="flex justify-end space-x-4">
          <Button
            type="button"
            variant="outline"
            onClick={() => router.visit('/admin/notifications')}
          >
            Cancel
          </Button>
          <Button type="submit" className="bg-teal-600 hover:bg-teal-700">
            {isEditing 
              ? 'Update Notification' 
              : (isScheduled ? 'Schedule Notification' : 'Send Notification')
            }
          </Button>
        </div>
      </form>
    </div>
  );
}
