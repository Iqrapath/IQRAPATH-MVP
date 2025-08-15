import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Search, MoreVertical, Plus, Loader2 } from 'lucide-react';
import { toast } from 'sonner';

interface ScheduledNotification {
  id: number;
  scheduled_date: string;
  message: string;
  target_audience: string;
  frequency: string;
  status: 'scheduled' | 'sent' | 'cancelled';
  created_at: string;
  updated_at: string;
}

interface Props {
  scheduledNotifications?: ScheduledNotification[];
}

export default function ScheduledNotifications({ scheduledNotifications }: Props) {
  const [notifications, setNotifications] = useState<ScheduledNotification[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [subjectFilter, setSubjectFilter] = useState('all');
  const [ratingFilter, setRatingFilter] = useState('all');
  const [selectedNotifications, setSelectedNotifications] = useState<number[]>([]);

  // Refresh notifications from props (for after delete/cancel operations)
  const refreshNotifications = () => {
    if (scheduledNotifications && scheduledNotifications.length > 0) {
      setNotifications(scheduledNotifications);
    }
  };

  // Search and filter notifications locally
  const searchNotifications = () => {
    setLoading(true);
    
    // Start with the original data from props
    let filteredData = scheduledNotifications || [];
    
    // Filter by search query
    if (searchQuery) {
      filteredData = filteredData.filter(notification => 
        notification.message.toLowerCase().includes(searchQuery.toLowerCase()) ||
        notification.target_audience.toLowerCase().includes(searchQuery.toLowerCase())
      );
    }
    
    // Filter by status
    if (statusFilter !== 'all') {
      filteredData = filteredData.filter(notification => 
        notification.status === statusFilter
      );
    }
    
    // Filter by subject (message type)
    if (subjectFilter !== 'all') {
      filteredData = filteredData.filter(notification => 
        notification.message.toLowerCase().includes(subjectFilter.toLowerCase())
      );
    }
    
    // Filter by rating (priority) - placeholder for future implementation
    if (ratingFilter !== 'all') {
      // This can be implemented when priority field is added
    }
    
    setNotifications(filteredData);
    setLoading(false);
  };

  // Delete notification
  const deleteNotification = async (id: number) => {
    try {
      const response = await fetch(`/api/admin/scheduled-notifications/${id}`, {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        credentials: 'include',
      });

      if (response.ok) {
        toast.success('Notification deleted successfully');
        refreshNotifications(); // Refresh the list
      } else {
        console.error('Delete API Error:', response.status, response.statusText);
        const errorText = await response.text();
        console.error('Delete error response:', errorText);
        toast.error('Failed to delete notification');
      }
    } catch (error) {
      console.error('Error deleting notification:', error);
      toast.error('Error deleting notification');
    }
  };

  // Cancel notification
  const cancelNotification = async (id: number) => {
    try {
      const response = await fetch(`/api/admin/scheduled-notifications/${id}/cancel`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        credentials: 'include',
      });

      if (response.ok) {
        toast.success('Notification cancelled successfully');
        refreshNotifications(); // Refresh the list
      } else {
        console.error('Cancel API Error:', response.status, response.statusText);
        const errorText = await response.text();
        console.error('Cancel error response:', errorText);
        toast.error('Failed to cancel notification');
      }
    } catch (error) {
      console.error('Error cancelling notification:', error);
      toast.error('Error cancelling notification');
    }
  };

  // Initialize with props data
  useEffect(() => {
    if (scheduledNotifications && scheduledNotifications.length > 0) {
      setNotifications(scheduledNotifications);
    }
  }, [scheduledNotifications]);

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedNotifications(notifications.map(n => n.id));
    } else {
      setSelectedNotifications([]);
    }
  };

  const handleSelectNotification = (id: number, checked: boolean) => {
    if (checked) {
      setSelectedNotifications(prev => [...prev, id]);
    } else {
      setSelectedNotifications(prev => prev.filter(n => n !== id));
    }
  };

  const handleSearch = () => {
    searchNotifications();
  };

  const handleClearFilters = () => {
    setSearchQuery('');
    setStatusFilter('all');
    setSubjectFilter('all');
    setRatingFilter('all');
    // Reset to original data from props
    if (scheduledNotifications && scheduledNotifications.length > 0) {
      setNotifications(scheduledNotifications);
    }
  };

  const formatDate = (dateString: string) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'scheduled':
        return <span className="text-blue-600 font-medium">Scheduled</span>;
      case 'sent':
        return <span className="text-green-600 font-medium">Sent</span>;
      case 'cancelled':
        return <span className="text-red-600 font-medium">Cancelled</span>;
      default:
        return <span className="text-gray-600 font-medium">{status}</span>;
    }
  };

  return (
    <div>
      {/* Header with Create Button */}
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-xl font-semibold text-gray-900">Notification History</h2>
        <Button className="bg-teal-600 hover:bg-teal-700 text-white">
          <Plus className="h-4 w-4 mr-2" />
          Create New Notification
        </Button>
      </div>

      {/* Search and Filter Bar */}
      <div className="mb-6">
        <div className="flex flex-col md:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Input
                placeholder="Search by Name / Email"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="pl-10 border rounded-lg h-11"
              />
              <div className="absolute left-3 top-3 text-gray-400">
                <Search size={18} />
              </div>
            </div>
          </div>
          <div className="flex gap-2">
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Select Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="scheduled">Scheduled</SelectItem>
                <SelectItem value="sent">Sent</SelectItem>
                <SelectItem value="cancelled">Cancelled</SelectItem>
              </SelectContent>
            </Select>
            
            <Select value={subjectFilter} onValueChange={setSubjectFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Select Subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Subjects</SelectItem>
                <SelectItem value="reminders">Reminders</SelectItem>
                <SelectItem value="reports">Reports</SelectItem>
                <SelectItem value="updates">Updates</SelectItem>
              </SelectContent>
            </Select>
            
            <Select value={ratingFilter} onValueChange={setRatingFilter}>
              <SelectTrigger className="w-[140px] border rounded-lg">
                <SelectValue placeholder="Rating" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Ratings</SelectItem>
                <SelectItem value="high">High Priority</SelectItem>
                <SelectItem value="medium">Medium Priority</SelectItem>
                <SelectItem value="low">Low Priority</SelectItem>
              </SelectContent>
            </Select>
            
            <Button
              type="button"
              onClick={handleSearch}
              disabled={loading}
              className="bg-teal-600 hover:bg-teal-700 text-white border-teal-600"
            >
              {loading ? <Loader2 className="h-4 w-4 animate-spin mr-2" /> : null}
              Search
            </Button>
            
            <Button
              type="button"
              onClick={handleClearFilters}
              disabled={loading}
              variant="outline"
              className="border-gray-300 text-gray-700 hover:bg-gray-50"
            >
              Clear
            </Button>
          </div>
        </div>
      </div>

      {/* Table */}
      <div className="bg-white rounded-lg border">
        <Table>
          <TableHeader className="bg-gray-50">
            <TableRow>
              <TableHead className="w-12">
                <input
                  type="checkbox"
                  className="rounded border-gray-300"
                  checked={selectedNotifications.length === notifications.length && notifications.length > 0}
                  onChange={(e) => handleSelectAll(e.target.checked)}
                />
              </TableHead>
              <TableHead className="font-medium text-gray-900">Scheduled Date</TableHead>
              <TableHead className="font-medium text-gray-900">Message</TableHead>
              <TableHead className="font-medium text-gray-900">Target Audience</TableHead>
              <TableHead className="font-medium text-gray-900">Frequency</TableHead>
              <TableHead className="font-medium text-gray-900">Status</TableHead>
              <TableHead className="font-medium text-gray-900">Actions</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {loading ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8">
                  <div className="flex items-center justify-center">
                    <Loader2 className="h-6 w-6 animate-spin mr-2" />
                    <span className="text-gray-500">Loading notifications...</span>
                  </div>
                </TableCell>
              </TableRow>
            ) : notifications.length === 0 ? (
              <TableRow>
                <TableCell colSpan={7} className="text-center py-8 text-gray-500">
                  No scheduled notifications found
                </TableCell>
              </TableRow>
            ) : (
              notifications.map((notification) => (
                <TableRow key={notification.id} className="hover:bg-gray-50">
                  <TableCell>
                    <input
                      type="checkbox"
                      className="rounded border-gray-300"
                      checked={selectedNotifications.includes(notification.id)}
                      onChange={(e) => handleSelectNotification(notification.id, e.target.checked)}
                    />
                  </TableCell>
                  <TableCell className="font-medium text-gray-900">
                    {formatDate(notification.scheduled_date)}
                  </TableCell>
                  <TableCell className="max-w-xs">
                    <div className="truncate" title={notification.message}>
                      {notification.message}
                    </div>
                  </TableCell>
                  <TableCell className="text-gray-600">
                    {notification.target_audience}
                  </TableCell>
                  <TableCell className="text-gray-600">
                    {notification.frequency}
                  </TableCell>
                  <TableCell>
                    {getStatusBadge(notification.status)}
                  </TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button
                          variant="ghost"
                          className="h-8 w-8 p-0"
                          aria-label="Open menu"
                        >
                          <MoreVertical className="h-4 w-4" />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end" className="w-48">
                        <DropdownMenuItem className="cursor-pointer">
                          Edit Notification
                        </DropdownMenuItem>
                        <DropdownMenuItem className="cursor-pointer">
                          View Details
                        </DropdownMenuItem>
                        {notification.status === 'scheduled' && (
                          <DropdownMenuItem 
                            className="cursor-pointer text-red-600"
                            onClick={() => cancelNotification(notification.id)}
                          >
                            Cancel Notification
                          </DropdownMenuItem>
                        )}
                        <DropdownMenuItem 
                          className="cursor-pointer text-red-600"
                          onClick={() => deleteNotification(notification.id)}
                        >
                          Delete Notification
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))
            )}
          </TableBody>
        </Table>
      </div>

      {/* Selected Actions */}
      {selectedNotifications.length > 0 && (
        <div className="mt-4 p-4 bg-blue-50 border border-blue-200 rounded-lg">
          <div className="flex items-center justify-between">
            <span className="text-sm text-blue-800">
              {selectedNotifications.length} notification(s) selected
            </span>
            <div className="flex gap-2">
              <Button
                size="sm"
                variant="outline"
                className="border-blue-300 text-blue-700 hover:bg-blue-100"
              >
                Edit Selected
              </Button>
              <Button
                size="sm"
                variant="outline"
                className="border-red-300 text-red-700 hover:bg-red-100"
                onClick={() => {
                  selectedNotifications.forEach(id => cancelNotification(id));
                  setSelectedNotifications([]);
                }}
              >
                Cancel Selected
              </Button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
}
