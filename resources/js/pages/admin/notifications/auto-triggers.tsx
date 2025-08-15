import React, { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Search, Edit, Eye, X, Bell, CreditCard, Clock, AlertTriangle, Receipt, Star } from 'lucide-react';
import { cn } from '@/lib/utils';
import { toast, Toaster } from 'sonner';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface NotificationTrigger {
  id: number;
  name: string;
  event: string;
  is_enabled: boolean;
  level: string;
  created_at: string;
  updated_at: string;
}

interface Filters {
  search: string;
  status: string;
  subject: string;
  rating: string;
}

interface Props {
  notifications: NotificationTrigger[];
  filters: Filters;
}

export default function AutoNotificationTriggersPage({ notifications, filters }: Props) {
  const [searchQuery, setSearchQuery] = useState(filters.search);
  const [statusFilter, setStatusFilter] = useState(filters.status);
  const [subjectFilter, setSubjectFilter] = useState(filters.subject);
  const [ratingFilter, setRatingFilter] = useState(filters.rating);
  const [selectedNotifications, setSelectedNotifications] = useState<number[]>([]);
  const [updatingNotifications, setUpdatingNotifications] = useState<number[]>([]);

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
    router.get('/admin/notifications/auto-triggers', {
      search: searchQuery,
      status: statusFilter,
      subject: subjectFilter,
      rating: ratingFilter,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleClearFilters = () => {
    setSearchQuery('');
    setStatusFilter('all');
    setSubjectFilter('all');
    setRatingFilter('all');
    router.get('/admin/notifications/auto-triggers', {}, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleStatusToggle = async (notificationId: number, newStatus: boolean) => {
    setUpdatingNotifications(prev => [...prev, notificationId]);
    
    try {
      const response = await fetch(`/admin/notifications/${notificationId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ is_enabled: newStatus }),
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        router.reload();
      } else {
        toast.error(data.message || 'Failed to update notification');
      }
    } catch (error) {
      console.error('Error updating notification:', error);
      toast.error('Failed to update notification. Please try again.');
    } finally {
      setUpdatingNotifications(prev => prev.filter(id => id !== notificationId));
    }
  };

  const handleDeleteNotification = async (notificationId: number) => {
    if (!confirm('Are you sure you want to delete this notification?')) return;

    try {
      const response = await fetch(`/admin/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });

      const data = await response.json();

      if (data.success) {
        toast.success(data.message);
        router.reload();
      } else {
        toast.error(data.message || 'Failed to delete notification');
      }
    } catch (error) {
      console.error('Error deleting notification:', error);
      toast.error('Failed to delete notification. Please try again.');
    }
  };

  const getAlertTypeIcon = (event: string) => {
    if (event.includes('payment') || event.includes('Payment')) {
      return <CreditCard className="w-5 h-5" />;
    }
    if (event.includes('class') || event.includes('Class')) {
      return <Clock className="w-5 h-5" />;
    }
    if (event.includes('subscription') || event.includes('Subscription')) {
      return <AlertTriangle className="w-5 h-5" />;
    }
    if (event.includes('feature') || event.includes('Feature')) {
      return <Star className="w-5 h-5" />;
    }
    return <Bell className="w-5 h-5" />;
  };

  const getAlertTypeColor = (event: string) => {
    if (event.includes('payment') || event.includes('Payment')) {
      return "bg-green-100 text-green-700";
    }
    if (event.includes('class') || event.includes('Class')) {
      return "bg-blue-100 text-blue-700";
    }
    if (event.includes('subscription') || event.includes('Subscription')) {
      return "bg-red-100 text-red-700";
    }
    if (event.includes('feature') || event.includes('Feature')) {
      return "bg-purple-100 text-purple-700";
    }
    return "bg-gray-100 text-gray-700";
  };

  const filteredNotifications = notifications.filter(notification => {
    if (searchQuery && !notification.name.toLowerCase().includes(searchQuery.toLowerCase())) {
      return false;
    }
    if (statusFilter !== 'all') {
      if (statusFilter === 'enabled' && !notification.is_enabled) return false;
      if (statusFilter === 'disabled' && notification.is_enabled) return false;
    }
    if (subjectFilter !== 'all' && !notification.event.toLowerCase().includes(subjectFilter.toLowerCase())) {
      return false;
    }
    if (ratingFilter !== 'all' && notification.level !== ratingFilter) {
      return false;
    }
    return true;
  });

  return (
    <AdminLayout pageTitle="Auto-Notification Table" showRightSidebar={false}>
      <Head title="Auto-Notification Table" />
      <Toaster position="top-right" />
      <div className="container py-6">
        
        {/* Breadcrumb */}
        <div className="mb-4">
        <Breadcrumbs 
          breadcrumbs={[
            { title: 'Dashboard', href: '/admin/dashboard' },
            { title: 'Notifications System', href: '/admin/notifications' },
            { title: 'Auto-Notification Table', href: '/admin/notifications/auto-triggers' },
          ]} 
        />
        </div>
        {/* Main Title */}
        <h1 className="text-2xl font-bold mb-6">Auto-Notification Table</h1>

        {/* Search and Filter Section */}
        <div className="flex flex-col md:flex-row gap-4 mb-6">
          {/* Search Bar */}
          <div className="flex-1 relative">
            <Input
              placeholder="Search by Name / Email"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
              className="pl-10 border rounded-full h-11"
            />
            <div className="absolute left-3 top-3 text-gray-400">
              <Search size={18} />
            </div>
          </div>

          {/* Filter Dropdowns */}
          <div className="flex gap-2">
            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px] border rounded-full">
                <SelectValue placeholder="Select Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="enabled">Enabled</SelectItem>
                <SelectItem value="disabled">Disabled</SelectItem>
              </SelectContent>
            </Select>

            <Select value={subjectFilter} onValueChange={setSubjectFilter}>
              <SelectTrigger className="w-[140px] border rounded-full">
                <SelectValue placeholder="Select Subject" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Subjects</SelectItem>
                <SelectItem value="payment">Payment</SelectItem>
                <SelectItem value="class">Class</SelectItem>
                <SelectItem value="subscription">Subscription</SelectItem>
                <SelectItem value="feature">Feature</SelectItem>
              </SelectContent>
            </Select>

            <Select value={ratingFilter} onValueChange={setRatingFilter}>
              <SelectTrigger className="w-[140px] border rounded-full">
                <SelectValue placeholder="Rating" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Ratings</SelectItem>
                <SelectItem value="high">High</SelectItem>
                <SelectItem value="medium">Medium</SelectItem>
                <SelectItem value="low">Low</SelectItem>
              </SelectContent>
            </Select>

            {/* Action Buttons */}
            <Button
              type="button"
              onClick={handleSearch}
              className="bg-teal-600 hover:bg-teal-700 rounded-full"
            >
              Search
            </Button>
            <Button
              type="button"
              onClick={handleClearFilters}
              variant="outline"
              className="border-teal-600 text-teal-600 hover:bg-teal-50 rounded-full"
            >
              Clear Filters
            </Button>
          </div>
        </div>

        {/* Auto-Notification Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow className="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-200">
                <TableHead className="w-16 px-6 py-4">
                  <div className="flex items-center justify-center">
                    <Checkbox
                      checked={selectedNotifications.length === filteredNotifications.length && filteredNotifications.length > 0}
                      onCheckedChange={handleSelectAll}
                      className="data-[state=checked]:bg-teal-600 data-[state=checked]:border-teal-600"
                    />
                  </div>
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 bg-teal-500 rounded-full"></div>
                    Alert Type
                  </div>
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 bg-blue-500 rounded-full"></div>
                    Trigger Event
                  </div>
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  <div className="flex items-center gap-2">
                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                    Status
                  </div>
                </TableHead>
                <TableHead className="w-28 px-6 py-4 text-center font-semibold text-gray-900">
                  <div className="flex items-center justify-center gap-2">
                    <div className="w-2 h-2 bg-amber-500 rounded-full"></div>
                    Actions
                  </div>
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {filteredNotifications.map((notification, index) => (
                <TableRow 
                  key={notification.id}
                  className={cn(
                    "border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150",
                    index % 2 === 0 ? "bg-white" : "bg-gray-50/30",
                    selectedNotifications.includes(notification.id) && "bg-teal-50 border-teal-200"
                  )}
                >
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center justify-center">
                      <Checkbox
                        checked={selectedNotifications.includes(notification.id)}
                        onCheckedChange={(checked) => 
                          handleSelectNotification(notification.id, checked as boolean)
                        }
                        className="data-[state=checked]:bg-teal-600 data-[state=checked]:border-teal-600"
                      />
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center gap-3">
                      <div className={cn(
                        "w-10 h-10 rounded-lg flex items-center justify-center",
                        getAlertTypeColor(notification.event)
                      )}>
                        {getAlertTypeIcon(notification.event)}
                      </div>
                      <div>
                        <div className="font-semibold text-gray-900">{notification.name}</div>
                        <div className="text-xs text-gray-500 mt-1">
                          {notification.is_enabled ? 'Active' : 'Inactive'}
                        </div>
                      </div>
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="max-w-xs">
                      <div className="text-gray-900 font-medium">{notification.event}</div>
                      <div className="text-xs text-gray-500 mt-1 flex items-center gap-1">
                        <div className="w-2 h-2 bg-gray-300 rounded-full"></div>
                        Auto-triggered
                      </div>
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      <span className={cn(
                        "px-3 py-1.5 rounded-full text-xs font-semibold flex items-center gap-1.5",
                        notification.is_enabled
                          ? "bg-green-100 text-green-800 border border-green-200" 
                          : "bg-red-100 text-red-800 border border-red-200"
                      )}>
                        <div className={cn(
                          "w-2 h-2 rounded-full",
                          notification.is_enabled ? "bg-green-500" : "bg-red-500"
                        )}></div>
                        {notification.is_enabled ? 'Enabled' : 'Disabled'}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center justify-center gap-2">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 w-9 p-0 hover:bg-blue-100 hover:text-blue-700 transition-colors"
                        title="Edit Notification"
                        onClick={() => handleStatusToggle(notification.id, !notification.is_enabled)}
                        disabled={updatingNotifications.includes(notification.id)}
                      >
                        {updatingNotifications.includes(notification.id) ? (
                          <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
                        ) : (
                          <Edit className="h-4 w-4" />
                        )}
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 w-9 p-0 hover:bg-green-100 hover:text-green-700 transition-colors"
                        title="Preview Notification"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 w-9 p-0 hover:bg-red-100 hover:text-red-700 transition-colors"
                        title="Delete Notification"
                        onClick={() => handleDeleteNotification(notification.id)}
                      >
                        <X className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {/* Empty State */}
          {filteredNotifications.length === 0 && (
            <div className="py-12 text-center">
              <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Search className="w-8 h-8 text-gray-400" />
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">No notifications found</h3>
              <p className="text-gray-500 mb-4">
                {searchQuery || statusFilter !== 'all' || subjectFilter !== 'all' || ratingFilter !== 'all'
                  ? 'No notifications match your current filters' 
                  : "No notifications available"}
              </p>
              {(searchQuery || statusFilter !== 'all' || subjectFilter !== 'all' || ratingFilter !== 'all') && (
                <Button variant="outline" onClick={handleClearFilters}>
                  Clear filters
                </Button>
              )}
            </div>
          )}
        </div>

        {/* Selected Actions */}
        {selectedNotifications.length > 0 && (
          <div className="mt-4 p-4 bg-gray-50 rounded-md border">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">
                {selectedNotifications.length} notification(s) selected
              </span>
              <div className="flex gap-2">
                <Button 
                  variant="outline" 
                  size="sm"
                  onClick={() => {
                    selectedNotifications.forEach(id => {
                      const notification = notifications.find(n => n.id === id);
                      if (notification && !notification.is_enabled) {
                        handleStatusToggle(id, true);
                      }
                    });
                  }}
                >
                  Enable Selected
                </Button>
                <Button 
                  variant="outline" 
                  size="sm"
                  onClick={() => {
                    selectedNotifications.forEach(id => {
                      const notification = notifications.find(n => n.id === id);
                      if (notification && notification.is_enabled) {
                        handleStatusToggle(id, false);
                      }
                    });
                  }}
                >
                  Disable Selected
                </Button>
                <Button 
                  variant="outline" 
                  size="sm" 
                  className="text-red-600"
                  onClick={() => {
                    if (confirm(`Are you sure you want to delete ${selectedNotifications.length} notification(s)?`)) {
                      selectedNotifications.forEach(id => handleDeleteNotification(id));
                    }
                  }}
                >
                  Delete Selected
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
