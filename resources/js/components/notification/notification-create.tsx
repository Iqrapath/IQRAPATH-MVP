import React, { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Search, Loader2, X, ArrowLeft } from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import axios from 'axios';
import debounce from 'lodash/debounce';
import { useInitials } from '@/hooks/use-initials';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  avatar?: string;
}

interface Template {
  id: number;
  name: string;
  title: string;
  body: string;
  type: string;
}

interface NotificationCreateProps {
  userRole: 'teacher' | 'student' | 'guardian' | 'admin';
  templates?: Template[];
  preselectedUsers?: User[];
  defaultType?: string;
  defaultTitle?: string;
  defaultBody?: string;
  isMessage?: boolean;
  onCancel?: () => void;
  backUrl?: string;
  submitEndpoint: string;
}

export default function NotificationCreate({
  userRole,
  templates = [],
  preselectedUsers = [],
  defaultType = 'message',
  defaultTitle = '',
  defaultBody = '',
  isMessage = false,
  onCancel,
  backUrl = '/notifications',
  submitEndpoint
}: NotificationCreateProps) {
  const getInitials = useInitials();
  const [searchQuery, setSearchQuery] = useState<string>('');
  const [searchResults, setSearchResults] = useState<User[]>([]);
  const [selectedUsers, setSelectedUsers] = useState<User[]>(preselectedUsers);
  const [isSearching, setIsSearching] = useState(false);
  const [selectedTemplate, setSelectedTemplate] = useState<string>('none');
  
  const { data, setData, post, processing, errors } = useForm({
    title: defaultTitle,
    body: defaultBody,
    type: defaultType,
    recipient_ids: preselectedUsers.map(u => u.id),
    is_message: isMessage
  });
  
  // Handle template selection
  const handleTemplateChange = (value: string) => {
    setSelectedTemplate(value);
    
    if (value === 'none') {
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
      const response = await axios.get(`/api/search-users`, {
        params: { query, role: userRole === 'admin' ? null : userRole }
      });
      
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
      setData('recipient_ids', newSelectedUsers.map(u => u.id));
    }
    
    // Clear search
    setSearchQuery('');
    setSearchResults([]);
  };
  
  // Handle user removal
  const handleRemoveUser = (userId: number) => {
    const newSelectedUsers = selectedUsers.filter(user => user.id !== userId);
    setSelectedUsers(newSelectedUsers);
    setData('recipient_ids', newSelectedUsers.map(u => u.id));
  };
  
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
          <h1 className="text-2xl font-bold text-gray-800">
            {isMessage ? 'Compose Message' : 'Create Notification'}
          </h1>
        </div>
      </div>
      
      <Card>
        <CardContent className="p-6">
          <form onSubmit={handleSubmit} className="space-y-6">
            {/* Templates - Only show if templates are provided */}
            {templates.length > 0 && (
              <div className="space-y-2">
                <Label htmlFor="template" className="text-base font-medium">Use Template</Label>
                <Select value={selectedTemplate} onValueChange={handleTemplateChange}>
                  <SelectTrigger className="max-w-md">
                    <SelectValue placeholder="Select a template" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="none">No Template</SelectItem>
                    {templates.map((template) => (
                      <SelectItem key={template.id} value={template.id.toString()}>
                        {template.name}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
            )}
            
            {/* Message Type - Only show if it's not a message */}
            {!isMessage && (
              <div className="space-y-2">
                <Label htmlFor="type" className="text-base font-medium">Notification Type</Label>
                <Select 
                  value={data.type} 
                  onValueChange={(value) => setData('type', value)}
                >
                  <SelectTrigger className="max-w-md">
                    <SelectValue placeholder="Select a type" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="message">Message</SelectItem>
                    <SelectItem value="alert">Alert</SelectItem>
                    <SelectItem value="system">System</SelectItem>
                    {userRole === 'admin' && (
                      <>
                        <SelectItem value="payment">Payment</SelectItem>
                        <SelectItem value="session">Session</SelectItem>
                      </>
                    )}
                  </SelectContent>
                </Select>
                {errors.type && (
                  <p className="text-sm text-red-500 mt-1">{errors.type}</p>
                )}
              </div>
            )}
            
            {/* Recipients */}
            <div className="space-y-2">
              <Label htmlFor="recipients" className="text-base font-medium">Recipients</Label>
              <div className="space-y-4">
                {/* Search input */}
                <div className="relative max-w-md">
                  <Input
                    id="search-users"
                    placeholder="Search users..."
                    value={searchQuery}
                    onChange={handleSearchInputChange}
                    className="pr-8"
                  />
                  <div className="absolute right-2 top-1/2 -translate-y-1/2">
                    {isSearching ? (
                      <Loader2 className="h-4 w-4 animate-spin text-muted-foreground" />
                    ) : (
                      <Search className="h-4 w-4 text-muted-foreground" />
                    )}
                  </div>
                </div>
                
                {/* Search results */}
                {searchResults.length > 0 && (
                  <div className="border rounded-md max-w-md max-h-60 overflow-y-auto">
                    <div className="p-2 text-sm text-muted-foreground border-b">
                      Search Results
                    </div>
                    <ul className="divide-y">
                      {searchResults.map((user) => (
                        <li 
                          key={user.id} 
                          className="p-2 hover:bg-muted cursor-pointer"
                          onClick={() => handleUserSelect(user)}
                        >
                          <div className="flex items-center gap-2">
                            <Avatar className="h-8 w-8">
                              {user.avatar ? (
                                <AvatarImage src={user.avatar} alt={user.name} />
                              ) : (
                                <AvatarFallback className="bg-primary text-white">
                                  {getInitials(user.name)}
                                </AvatarFallback>
                              )}
                            </Avatar>
                            <div>
                              <div className="font-medium">{user.name}</div>
                              <div className="text-xs text-muted-foreground">{user.email}</div>
                            </div>
                          </div>
                        </li>
                      ))}
                    </ul>
                  </div>
                )}
                
                {/* Selected users */}
                <div>
                  <div className="text-sm font-medium mb-2">Selected Recipients</div>
                  {selectedUsers.length === 0 ? (
                    <div className="text-sm text-muted-foreground">No recipients selected</div>
                  ) : (
                    <div className="flex flex-wrap gap-2">
                      {selectedUsers.map((user) => (
                        <Badge 
                          key={user.id} 
                          variant="secondary"
                          className="flex items-center gap-1 pl-1 pr-2 py-1"
                        >
                          <Avatar className="h-5 w-5 mr-1">
                            {user.avatar ? (
                              <AvatarImage src={user.avatar} alt={user.name} />
                            ) : (
                              <AvatarFallback className="bg-primary text-white text-[10px]">
                                {getInitials(user.name)}
                              </AvatarFallback>
                            )}
                          </Avatar>
                          {user.name}
                          <button 
                            type="button" 
                            onClick={() => handleRemoveUser(user.id)}
                            className="ml-1 text-muted-foreground hover:text-foreground"
                          >
                            <X className="h-3 w-3" />
                          </button>
                        </Badge>
                      ))}
                    </div>
                  )}
                </div>
                
                {errors.recipient_ids && (
                  <p className="text-sm text-red-500 mt-1">{errors.recipient_ids}</p>
                )}
              </div>
            </div>
            
            {/* Title */}
            <div className="space-y-2">
              <Label htmlFor="title" className="text-base font-medium">
                {isMessage ? 'Subject' : 'Notification Title'}
              </Label>
              <Input 
                id="title" 
                value={data.title} 
                onChange={(e) => setData('title', e.target.value)}
                className="max-w-md"
              />
              {errors.title && (
                <p className="text-sm text-red-500 mt-1">{errors.title}</p>
              )}
            </div>
            
            {/* Message Body */}
            <div className="space-y-2">
              <Label htmlFor="body" className="text-base font-medium">Message</Label>
              <Textarea 
                id="body" 
                value={data.body} 
                onChange={(e) => setData('body', e.target.value)}
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
                disabled={processing || selectedUsers.length === 0} 
                className="bg-teal-600 hover:bg-teal-700"
              >
                {processing && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
                {isMessage ? 'Send Message' : 'Send Notification'}
              </Button>
            </div>
          </form>
        </CardContent>
      </Card>
    </div>
  );
} 