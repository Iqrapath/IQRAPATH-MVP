import { useState, useEffect } from 'react';
import { Head, useForm, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { PlusCircle, X, Search, Loader2 } from 'lucide-react';
import axios from 'axios';
import debounce from 'lodash/debounce';

interface Template {
  id: number;
  name: string;
  title: string;
  body: string;
  type: string;
  placeholders?: string[];
}

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  avatar?: string;
}

interface NotificationCreateProps {
  templates: Template[];
  roles: string[];
}

export default function NotificationCreate({ templates, roles }: NotificationCreateProps) {
  const [allUsers, setAllUsers] = useState(true);
  const [selectedRecipientType, setSelectedRecipientType] = useState<string>('all');
  const [inAppEnabled, setInAppEnabled] = useState(true);
  const [emailEnabled, setEmailEnabled] = useState(false);
  const [smsEnabled, setSmsEnabled] = useState(false);
  const [allChannelsEnabled, setAllChannelsEnabled] = useState(false);
  const [sendNow, setSendNow] = useState(true);
  const [scheduleForLater, setScheduleForLater] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState<string>('none');
  const [availablePlaceholders, setAvailablePlaceholders] = useState<string[]>([]);
  const [selectedRoles, setSelectedRoles] = useState<string[]>([]);
  
  // User search state
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<User[]>([]);
  const [selectedUsers, setSelectedUsers] = useState<User[]>([]);
  const [isSearching, setIsSearching] = useState(false);
  
  const { data, setData, post, processing } = useForm({
    title: 'Payment Successful – "Jazakallah Khair!"',
    body: 'Hello [Student_Name],\n\nYour subscription for [Plan_Name] has been successfully processed.\n\nAmount: [Amount_Paid]\nDate: [Date]\n\nYour Teacher will contact you soon, In Shaa Allah.',
    type: 'payment',
    recipient_type: 'all',
    roles: [] as string[],
    user_ids: [] as number[],
    channels: ['in-app'],
    scheduled_at: null as Date | null,
  });
  
  // Handle recipient type change
  const handleRecipientTypeChange = (value: string) => {
    setSelectedRecipientType(value);
    setData('recipient_type', value);
    
    // Reset user selections when changing recipient type
    if (value === 'role') {
      setData('user_ids', []);
    } else if (value === 'specific') {
      setData('roles', []);
    }
  };
  
  // Handle role selection
  const handleRoleChange = (role: string, checked: boolean) => {
    const updatedRoles = checked 
      ? [...selectedRoles, role]
      : selectedRoles.filter(r => r !== role);
    
    setSelectedRoles(updatedRoles);
    setData('roles', updatedRoles);
  };
  
  // Handle all users toggle change
  const handleAllUsersChange = (checked: boolean) => {
    setAllUsers(checked);
    if (checked) {
      setData('recipient_type', 'all');
      setSelectedUsers([]);
      setData('user_ids', []);
      setSelectedRoles([]);
      setData('roles', []);
    } else {
      setData('recipient_type', selectedRecipientType);
    }
  };
  
  // Handle user search
  const searchUsers = debounce(async (query: string) => {
    if (query.length < 2) {
      setSearchResults([]);
      return;
    }
    
    setIsSearching(true);
    setSearchResults([]);
    
    try {
      const response = await axios.get(route('admin.notification.search-users'), {
        params: { query }
      });
      
      console.log('Search response:', response.data);
      
      if (Array.isArray(response.data)) {
        setSearchResults(response.data);
      } else {
        console.error('Unexpected response format:', response.data);
        setSearchResults([]);
      }
    } catch (error) {
      console.error('Error searching users:', error);
      setSearchResults([]);
    } finally {
      setIsSearching(false);
    }
  }, 300);
  
  // Handle search input change
  const handleSearchInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const query = e.target.value;
    setSearchQuery(query);
    searchUsers(query);
  };
  
  // Handle user selection
  const handleUserSelect = (user: User) => {
    // Check if user is already selected
    if (!selectedUsers.some(u => u.id === user.id)) {
      const newSelectedUsers = [...selectedUsers, user];
      setSelectedUsers(newSelectedUsers);
      setData('user_ids', newSelectedUsers.map(u => u.id));
    }
    
    // Clear search
    setSearchQuery('');
    setSearchResults([]);
  };
  
  // Handle user removal
  const handleRemoveUser = (userId: number) => {
    const newSelectedUsers = selectedUsers.filter(user => user.id !== userId);
    setSelectedUsers(newSelectedUsers);
    setData('user_ids', newSelectedUsers.map(u => u.id));
  };
  
  // Handle template selection
  const handleTemplateChange = (value: string) => {
    setSelectedTemplate(value);
    
    if (value === 'none') {
      setAvailablePlaceholders([]);
      return;
    }
    
    const template = templates.find(t => t.id.toString() === value);
    if (template) {
      setData({
        ...data,
        title: template.title,
        body: template.body,
        type: template.type
      });
      
      // Set available placeholders
      setAvailablePlaceholders(template.placeholders || []);
    }
  };
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Update channels based on checkboxes
    const channels = [];
    if (inAppEnabled) channels.push('in-app');
    if (emailEnabled) channels.push('email');
    if (smsEnabled) channels.push('sms');
    
    setData('channels', channels);
    
    post(route('admin.notification.store'));
  };

  const createTestUser = async () => {
    try {
      const response = await axios.get(route('admin.notification.create-test-user'));
      console.log('Created test user:', response.data);
      alert(`Test user created: ${response.data.user.name} (${response.data.user.email})`);
    } catch (error) {
      console.error('Error creating test user:', error);
    }
  };
  
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: route('admin.notification.index') },
    { title: 'Create Notification', href: '#' },
  ];

  return (
    <AdminLayout pageTitle="Create Notification" showRightSidebar={false}>
      <Head title="Create Notification" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
            <h2 className="text-xl font-semibold border-b pb-3 mb-6">Notification Form Fields</h2>
        <Card>
          <CardContent className="p-6">
            
            <form onSubmit={handleSubmit} className="space-y-8">
              {/* Template Selection */}
              <div className="space-y-2">
                <div className="flex items-center justify-between">
                  <Label htmlFor="template" className="text-base font-medium">Choose Template</Label>
                  <Link href={route('admin.notification.templates.create')}>
                    <Button 
                      type="button" 
                      variant="outline" 
                      size="sm"
                      className="flex items-center gap-1 text-teal-600 border-teal-600 hover:bg-teal-50"
                    >
                      <PlusCircle className="w-4 h-4" />
                      <span>Create Template</span>
                    </Button>
                  </Link>
                </div>
                <Select 
                  value={selectedTemplate} 
                  onValueChange={handleTemplateChange}
                >
                  <SelectTrigger className="max-w-md">
                    <SelectValue placeholder="Select a template" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">Create from scratch</SelectItem>
                    {templates.map((template) => (
                      <SelectItem key={template.id} value={template.id.toString()}>
                        {template.name.replace('_', ' ').split(' ').map(word => 
                          word.charAt(0).toUpperCase() + word.slice(1)
                        ).join(' ')} ({template.type})
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
                <p className="text-sm text-muted-foreground">
                  Select a template to pre-fill the notification content
                </p>
                {availablePlaceholders.length > 0 && (
                  <div className="mt-2 p-2 bg-muted rounded-md">
                    <p className="text-sm font-medium">Available placeholders:</p>
                    <div className="flex flex-wrap gap-2 mt-1">
                      {availablePlaceholders.map((placeholder) => (
                        <span key={placeholder} className="px-2 py-1 bg-background text-xs rounded-md border">
                          [{placeholder}]
                        </span>
                      ))}
                    </div>
                  </div>
                )}
              </div>
              
              {/* Notification Title */}
              <div className="space-y-2">
                <Label htmlFor="title" className="text-base font-medium">Notification Title</Label>
                <Input 
                  id="title" 
                  value={data.title} 
                  onChange={(e) => setData('title', e.target.value)}
                  className="max-w-md"
                />
              </div>
              
              {/* Message Body */}
              <div className="space-y-2">
                <Label htmlFor="body" className="text-base font-medium">Message Body</Label>
                <Textarea 
                  id="body" 
                  value={data.body} 
                  onChange={(e) => setData('body', e.target.value)}
                  rows={8}
                  className="max-w-2xl"
                />
              </div>
              
              {/* Recipient Type */}
              <div className="space-y-4">
                <div className="flex items-center gap-4">
                  <div className="w-32">
                    <Label className="text-base font-medium">Recipient Type</Label>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={allUsers} 
                      onCheckedChange={handleAllUsersChange}
                    />
                    <span className="text-sm">All Users</span>
                  </div>
                </div>
                
                {allUsers ? (
                  <div className="flex items-center gap-4 ml-36">
                    <Select value="all" disabled>
                      <SelectTrigger className="w-[180px]">
                        <SelectValue placeholder="All Users" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectItem value="all">All Users</SelectItem>
                      </SelectContent>
                    </Select>
                  </div>
                ) : (
                  <div className="ml-36 space-y-4">
                    <div className="flex items-center gap-4">
                      <Select 
                        value={selectedRecipientType} 
                        onValueChange={handleRecipientTypeChange}
                      >
                        <SelectTrigger className="w-[180px]">
                          <SelectValue placeholder="Select recipient type" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectItem value="role">By Role</SelectItem>
                          <SelectItem value="specific">Specific Users</SelectItem>
                        </SelectContent>
                      </Select>
                    </div>
                    
                    {selectedRecipientType === 'role' && (
                      <div className="space-y-2">
                        <Label className="text-sm font-medium">Select Roles</Label>
                        <div className="grid grid-cols-2 gap-2">
                          {roles.map((role) => (
                            <div key={role} className="flex items-center space-x-2">
                              <Checkbox 
                                id={`role-${role}`} 
                                checked={selectedRoles.includes(role)}
                                onCheckedChange={(checked) => handleRoleChange(role, checked === true)}
                              />
                              <Label htmlFor={`role-${role}`} className="text-sm capitalize">
                                {role}
                              </Label>
                            </div>
                          ))}
                        </div>
                      </div>
                    )}
                    
                    {selectedRecipientType === 'specific' && (
                      <div className="space-y-4">
                        <div className="space-y-2">
                          <Label className="text-sm font-medium">Search Users</Label>
                          <div className="relative">
                            <div className="flex w-full max-w-md items-center space-x-2">
                              <div className="relative flex-1">
                                <Input 
                                  placeholder="Search by name or email" 
                                  value={searchQuery}
                                  onChange={handleSearchInputChange}
                                  className="pl-10 pr-4"
                                />
                                <div className="absolute left-3 top-2.5 text-muted-foreground">
                                  <Search className="h-4 w-4" />
                                </div>
                              </div>
                              <Button 
                                type="button" 
                                variant="outline"
                                size="sm"
                                onClick={() => searchUsers(searchQuery)}
                                disabled={isSearching || searchQuery.length < 2}
                              >
                                {isSearching ? (
                                  <>
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                    Searching...
                                  </>
                                ) : (
                                  <>Search</>
                                )}
                              </Button>
                            </div>
                          </div>
                          
                          {/* Search results */}
                          {searchResults.length > 0 ? (
                            <div className="mt-2 border rounded-md">
                              <div className="px-3 py-2 bg-muted text-sm font-medium border-b">
                                Search Results ({searchResults.length})
                              </div>
                              <div className="max-h-60 overflow-auto">
                                {searchResults.map((user) => (
                                  <div
                                    key={user.id}
                                    className="flex items-center px-3 py-2 border-b last:border-b-0 hover:bg-muted/50 cursor-pointer"
                                    onClick={() => handleUserSelect(user)}
                                  >
                                    <Avatar className="h-8 w-8 mr-3">
                                      <AvatarImage src={user.avatar} alt={user.name} />
                                      <AvatarFallback>{user.name.charAt(0).toUpperCase()}</AvatarFallback>
                                    </Avatar>
                                    <div className="flex-1">
                                      <div className="text-sm font-medium">{user.name}</div>
                                      <div className="text-xs text-muted-foreground">{user.email}</div>
                                    </div>
                                    {user.role && (
                                      <Badge variant="outline" className="ml-2 capitalize">
                                        {user.role}
                                      </Badge>
                                    )}
                                  </div>
                                ))}
                              </div>
                            </div>
                          ) : searchQuery.length >= 2 && !isSearching ? (
                            <div className="mt-2 p-4 border rounded-md text-center text-muted-foreground">
                              No users found matching "{searchQuery}"
                            </div>
                          ) : null}

                          {process.env.NODE_ENV === 'local' && (
                            <Button
                              type="button"
                              variant="outline"
                              size="sm"
                              onClick={createTestUser}
                              className="mt-2 text-xs"
                            >
                              Create Test User
                            </Button>
                          )}
                        </div>
                        
                        {/* Selected users */}
                        {selectedUsers.length > 0 && (
                          <div className="space-y-2 border rounded-md p-3">
                            <div className="flex items-center justify-between">
                              <Label className="text-sm font-medium">Selected Recipients ({selectedUsers.length})</Label>
                              <Button 
                                type="button" 
                                variant="ghost" 
                                size="sm"
                                onClick={() => {
                                  setSelectedUsers([]);
                                  setData('user_ids', []);
                                }}
                                className="h-8 px-2 text-xs text-muted-foreground hover:text-destructive"
                              >
                                Clear All
                              </Button>
                            </div>
                            <div className="flex flex-wrap gap-2 mt-2">
                              {selectedUsers.map((user) => (
                                <Badge 
                                  key={user.id} 
                                  variant="secondary"
                                  className="flex items-center gap-1 pl-1 pr-1 py-1"
                                >
                                  <Avatar className="h-5 w-5 mr-1">
                                    <AvatarImage src={user.avatar} alt={user.name} />
                                    <AvatarFallback className="text-xs">{user.name.charAt(0).toUpperCase()}</AvatarFallback>
                                  </Avatar>
                                  <span className="text-xs max-w-[120px] truncate">{user.name}</span>
                                  <button 
                                    type="button" 
                                    onClick={() => handleRemoveUser(user.id)}
                                    className="ml-1 rounded-full p-0.5 text-muted-foreground hover:bg-muted hover:text-foreground"
                                  >
                                    <X className="h-3 w-3" />
                                  </button>
                                </Badge>
                              ))}
                            </div>
                          </div>
                        )}
                      </div>
                    )}
                  </div>
                )}
              </div>
              
              {/* Send As */}
              <div className="flex items-center gap-4">
                <div className="w-32">
                  <Label className="text-base font-medium">Send As:</Label>
                </div>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={inAppEnabled} 
                      onCheckedChange={setInAppEnabled}
                    />
                    <span className="text-sm">In-App</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={emailEnabled} 
                      onCheckedChange={setEmailEnabled}
                    />
                    <span className="text-sm">Email</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={smsEnabled} 
                      onCheckedChange={setSmsEnabled}
                    />
                    <span className="text-sm">SMS</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={allChannelsEnabled} 
                      onCheckedChange={(checked) => {
                        setAllChannelsEnabled(checked);
                        if (checked) {
                          setInAppEnabled(true);
                          setEmailEnabled(true);
                          setSmsEnabled(true);
                        }
                      }}
                    />
                    <span className="text-sm">All</span>
                  </div>
                </div>
              </div>
              
              {/* Schedule Delivery Time */}
              <div className="flex items-center gap-4">
                <div className="w-32">
                  <Label className="text-base font-medium">Schedule Delivery Time:</Label>
                </div>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={sendNow} 
                      onCheckedChange={(checked) => {
                        setSendNow(checked);
                        setScheduleForLater(!checked);
                      }}
                    />
                    <span className="text-sm">Send Now</span>
                  </div>
                  <div className="flex items-center gap-2">
                    <Switch 
                      checked={scheduleForLater} 
                      onCheckedChange={(checked) => {
                        setScheduleForLater(checked);
                        setSendNow(!checked);
                      }}
                    />
                    <span className="text-sm">Schedule for Later</span>
                  </div>
                  {scheduleForLater && (
                    <div className="ml-2">
                      <Input 
                        type="datetime-local"
                        onChange={(e) => setData('scheduled_at', new Date(e.target.value))}
                      />
                    </div>
                  )}
                </div>
              </div>
              
              {/* Buttons */}
              <div className="flex gap-4 pt-4">
                <Button 
                  type="button" 
                  variant="outline" 
                  onClick={() => window.history.back()}
                >
                  Cancel
                </Button>
                <Button 
                  type="submit" 
                  className="bg-teal-600 hover:bg-teal-700"
                  disabled={processing}
                >
                  Send Notification
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
} 