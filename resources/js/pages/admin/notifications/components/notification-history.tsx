import React, { useState } from 'react';
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
import { Search, Eye, MoreHorizontal, CheckCircle, Clock } from 'lucide-react';
import { cn } from '@/lib/utils';

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
  notifiable?: {
    id: string;
    name?: string;
    email?: string;
  };
}

interface Props {
  notifications: {
    data: Notification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  selectedNotifications: string[];
  onSelectAll: (checked: boolean) => void;
  onSelectNotification: (id: string, checked: boolean) => void;
  onSearch: () => void;
  onClearFilters: () => void;
  searchQuery: string;
  setSearchQuery: (query: string) => void;
  roleFilter: string;
  setRoleFilter: (filter: string) => void;
  statusFilter: string;
  setStatusFilter: (filter: string) => void;
  isLoading: boolean;
}

export default function NotificationHistory({
  notifications,
  selectedNotifications,
  onSelectAll,
  onSelectNotification,
  onSearch,
  onClearFilters,
  searchQuery,
  setSearchQuery,
  roleFilter,
  setRoleFilter,
  statusFilter,
  setStatusFilter,
  isLoading,
}: Props) {
  const getDeliveryStatusIcon = (status: string) => {
    switch (status) {
      case 'read':
        return <CheckCircle className="w-4 h-4 text-green-500" />;
      case 'unread':
        return <Clock className="w-4 h-4 text-blue-500" />;
      default:
        return <Clock className="w-4 h-4 text-gray-500" />;
    }
  };

  const getDeliveryStatusText = (status: string) => {
    switch (status) {
      case 'read':
        return 'Read';
      case 'unread':
        return 'Unread';
      default:
        return 'Unknown';
    }
  };

  const getDeliveryStatusColor = (status: string) => {
    switch (status) {
      case 'read':
        return 'text-green-600';
      case 'unread':
        return 'text-blue-600';
      default:
        return 'text-gray-600';
    }
  };

  const filteredNotifications = (notifications?.data || []).filter(notification => {
    if (searchQuery && !notification.type.toLowerCase().includes(searchQuery.toLowerCase())) {
      return false;
    }
    if (roleFilter !== 'all' && !notification.level.toLowerCase().includes(roleFilter.toLowerCase())) {
      return false;
    }
    if (statusFilter !== 'all') {
      if (statusFilter === 'read' && !notification.read_at) return false;
      if (statusFilter === 'unread' && notification.read_at) return false;
    }
    return true;
  });

  return (
    <div>
      {/* Notification History Section */}
      <div className="mb-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Notification History</h3>
        
        {/* Search and Filter Bar */}
        <div className="flex flex-col md:flex-row gap-4 mb-6">
          <div className="flex-1 relative">
            <Input
              placeholder="Search by Type / Level"
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              className="pl-10 border rounded-full h-11"
            />
            <div className="absolute left-3 top-3 text-gray-400">
              <Search size={18} />
            </div>
          </div>

          <div className="flex gap-2">
            <Select value={roleFilter} onValueChange={setRoleFilter}>
              <SelectTrigger className="w-[140px] border rounded-full">
                <SelectValue placeholder="Select Level" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Levels</SelectItem>
                <SelectItem value="info">Info</SelectItem>
                <SelectItem value="success">Success</SelectItem>
                <SelectItem value="warning">Warning</SelectItem>
                <SelectItem value="error">Error</SelectItem>
              </SelectContent>
            </Select>

            <Select value={statusFilter} onValueChange={setStatusFilter}>
              <SelectTrigger className="w-[140px] border rounded-full">
                <SelectValue placeholder="Select Status" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Statuses</SelectItem>
                <SelectItem value="read">Read</SelectItem>
                <SelectItem value="unread">Unread</SelectItem>
              </SelectContent>
            </Select>

            <Button
              type="button"
              onClick={onSearch}
              disabled={isLoading}
              className="bg-teal-600 hover:bg-teal-700 rounded-full"
            >
              {isLoading ? (
                <div className="h-4 w-4 animate-spin rounded-full border-2 border-current border-t-transparent" />
              ) : (
                'Search'
              )}
            </Button>
            <Button
              type="button"
              onClick={onClearFilters}
              variant="outline"
              className="border-teal-600 text-teal-600 hover:bg-teal-50 rounded-full"
            >
              Clear Filters
            </Button>
          </div>
        </div>

        {/* Notification History Table */}
        <div className="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
          <Table>
            <TableHeader>
              <TableRow className="bg-gray-50 border-b border-gray-200">
                <TableHead className="w-16 px-6 py-4">
                  <div className="flex items-center justify-center">
                    <Checkbox
                      checked={selectedNotifications.length === (filteredNotifications?.length || 0) && (filteredNotifications?.length || 0) > 0}
                      onCheckedChange={onSelectAll}
                      className="data-[state=checked]:bg-teal-600 data-[state=checked]:border-teal-600"
                    />
                  </div>
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  Date & Time
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  Message
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  Recipient
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  Type
                </TableHead>
                <TableHead className="px-6 py-4 text-left font-semibold text-gray-900">
                  Status
                </TableHead>
                <TableHead className="w-20 px-6 py-4 text-center font-semibold text-gray-900">
                  Actions
                </TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {(filteredNotifications || []).map((notification, index) => (
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
                          onSelectNotification(notification.id, checked as boolean)
                        }
                        className="data-[state=checked]:bg-teal-600 data-[state=checked]:border-teal-600"
                      />
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="text-gray-900 font-medium">
                      {new Date(notification.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                      })}
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="text-gray-900 max-w-xs">
                      <div className="truncate" title={notification.data?.message || notification.type}>
                        {notification.data?.message || notification.type}
                      </div>
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="text-gray-900">
                      {notification.notifiable?.name || notification.notifiable?.email || 'System'}
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="text-gray-900">{notification.type}</div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center gap-2">
                      {getDeliveryStatusIcon(notification.read_at ? 'read' : 'unread')}
                      <span className={getDeliveryStatusColor(notification.read_at ? 'read' : 'unread')}>
                        {getDeliveryStatusText(notification.read_at ? 'read' : 'unread')}
                      </span>
                    </div>
                  </TableCell>
                  <TableCell className="px-6 py-4">
                    <div className="flex items-center justify-center">
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 w-9 p-0 hover:bg-gray-100"
                        title="View Details"
                      >
                        <Eye className="h-4 w-4" />
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        className="h-9 w-9 p-0 hover:bg-gray-100"
                        title="More Actions"
                      >
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </div>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
          
          {/* Empty State */}
          {(!filteredNotifications || filteredNotifications.length === 0) && (
            <div className="py-12 text-center">
              <div className="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <Search className="w-8 h-8 text-gray-400" />
              </div>
              <h3 className="text-lg font-medium text-gray-900 mb-2">
                {!notifications?.data ? 'Loading notifications...' : 'No notifications found'}
              </h3>
              <p className="text-gray-500">
                {!notifications?.data 
                  ? 'Please wait while we fetch your notifications'
                  : (searchQuery || roleFilter !== 'all' || statusFilter !== 'all'
                    ? 'No notifications match your current filters' 
                    : "No notifications available")}
              </p>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
