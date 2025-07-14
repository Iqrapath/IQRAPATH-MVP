import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Checkbox } from '@/components/ui/checkbox';
import { MoreVertical } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { 
  DropdownMenu, 
  DropdownMenuContent, 
  DropdownMenuItem, 
  DropdownMenuTrigger 
} from '@/components/ui/dropdown-menu';

interface ScheduledNotification {
  id: number;
  title: string;
  body: string;
  type: string;
  status: string;
  scheduled_at: string;
  sender_id: number;
  sender_type: string;
  sender?: {
    id: number;
    name: string;
    email: string;
  };
  metadata?: {
    frequency?: string;
    audience?: {
      type: string;
      name: string;
    };
  };
}

interface ScheduledProps {
  scheduledNotifications?: {
    data: ScheduledNotification[];
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
  filters?: {
    search?: string;
    status?: string;
    subject?: string;
  };
}

export default function Scheduled({ scheduledNotifications = { data: [] }, filters = {} }: ScheduledProps) {
  const [search, setSearch] = useState(filters.search || '');
  const [status, setStatus] = useState(filters.status || '');
  const [subject, setSubject] = useState(filters.subject || '');
  const [rating, setRating] = useState('');
  const [selectedItems, setSelectedItems] = useState<number[]>([]);

  // Format date for display
  const formatDate = (dateString: string) => {
    if (!dateString) return 'N/A';
    
    const date = new Date(dateString);
    const today = new Date();
    const tomorrow = new Date(today);
    tomorrow.setDate(today.getDate() + 1);
    
    // Format: "Apr 20, 2025 - 07:00 AM"
    return date.toLocaleDateString('en-US', { 
      month: 'short', 
      day: 'numeric', 
      year: 'numeric' 
    }) + ' - ' + date.toLocaleTimeString('en-US', { 
      hour: '2-digit', 
      minute: '2-digit', 
      hour12: true 
    });
  };

  // Handle search form submission
  const handleSearch = () => {
    const url = new URL(window.location.href);
    if (search) url.searchParams.set('search', search);
    else url.searchParams.delete('search');
    
    if (status && status !== 'all') url.searchParams.set('status', status);
    else url.searchParams.delete('status');
    
    if (subject && subject !== 'all') url.searchParams.set('subject', subject);
    else url.searchParams.delete('subject');
    
    if (rating && rating !== 'all') url.searchParams.set('rating', rating);
    else url.searchParams.delete('rating');
    
    window.location.href = url.toString();
  };

  // Handle select all checkbox
  const handleSelectAll = (checked: boolean) => {
    if (checked && scheduledNotifications?.data) {
      setSelectedItems(scheduledNotifications.data.map(item => item.id));
    } else {
      setSelectedItems([]);
    }
  };

  // Handle select individual item
  const handleSelectItem = (checked: boolean, id: number) => {
    if (checked) {
      setSelectedItems([...selectedItems, id]);
    } else {
      setSelectedItems(selectedItems.filter(item => item !== id));
    }
  };

  const isSelected = (id: number) => selectedItems.includes(id);
  const isAllSelected = scheduledNotifications?.data?.length > 0 && selectedItems.length === scheduledNotifications.data.length;

  // Get audience name from notification metadata or default to "All Users"
  const getAudienceName = (notification: ScheduledNotification) => {
    if (notification.metadata?.audience?.name) {
      return notification.metadata.audience.name;
    }
    
    // Default audience based on notification type
    switch (notification.type) {
      case 'class':
        return 'Students';
      case 'system':
        return 'All Users';
      case 'payment':
        return 'Teachers';
      default:
        return 'All Users';
    }
  };

  // Get frequency from notification metadata or default to "One-time"
  const getFrequency = (notification: ScheduledNotification) => {
    return notification.metadata?.frequency || 'One-time';
  };

  return (
    <>
      {/* Notification History Header */}
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Scheduled Notifications</h1>
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
              <SelectItem value="scheduled">Scheduled</SelectItem>
              <SelectItem value="pending">Pending</SelectItem>
              <SelectItem value="cancelled">Cancelled</SelectItem>
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
              <SelectItem value="class">Class</SelectItem>
              <SelectItem value="report">Report</SelectItem>
            </SelectContent>
          </Select>
        </div>
        
        <div className="w-40">
          <Select value={rating} onValueChange={setRating}>
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
        
        <Button 
          className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
          onClick={handleSearch}
        >
          Search
        </Button>
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
                <th className="py-3 px-4 font-medium text-sm">Scheduled Date</th>
                <th className="py-3 px-4 font-medium text-sm">Message</th>
                <th className="py-3 px-4 font-medium text-sm">Target Audience</th>
                <th className="py-3 px-4 font-medium text-sm">Frequency</th>
                <th className="py-3 px-4 font-medium text-sm text-center">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {scheduledNotifications.data.map((notification) => (
                <tr key={notification.id} className="hover:bg-gray-50">
                  <td className="py-4 px-4">
                    <Checkbox 
                      checked={isSelected(notification.id)}
                      onCheckedChange={(checked) => handleSelectItem(!!checked, notification.id)}
                    />
                  </td>
                  <td className="py-4 px-4 whitespace-nowrap">
                    {formatDate(notification.scheduled_at)}
                  </td>
                  <td className="py-4 px-4">{notification.body}</td>
                  <td className="py-4 px-4">{getAudienceName(notification)}</td>
                  <td className="py-4 px-4">{getFrequency(notification)}</td>
                  <td className="py-4 px-4 text-center">
                    <div className="flex justify-center">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="sm" className="text-gray-500 hover:text-gray-700">
                            <MoreVertical className="h-5 w-5" />
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem>Edit</DropdownMenuItem>
                          <DropdownMenuItem>Send Now</DropdownMenuItem>
                          <DropdownMenuItem>Reschedule</DropdownMenuItem>
                          <DropdownMenuItem className="text-red-600">Cancel</DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </div>
                  </td>
                </tr>
              ))}

              {scheduledNotifications.data.length === 0 && (
                <tr>
                  <td colSpan={6} className="py-8 text-center text-gray-500">
                    No scheduled notifications found
                  </td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
        
        {/* Pagination */}
        {scheduledNotifications.meta && scheduledNotifications.meta.last_page > 1 && (
          <div className="px-6 py-3 flex items-center justify-between border-t border-gray-200">
            <div className="flex-1 flex justify-between sm:hidden">
              <Button 
                variant="outline" 
                disabled={scheduledNotifications.meta.current_page === 1}
                onClick={() => window.location.href = scheduledNotifications.links.prev}
                className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
              >
                Previous
              </Button>
              <Button 
                variant="outline" 
                disabled={scheduledNotifications.meta.current_page === scheduledNotifications.meta.last_page}
                onClick={() => window.location.href = scheduledNotifications.links.next}
                className="bg-white text-[#338078] border-2 border-[#338078] rounded-full hover:bg-[#338078] hover:text-white transition-all duration-300 cursor-pointer"
              >
                Next
              </Button>
            </div>
            <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
              <div>
                <p className="text-sm text-gray-700">
                  Showing <span className="font-medium">{scheduledNotifications.meta.from}</span> to{' '}
                  <span className="font-medium">{scheduledNotifications.meta.to}</span> of{' '}
                  <span className="font-medium">{scheduledNotifications.meta.total}</span> results
                </p>
              </div>
              <div>
                <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                  {scheduledNotifications.meta.links.map((link, index) => {
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
    </>
  );
}
