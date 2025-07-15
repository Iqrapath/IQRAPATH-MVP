import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { useState, useEffect } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { Eye, Pencil } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import { toast } from 'sonner';

// Add debug component to check if user ID is available
const DebugUserID = () => {
  useEffect(() => {
    const metaTag = document.querySelector('meta[name="user-id"]');
    console.log('User ID Meta Tag:', metaTag);
    console.log('User ID Content:', metaTag?.getAttribute('content'));
    
    // Check if we're in an iframe
    console.log('Is in iframe:', window !== window.parent);
    
    // Check all meta tags
    const allMeta = document.querySelectorAll('meta');
    console.log('All meta tags:', allMeta);
  }, []);
  
  return null;
};

interface Notification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  created_at: string;
}

interface NotificationProps {
  notifications: {
    data: Notification[];
    links: any;
    meta: {
      current_page: number;
      from: number;
      last_page: number;
      links: any[];
      path: string;
      per_page: number;
      to: number;
      total: number;
    };
  };
  filters?: {
    search?: string;
    type?: string;
    status?: string;
  };
}

export default function NotificationPage({ notifications: initialNotifications, filters = {} }: NotificationProps) {
  const [notifications, setNotifications] = useState(initialNotifications);
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');
  const [subject, setSubject] = useState('');
  const [selectedItems, setSelectedItems] = useState<number[]>([]);

  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications System', href: '/admin/notification' },
  ];

  // Set up real-time notification listeners - simplified implementation
  useEffect(() => {
    // Try to get user ID from meta tag first
    let userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
    
    // If not found, try to get from Inertia page props
    // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
    if (!userId && window.Inertia?.page?.props?.auth?.user?.id) {
      // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
      userId = window.Inertia.page.props.auth.user.id.toString();
      console.log('Found user ID in Inertia page props:', userId);
      
      // Add meta tag dynamically if it doesn't exist
      if (!document.querySelector('meta[name="user-id"]') && userId) {
        const meta = document.createElement('meta');
        meta.name = 'user-id';
        meta.content = userId;
        document.head.appendChild(meta);
        console.log('Added user ID meta tag dynamically');
      }
    }
    
    console.log('Using user ID for WebSocket connection:', userId);
    
    // Function to set up the Echo listeners
    const setupEchoListeners = (userId: string): (() => void) => {
      try {
        if (!window.Echo) {
          console.error('Echo not available when trying to set up listeners');
          return () => {}; // Return empty cleanup function
        }
        
        // Listen for new notifications
        const channel = window.Echo.private(`notifications.${userId}`);
        
        channel.listen('.notification.received', (data: { notification: Notification }) => {
          console.log('New admin notification received:', data);
          
          // Add the new notification to the list if it's not already there
          setNotifications(prev => {
            const existingIds = new Set(prev.data.map(n => n.id));
            if (!existingIds.has(data.notification.id)) {
              return {
                ...prev,
                data: [data.notification, ...prev.data],
                meta: {
                  ...prev.meta,
                  total: prev.meta.total + 1
                }
              };
            }
            return prev;
          });
          
          // Show toast notification
          toast.info(data.notification.title, {
            description: data.notification.body,
            duration: 5000,
          });
        });
        
        console.log('Successfully subscribed to notifications channel');
        
        // Return cleanup function
        return () => {
          channel.stopListening('.notification.received');
          if (window.Echo) {
            window.Echo.leave(`notifications.${userId}`);
          }
        };
      } catch (error) {
        console.error('Error setting up WebSocket connection:', error);
        return () => {}; // Return empty cleanup function on error
      }
    };
    
    if (userId && window.Echo) {
      // Set up listeners immediately if Echo is available
      return setupEchoListeners(userId);
    } else if (userId) {
      // Wait for Echo to be initialized if it's not available yet
      console.warn('Echo not available yet, will retry in 2 seconds');
      
      let cleanup: (() => void) | undefined;
      
      const timeoutId = setTimeout(() => {
        if (window.Echo) {
          console.log('Echo is now available, setting up listeners...');
          cleanup = setupEchoListeners(userId);
        } else {
          console.error('Echo still not available after waiting');
        }
      }, 2000);
      
      // Clean up timeout and Echo listeners when component unmounts
      return () => {
        clearTimeout(timeoutId);
        if (cleanup) cleanup();
      };
    } else {
      console.error('User ID not available, WebSocket connection not established');
      return () => {}; // Return empty cleanup function
    }
  }, []);

  // Handle select all
  const handleSelectAll = (checked: boolean) => {
    if (checked && notifications?.data) {
      setSelectedItems(notifications.data.map(item => item.id));
    } else {
      setSelectedItems([]);
    }
  };

  // Handle select item
  const handleSelectItem = (checked: boolean, id: number) => {
    if (checked) {
      setSelectedItems([...selectedItems, id]);
    } else {
      setSelectedItems(selectedItems.filter(item => item !== id));
    }
  };



  const isSelected = (id: number) => selectedItems.includes(id);
  const isAllSelected = notifications?.data?.length > 0 && selectedItems.length === notifications.data.length;

  return (
    <AdminLayout pageTitle="Notification Management" showRightSidebar={false}>
      <Head title="Auto-Notification Management" />
      <DebugUserID />
      
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
        
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold">Auto-Notification Table</h1>
        </div>
        
        {/* Search and filters */}
        <div className="flex flex-wrap gap-4 mb-6">
          <div className="flex-1 min-w-[240px]">
            <Input 
              placeholder="Search by Name / Email" 
              value={search} 
              onChange={(e) => setSearch(e.target.value)}
              className="w-full"
            />
          </div>
          
          <div className="w-40">
            <Select value={status} onValueChange={setStatus}>
              <SelectTrigger>
                <SelectValue placeholder="Select Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All</SelectItem>
                <SelectItem value="draft">Draft</SelectItem>
                <SelectItem value="scheduled">Scheduled</SelectItem>
                <SelectItem value="sent">Sent</SelectItem>
                <SelectItem value="delivered">Delivered</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div className="w-40">
            <Select value={subject} onValueChange={setSubject}>
              <SelectTrigger>
                <SelectValue placeholder="Select Type" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Types</SelectItem>
                <SelectItem value="system">System</SelectItem>
                <SelectItem value="payment">Payment</SelectItem>
                <SelectItem value="class">Class</SelectItem>
                <SelectItem value="subscription">Subscription</SelectItem>
                <SelectItem value="feature">Feature</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <Link href="/admin/notification-history">
            <Button className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer">
              View History
            </Button>
          </Link>
        </div>
        
        {/* Notification table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 text-left">
                <tr>
                  <th className="py-3 px-4 w-10">
                    <Checkbox 
                      checked={isAllSelected}
                      onCheckedChange={(checked) => handleSelectAll(!!checked)}
                    />
                  </th>
                  <th className="py-3 px-4 font-medium text-sm">Title</th>
                  <th className="py-3 px-4 font-medium text-sm">Type</th>
                  <th className="py-3 px-4 font-medium text-sm">Status</th>
                  <th className="py-3 px-4 font-medium text-sm text-center">Edit</th>
                  <th className="py-3 px-4 font-medium text-sm text-center">Preview</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {notifications?.data?.map((notification) => (
                  <tr key={notification.id} className="hover:bg-gray-50">
                    <td className="py-4 px-4">
                      <Checkbox 
                        checked={isSelected(notification.id)}
                        onCheckedChange={(checked) => handleSelectItem(!!checked, notification.id)}
                      />
                    </td>
                    <td className="py-4 px-4">{notification.title}</td>
                    <td className="py-4 px-4">{notification.type}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-1 rounded-full text-xs ${
                        notification.status === 'sent' || notification.status === 'delivered' || notification.status === 'read'
                          ? 'bg-green-100 text-green-800' 
                          : notification.status === 'scheduled'
                          ? 'bg-blue-100 text-blue-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {notification.status}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-center">
                      <Link href={`/admin/notification/${notification.id}/edit`} className="inline-flex">
                        <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700">
                          <Pencil className="h-5 w-5" />
                        </Button>
                      </Link>
                    </td>
                    <td className="py-4 px-4 text-center">
                      <Link href={`/admin/notification/${notification.id}`} className="inline-flex">
                        <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700">
                          <Eye className="h-5 w-5" />
                        </Button>
                      </Link>
                    </td>
                  </tr>
                ))}

                {(!notifications || !notifications.data || notifications.data.length === 0) && (
                  <tr>
                    <td colSpan={6} className="py-8 text-center text-gray-500">
                      No notifications found
                    </td>
                  </tr>
                )}
              </tbody>
            </table>
          </div>
          
          {/* Pagination */}
          {notifications?.meta && notifications.meta.last_page > 1 && (
            <div className="px-4 py-3 flex items-center justify-between border-t border-gray-200">
              <div className="flex-1 flex justify-between sm:hidden">
                <Button 
                  variant="outline" 
                  disabled={notifications.meta.current_page === 1}
                  onClick={() => window.location.href = notifications.links.prev}
                >
                  Previous
                </Button>
                <Button 
                  variant="outline" 
                  disabled={notifications.meta.current_page === notifications.meta.last_page}
                  onClick={() => window.location.href = notifications.links.next}
                >
                  Next
                </Button>
              </div>
              <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                <div>
                  <p className="text-sm text-gray-700">
                    Showing <span className="font-medium">{notifications.meta.from}</span> to{' '}
                    <span className="font-medium">{notifications.meta.to}</span> of{' '}
                    <span className="font-medium">{notifications.meta.total}</span> results
                  </p>
                </div>
                <div>
                  <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                    {notifications.meta.links.map((link, index) => {
                      if (link.url === null) return null;
                      
                      return (
                        <Link
                          key={index}
                          href={link.url}
                          className={`relative inline-flex items-center px-4 py-2 border text-sm font-medium ${
                            link.active 
                              ? 'z-10 bg-teal-600 border-teal-600 text-white' 
                              : 'bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 shadow-sm cursor-pointer'
                          } ${
                            link.label.includes('Previous') 
                              ? 'rounded-l-md' 
                              : link.label.includes('Next') 
                                ? 'rounded-r-md' 
                                : ''
                          }`}
                          dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                      );
                    })}
                  </nav>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </AdminLayout>
  );
}
