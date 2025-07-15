import { useState, useEffect } from 'react';
import { Head, useForm, router, Link } from '@inertiajs/react';
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
import { PlusCircle, X, Search, Loader2, AlertCircle } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import axios from 'axios';
import debounce from 'lodash/debounce';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  avatar?: string;
}

interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  scheduled_at: string | null;
  created_at: string;
}

interface NotificationEditProps {
  notification: Notification;
  recipientType: string;
  roles: string[];
  userIds: number[];
  channels: string[];
  allRoles: string[];
  selectedUsers: User[];
}

export default function NotificationEdit({ 
  notification, 
  recipientType, 
  roles: initialRoles, 
  userIds,
  channels: selectedChannels,
  allRoles,
  selectedUsers: initialSelectedUsers
}: NotificationEditProps) {
  const [allUsers, setAllUsers] = useState(recipientType === 'all');
  const [selectedRecipientType, setSelectedRecipientType] = useState<string>(recipientType);
  const [inAppEnabled, setInAppEnabled] = useState(selectedChannels.includes('in-app'));
  const [emailEnabled, setEmailEnabled] = useState(selectedChannels.includes('email'));
  const [smsEnabled, setSmsEnabled] = useState(selectedChannels.includes('sms'));
  const [allChannelsEnabled, setAllChannelsEnabled] = useState(
    inAppEnabled && emailEnabled && smsEnabled
  );
  const [sendNow, setSendNow] = useState(!notification.scheduled_at);
  const [scheduleForLater, setScheduleForLater] = useState(!!notification.scheduled_at);
  const [selectedRoles, setSelectedRoles] = useState<string[]>(initialRoles);
  const [dateError, setDateError] = useState<string | null>(null);
  
  // User search state
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<User[]>([]);
  const [selectedUsers, setSelectedUsers] = useState<User[]>(initialSelectedUsers || []);
  const [isSearching, setIsSearching] = useState(false);
  
  const { data, setData, put, processing } = useForm({
    title: notification.title,
    body: notification.body,
    type: notification.type,
    status: notification.status,
    recipient_type: recipientType,
    roles: initialRoles,
    user_ids: userIds,
    channels: selectedChannels,
    scheduled_at: notification.scheduled_at ? new Date(notification.scheduled_at) : null as Date | null,
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
  
  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    
    // Update channels based on switches
    const channels = [];
    if (inAppEnabled) channels.push('in-app');
    if (emailEnabled) channels.push('email');
    if (smsEnabled) channels.push('sms');
    
    setData('channels', channels);
    
    // Validate scheduled date is not in the past
    if (scheduleForLater && data.scheduled_at) {
      const scheduledDate = new Date(data.scheduled_at);
      const now = new Date();
      
      if (scheduledDate <= now) {
        setDateError("Cannot schedule notifications in the past. Please select a future date and time.");
        return;
      }
    }
    
    setDateError(null);
    put(route('admin.notification.update', notification.id));
  };
  
  const handleSendNow = () => {
    router.post(route('admin.notification.send', notification.id));
  };
  
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: route('admin.dashboard') },
    { title: 'Notifications', href: route('admin.notification.index') },
    { title: 'Edit Notification', href: '#' },
  ];

  return (
    <AdminLayout pageTitle="Edit Notification" showRightSidebar={false}>
      <Head title="Edit Notification" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        <h2 className="text-xl font-semibold border-b pb-3 mb-6">Edit Notification</h2>
        <Card>
          <CardContent className="p-6">
            
            <form onSubmit={handleSubmit} className="space-y-8">
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
              
              {/* Status */}
              <div className="space-y-2">
                <Label htmlFor="status" className="text-base font-medium">Status</Label>
                <Select 
                  value={data.status} 
                  onValueChange={(value) => setData('status', value)}
                >
                  <SelectTrigger className="max-w-md">
                    <SelectValue placeholder="Select status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="draft">Draft</SelectItem>
                    <SelectItem value="scheduled">Scheduled</SelectItem>
                    <SelectItem value="sent">Sent</SelectItem>
                    <SelectItem value="delivered">Delivered</SelectItem>
                    <SelectItem value="read">Read</SelectItem>
                    <SelectItem value="failed">Failed</SelectItem>
                  </SelectContent>
                </Select>
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
                          {allRoles.map((role) => (
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
                <div className="flex flex-col gap-2">
                  <div className="flex items-center gap-6">
                    <div className="flex items-center gap-2">
                      <Switch 
                        checked={sendNow} 
                        onCheckedChange={(checked) => {
                          setSendNow(checked);
                          setScheduleForLater(!checked);
                          if (checked) {
                            setData('scheduled_at', null);
                            setDateError(null);
                          }
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
                          if (!checked) {
                            setDateError(null);
                          }
                        }}
                      />
                      <span className="text-sm">Schedule for Later</span>
                    </div>
                    {scheduleForLater && (
                      <div className="ml-2">
                        <Input 
                          type="datetime-local"
                          defaultValue={notification.scheduled_at ? new Date(notification.scheduled_at).toISOString().slice(0, 16) : undefined}
                          onChange={(e) => {
                            const selectedDate = new Date(e.target.value);
                            const now = new Date();
                            
                            if (selectedDate <= now) {
                              setDateError("Cannot schedule notifications in the past. Please select a future date and time.");
                            } else {
                              setDateError(null);
                              setData('scheduled_at', selectedDate);
                            }
                          }}
                          className={dateError ? "border-red-500" : ""}
                        />
                      </div>
                    )}
                  </div>
                  
                  {dateError && (
                    <Alert variant="destructive" className="mt-2">
                      <AlertCircle className="h-4 w-4" />
                      <AlertDescription>
                        {dateError}
                      </AlertDescription>
                    </Alert>
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
                  Update Notification
                </Button>
                {notification.status === 'draft' && (
                  <Button 
                    type="button" 
                    onClick={handleSendNow}
                    className="bg-blue-600 hover:bg-blue-700"
                  >
                    Send Now
                  </Button>
                )}
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AdminLayout>
  );
} 