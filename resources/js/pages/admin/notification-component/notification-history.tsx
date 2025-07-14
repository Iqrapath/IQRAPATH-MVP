import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Eye, MoreVertical } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Head } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';

interface NotificationHistoryProps {
  notifications?: {
    data: any[];
    links?: any;
    meta?: {
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
  urgentActions?: {
    withdrawalRequests: number;
    teacherApplications: number;
    pendingSessions: number;
    reportedDisputes: number;
  };
  filters?: {
    search?: string;
    status?: string;
    subject?: string;
  };
}

export default function NotificationHistory({ 
  notifications = { data: [] }, 
  urgentActions = {
    withdrawalRequests: 5,
    teacherApplications: 2,
    pendingSessions: 3,
    reportedDisputes: 1
  },
  filters = {} 
}: NotificationHistoryProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');
  const [subject, setSubject] = useState(filters.subject || '');
  const [activeTab, setActiveTab] = useState('history');

  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: '/admin/notifications' },
    // { title: 'Notification History', href: '/admin/notification-history' },
    { title: 'Notification History', href: '#' },
  ];

  // Example notification data if none provided
  const exampleNotifications = [
    {
      id: 1,
      date: 'Apr 11, 2023',
      time: '10:15 AM',
      message: 'Payment received successfully',
      sent_to: '130 Students',
      type: 'System',
      status: 'Delivered',
    },
    {
      id: 2,
      date: 'Apr 12, 2023',
      time: '08:00 PM',
      message: 'Platform Maintenance Alert',
      sent_to: 'All Users',
      type: 'Announcement',
      status: 'Sent',
    },
    {
      id: 3,
      date: 'Apr 11, 2023',
      time: '03:30 PM',
      message: 'Weekly progress available',
      sent_to: 'Guardian: Amina Rabi',
      type: 'Custom',
      status: 'Read',
    }
  ];

  const displayNotifications = notifications.data.length > 0 ? notifications.data : exampleNotifications;

