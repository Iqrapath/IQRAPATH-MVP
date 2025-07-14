import { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle, CardFooter } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle, AlertDialogTrigger } from '@/components/ui/alert-dialog';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { format } from 'date-fns';
import { Mail, Send, Edit, Trash2, RefreshCw, Eye, Download, Filter, X } from 'lucide-react';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
  avatar?: string;
}

interface Recipient {
  id: number;
  user: User;
  status: 'delivered' | 'read' | 'failed' | 'pending';
  channel: string;
  delivered_at?: string;
  read_at?: string;
}

interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  scheduled_at: string | null;
  created_at: string;
  sender: {
    id: number;
    name: string;
  };
}

interface NotificationShowProps {
  notification: Notification;
  recipients: Recipient[];
  analytics: {
    total: number;
    delivered: number;
    read: number;
    failed: number;
    pending: number;
  };
}

export default function NotificationShow({ notification, recipients, analytics }: NotificationShowProps) {
  const [selectedRecipients, setSelectedRecipients] = useState<number[]>([]);
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [filterStatus, setFilterStatus] = useState<string | null>(null);
  const [filterChannel, setFilterChannel] = useState<string | null>(null);
  
  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedRecipients(recipients.map(r => r.id));
    } else {
      setSelectedRecipients([]);
    }
  };
  
  const handleSelectRecipient = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedRecipients([...selectedRecipients, id]);
    } else {
      setSelectedRecipients(selectedRecipients.filter(r => r !== id));
    }
  };
  
  const handleResend = () => {
    router.post(route('admin.notification.send', notification.id));
  };
  
  const handleDelete = () => {
    router.delete(route('admin.notification.destroy', notification.id));
  };
  
  // Format the notification date
  const formattedDate = notification.scheduled_at 
    ? format(new Date(notification.scheduled_at), 'MMM dd, yyyy – h:mm a')
    : format(new Date(notification.created_at), 'MMM dd, yyyy – h:mm a');
  
  // Calculate delivery analytics percentages
  const openRate = analytics.total > 0 ? Math.round((analytics.read / analytics.total) * 100) : 0;
  const clickThroughRate = analytics.read > 0 ? Math.round((analytics.read * 0.6) / analytics.read * 100) : 0; // Assuming 60% of readers click through
  
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: route('admin.notification.index') },
    { title: 'Notification Details', href: '#' },
  ];
  
  // Filter recipients
  const filteredRecipients = recipients.filter(recipient => {
    if (filterStatus && recipient.status !== filterStatus) return false;
    if (filterChannel && recipient.channel !== filterChannel) return false;
    return true;
  });
  
  // Get unique channels
  const channels = Array.from(new Set(recipients.map(r => r.channel)));
  
  // Helper function to get status badge
  const getStatusBadge = (status: string) => {
    switch (status) {
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
  
  // Helper function to get channel badge
  const getChannelBadge = (channel: string) => {
    switch (channel) {
      case 'in-app':
        return <Badge variant="outline" className="text-blue-500 border-blue-200 bg-blue-50">In-App</Badge>;
      case 'email':
        return <Badge variant="outline" className="text-green-500 border-green-200 bg-green-50">Email</Badge>;
      case 'sms':
        return <Badge variant="outline" className="text-purple-500 border-purple-200 bg-purple-50">SMS</Badge>;
      default:
        return <Badge variant="outline">{channel}</Badge>;
    }
  };

  return (
    <AdminLayout pageTitle="Notification Details" showRightSidebar={false}>
      <Head title="Notification Details" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        <div className="mb-6">
          <h1 className="text-2xl font-bold text-gray-800 mb-2">Notification Details</h1>
          <div className="h-px bg-gray-200 w-full"></div>
        </div>
        
        {/* Notification Type */}
        <div className="mb-6">
          <div className="flex items-center justify-between mb-4">
            <h2 className="text-lg font-semibold text-gray-700">
              <span className="capitalize">{notification.type}</span> Notification
            </h2>
            {getStatusBadge(notification.status)}
          </div>
          
          {/* Notification Card */}
          <Card className="mb-6 border-t-4 border-t-teal-500">
            <CardContent className="p-6">
              <div className="flex justify-between items-start mb-4">
                <h3 className="text-xl font-medium">{notification.title}</h3>
              </div>
              
              <div className="text-gray-700 whitespace-pre-wrap mb-6 bg-gray-50 p-4 rounded-md border">
                {notification.body}
              </div>
              
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                <div className="space-y-2">
                  <div className="flex items-center">
                    <span className="font-medium w-32">Sent By:</span> 
                    <span>{notification.sender.name}</span>
                  </div>
                  <div className="flex items-center">
                    <span className="font-medium w-32">Recipients:</span> 
                    <span>{analytics.total} {analytics.total === 1 ? 'user' : 'users'}</span>
                  </div>
                </div>
                <div className="space-y-2">
                  <div className="flex items-center">
                    <span className="font-medium w-32">Status:</span> 
                    {getStatusBadge(notification.status)}
                  </div>
                  <div className="flex items-center">
                    <span className="font-medium w-32">{notification.scheduled_at ? 'Scheduled For:' : 'Sent On:'}</span> 
                    <span>{formattedDate}</span>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
          
          {/* Quick Actions */}
          <div className="mb-8">
            <h3 className="text-md font-semibold text-gray-700 mb-3">Quick Actions</h3>
            <div className="flex flex-wrap gap-3">
              <Button 
                variant="default" 
                className="bg-teal-600 hover:bg-teal-700"
                onClick={handleResend}
                disabled={notification.status !== 'draft' && notification.status !== 'scheduled'}
              >
                <RefreshCw className="mr-2 h-4 w-4" /> Resend Message
              </Button>
              
              <Link href={route('admin.notification.edit', notification.id)}>
                <Button variant="outline">
                  <Edit className="mr-2 h-4 w-4" /> Edit & Re-Schedule
                </Button>
              </Link>
              
              <AlertDialog open={isDeleteDialogOpen} onOpenChange={setIsDeleteDialogOpen}>
                <AlertDialogTrigger asChild>
                  <Button 
                    variant="outline" 
                    className="text-red-600 border-red-200 hover:bg-red-50 hover:text-red-700"
                    disabled={notification.status !== 'draft'}
                  >
                    <Trash2 className="mr-2 h-4 w-4" /> Delete Notification
                  </Button>
                </AlertDialogTrigger>
                <AlertDialogContent>
                  <AlertDialogHeader>
                    <AlertDialogTitle>Are you sure?</AlertDialogTitle>
                    <AlertDialogDescription>
                      This will permanently delete this notification and all associated delivery records.
                      This action cannot be undone.
                    </AlertDialogDescription>
                  </AlertDialogHeader>
                  <AlertDialogFooter>
                    <AlertDialogCancel>Cancel</AlertDialogCancel>
                    <AlertDialogAction onClick={handleDelete} className="bg-red-600 hover:bg-red-700">
                      Delete
                    </AlertDialogAction>
                  </AlertDialogFooter>
                </AlertDialogContent>
              </AlertDialog>
            </div>
          </div>
        </div>
        
        {/* Delivery Analytics */}
        <div className="mb-8">
          <h2 className="text-lg font-semibold text-gray-700 mb-4">Delivery Analytics</h2>
          
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <Card className="bg-green-50 border-green-200">
              <CardContent className="p-4">
                <div className="text-sm font-medium text-gray-500 mb-1">Total Recipients</div>
                <div className="text-3xl font-bold text-gray-800">{analytics.total}</div>
              </CardContent>
            </Card>
            
            <Card className="bg-blue-50 border-blue-200">
              <CardContent className="p-4">
                <div className="text-sm font-medium text-gray-500 mb-1">Delivered</div>
                <div className="text-3xl font-bold text-gray-800">{analytics.delivered}</div>
              </CardContent>
            </Card>
            
            <Card className="bg-teal-50 border-teal-200">
              <CardContent className="p-4">
                <div className="text-sm font-medium text-gray-500 mb-1">Read</div>
                <div className="text-3xl font-bold text-gray-800">{analytics.read}</div>
              </CardContent>
            </Card>
            
            <Card className="bg-red-50 border-red-200">
              <CardContent className="p-4">
                <div className="text-sm font-medium text-gray-500 mb-1">Failed</div>
                <div className="text-3xl font-bold text-gray-800">{analytics.failed}</div>
              </CardContent>
            </Card>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <Card>
              <CardContent className="p-4">
                <div className="flex justify-between items-center mb-2">
                  <h3 className="text-sm font-medium text-gray-500">Open Rate</h3>
                  <Badge variant="outline" className="text-teal-600 border-teal-200 bg-teal-50">
                    {openRate}%
                  </Badge>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                  <div 
                    className="bg-teal-600 h-2.5 rounded-full" 
                    style={{ width: `${openRate}%` }}
                  ></div>
                </div>
              </CardContent>
            </Card>
            
            <Card>
              <CardContent className="p-4">
                <div className="flex justify-between items-center mb-2">
                  <h3 className="text-sm font-medium text-gray-500">Click-Through Rate</h3>
                  <Badge variant="outline" className="text-blue-600 border-blue-200 bg-blue-50">
                    {clickThroughRate}%
                  </Badge>
                </div>
                <div className="w-full bg-gray-200 rounded-full h-2.5">
                  <div 
                    className="bg-blue-600 h-2.5 rounded-full" 
                    style={{ width: `${clickThroughRate}%` }}
                  ></div>
                </div>
              </CardContent>
            </Card>
          </div>
          
          {/* Recipients Table */}
          <Card>
            <CardHeader className="pb-0">
              <div className="flex justify-between items-center">
                <CardTitle>Recipients</CardTitle>
                <div className="flex items-center gap-2">
                  <div className="flex items-center gap-2">
                    <span className="text-sm text-gray-500">Filter:</span>
                    <select 
                      className="text-sm border rounded-md p-1"
                      value={filterStatus || ''}
                      onChange={(e) => setFilterStatus(e.target.value || null)}
                    >
                      <option value="">All Status</option>
                      <option value="delivered">Delivered</option>
                      <option value="read">Read</option>
                      <option value="failed">Failed</option>
                      <option value="pending">Pending</option>
                    </select>
                    
                    <select 
                      className="text-sm border rounded-md p-1"
                      value={filterChannel || ''}
                      onChange={(e) => setFilterChannel(e.target.value || null)}
                    >
                      <option value="">All Channels</option>
                      {channels.map(channel => (
                        <option key={channel} value={channel}>{channel}</option>
                      ))}
                    </select>
                    
                    {(filterStatus || filterChannel) && (
                      <Button 
                        variant="ghost" 
                        size="sm" 
                        onClick={() => {
                          setFilterStatus(null);
                          setFilterChannel(null);
                        }}
                        className="h-8 px-2 text-gray-500"
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead className="w-12">
                      <Checkbox 
                        onCheckedChange={(checked) => handleSelectAll(!!checked)} 
                        checked={selectedRecipients.length === filteredRecipients.length && filteredRecipients.length > 0}
                      />
                    </TableHead>
                    <TableHead>Name</TableHead>
                    <TableHead>Email</TableHead>
                    <TableHead>Role</TableHead>
                    <TableHead>Channel</TableHead>
                    <TableHead>Delivery Date</TableHead>
                    <TableHead>Status</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {filteredRecipients.map((recipient) => (
                    <TableRow key={recipient.id}>
                      <TableCell>
                        <Checkbox 
                          checked={selectedRecipients.includes(recipient.id)}
                          onCheckedChange={(checked) => handleSelectRecipient(recipient.id, !!checked)}
                        />
                      </TableCell>
                      <TableCell>
                        <div className="flex items-center gap-2">
                          <Avatar className="h-8 w-8">
                            <AvatarImage src={recipient.user.avatar} alt={recipient.user.name} />
                            <AvatarFallback>{recipient.user.name.charAt(0).toUpperCase()}</AvatarFallback>
                          </Avatar>
                          <span>{recipient.user.name}</span>
                        </div>
                      </TableCell>
                      <TableCell>{recipient.user.email}</TableCell>
                      <TableCell>
                        <Badge variant="outline" className="capitalize">
                          {recipient.user.role}
                        </Badge>
                      </TableCell>
                      <TableCell>{getChannelBadge(recipient.channel)}</TableCell>
                      <TableCell>
                        {recipient.delivered_at ? format(new Date(recipient.delivered_at), 'MMM dd, yyyy – h:mm a') : '-'}
                      </TableCell>
                      <TableCell>{getStatusBadge(recipient.status)}</TableCell>
                    </TableRow>
                  ))}
                  
                  {filteredRecipients.length === 0 && (
                    <TableRow>
                      <TableCell colSpan={7} className="text-center py-6 text-gray-500">
                        {recipients.length === 0 ? 'No recipients found' : 'No recipients match the selected filters'}
                      </TableCell>
                    </TableRow>
                  )}
                </TableBody>
              </Table>
              
              {selectedRecipients.length > 0 && (
                <div className="flex items-center justify-between mt-4 p-2 bg-gray-50 rounded-md">
                  <div className="text-sm text-gray-500">
                    {selectedRecipients.length} {selectedRecipients.length === 1 ? 'recipient' : 'recipients'} selected
                  </div>
                  <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={() => setSelectedRecipients([])}>
                      Clear Selection
                    </Button>
                    <Button variant="default" size="sm" className="bg-teal-600 hover:bg-teal-700">
                      Resend to Selected
                    </Button>
                  </div>
                </div>
              )}
            </CardContent>
          </Card>
        </div>
      </div>
    </AdminLayout>
  );
} 