  return (
    <AdminLayout pageTitle="Notification History" showRightSidebar={false}>
      <Head title="Notification History" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
      </div>
      <div>
        {/* Urgent Action Required Section */}
        <div className="bg-gray-50 rounded-lg p-6 mb-8">
          <h2 className="text-lg font-semibold mb-4">Urgent / Action Required</h2>
          
          <div className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-gray-700">{urgentActions.withdrawalRequests} Withdrawal Requests Pending Approval</span>
              <Link href="/admin/withdrawal-requests">
                <Button variant="link" className="text-teal-600 hover:text-teal-700">
                  View Requests
                </Button>
              </Link>
            </div>
            
            <div className="flex items-center justify-between">
              <span className="text-gray-700">{urgentActions.teacherApplications} Teacher Applications Awaiting Verification</span>
              <Link href="/admin/teacher-applications">
                <Button variant="link" className="text-teal-600 hover:text-teal-700">
                  Review Now
                </Button>
              </Link>
            </div>
            
            <div className="flex items-center justify-between">
              <span className="text-gray-700">{urgentActions.pendingSessions} Sessions Pending Teacher Assignment</span>
              <Link href="/admin/pending-sessions">
                <Button variant="link" className="text-teal-600 hover:text-teal-700">
                  Assign Teachers
                </Button>
              </Link>
            </div>
            
            <div className="flex items-center justify-between">
              <span className="text-gray-700">{urgentActions.reportedDisputes} Reported Dispute Requires Resolution</span>
              <Link href="/admin/disputes">
                <Button variant="link" className="text-teal-600 hover:text-teal-700">
                  Open Dispute
                </Button>
              </Link>
            </div>
          </div>
        </div>

        {/* Notification Tabs */}
        <div className="flex border-b mb-6">
          <button 
            className={`px-6 py-3 font-medium text-sm ${activeTab === 'history' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500'}`}
            onClick={() => setActiveTab('history')}
          >
            Notification History
          </button>
          <button 
            className={`px-6 py-3 font-medium text-sm ${activeTab === 'scheduled' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500'}`}
            onClick={() => setActiveTab('scheduled')}
          >
            Scheduled Notifications
          </button>
          <button 
            className={`px-6 py-3 font-medium text-sm ${activeTab === 'completed' ? 'text-teal-600 border-b-2 border-teal-600' : 'text-gray-500'}`}
            onClick={() => setActiveTab('completed')}
          >
            Completed Classes
          </button>
        </div>

        {/* Notification History Header */}
        <div className="flex justify-between items-center mb-6">
          <h1 className="text-2xl font-bold">Notification History</h1>
          <Link href="/admin/notifications/create">
            <Button className="bg-teal-600 hover:bg-teal-700">
              Create New Notification
            </Button>
          </Link>
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
                <SelectItem value="delivered">Delivered</SelectItem>
                <SelectItem value="sent">Sent</SelectItem>
                <SelectItem value="read">Read</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div className="w-40">
            <Select value={subject} onValueChange={setSubject}>
              <SelectTrigger>
                <SelectValue placeholder="Select Subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Subjects</SelectItem>
                <SelectItem value="system">System</SelectItem>
                <SelectItem value="announcement">Announcement</SelectItem>
                <SelectItem value="custom">Custom</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <div className="w-40">
            <Select>
              <SelectTrigger>
                <SelectValue placeholder="Rating" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Ratings</SelectItem>
                <SelectItem value="high">High Priority</SelectItem>
                <SelectItem value="medium">Medium Priority</SelectItem>
                <SelectItem value="low">Low Priority</SelectItem>
              </SelectContent>
            </Select>
          </div>
          
          <Button className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer">
            Search
          </Button>
        </div>
        
        {/* Notification table */}
        <div className="bg-white rounded-lg shadow overflow-hidden">
          <div className="overflow-x-auto">
            <table className="w-full">
              <thead className="bg-gray-50 text-left">
                <tr>
                  <th className="py-3 px-4 font-medium text-sm">Date & Time</th>
                  <th className="py-3 px-4 font-medium text-sm">Message</th>
                  <th className="py-3 px-4 font-medium text-sm">Sent To</th>
                  <th className="py-3 px-4 font-medium text-sm">Type</th>
                  <th className="py-3 px-4 font-medium text-sm">Delivery Status</th>
                  <th className="py-3 px-4 font-medium text-sm text-center">Actions</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-gray-200">
                {displayNotifications.map((notification) => (
                  <tr key={notification.id} className="hover:bg-gray-50">
                    <td className="py-4 px-4 text-sm">
                      <div>{notification.date}</div>
                      <div className="text-gray-500">{notification.time}</div>
                    </td>
                    <td className="py-4 px-4">{notification.message}</td>
                    <td className="py-4 px-4">{notification.sent_to}</td>
                    <td className="py-4 px-4">{notification.type}</td>
                    <td className="py-4 px-4">
                      <span className={`px-2 py-1 rounded-full text-xs ${
                        notification.status === 'Delivered'
                          ? 'bg-green-100 text-green-800' 
                          : notification.status === 'Sent'
                          ? 'bg-blue-100 text-blue-800'
                          : notification.status === 'Read'
                          ? 'bg-green-100 text-green-800'
                          : 'bg-gray-100 text-gray-800'
                      }`}>
                        {notification.status}
                      </span>
                    </td>
                    <td className="py-4 px-4 text-center">
                      <div className="flex justify-center">
                        <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700 mr-2">
                          <Eye className="h-5 w-5" />
                        </Button>
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700">
                              <MoreVertical className="h-5 w-5" />
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem>View Details</DropdownMenuItem>
                            <DropdownMenuItem>Resend</DropdownMenuItem>
                            <DropdownMenuItem className="text-red-600">Delete</DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      </div>
                    </td>
                  </tr>
                ))}

                {displayNotifications.length === 0 && (
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
          {notifications.meta && notifications.meta.last_page > 1 && (
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
                              ? 'z-10 bg-primary border-primary text-white' 
                              : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'
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
