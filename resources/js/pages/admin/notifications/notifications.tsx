import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';

import { Breadcrumbs } from '@/components/breadcrumbs';
import {
  NotificationHistory,
  ScheduledNotifications,
  CompletedClasses,
  UrgentActions
} from './components';

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

interface UrgentAction {
  id: number;
  title: string;
  count: number;
  actionText: string;
  actionUrl: string;
}

interface Props {
  notifications: {
    data: Notification[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  urgentActions: UrgentAction[];
  scheduledNotifications?: any[];
  completedClasses?: any[];
  templates?: any[];
  users?: any[];
}

export default function AdminNotificationsPage({ notifications, urgentActions, scheduledNotifications, completedClasses, templates, users }: Props) {
  // Ensure we have valid data
  const safeNotifications = notifications || { data: [], current_page: 1, last_page: 1, per_page: 20, total: 0 };
  const safeUrgentActions = urgentActions || [];
  const [activeTab, setActiveTab] = useState<'history' | 'scheduled' | 'completed'>('history');
  const [searchQuery, setSearchQuery] = useState('');
  const [roleFilter, setRoleFilter] = useState('all');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedNotifications, setSelectedNotifications] = useState<string[]>([]);
  const [currentNotifications, setCurrentNotifications] = useState(safeNotifications);
  const [isLoading, setIsLoading] = useState(false);

  const handleSelectAll = (checked: boolean) => {
    if (checked) {
      setSelectedNotifications((currentNotifications?.data || []).map(n => n.id));
    } else {
      setSelectedNotifications([]);
    }
  };

  const handleSelectNotification = (id: string, checked: boolean) => {
    if (checked) {
      setSelectedNotifications(prev => [...prev, id]);
    } else {
      setSelectedNotifications(prev => prev.filter(n => n !== id));
    }
  };

  const handleSearch = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(`/admin/notifications/search?${new URLSearchParams({
        search: searchQuery,
        role: roleFilter,
        status: statusFilter,
      })}`);

      if (response.ok) {
        const data = await response.json();
        setCurrentNotifications(data);
      }
    } catch (error) {
      console.error('Error searching notifications:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleClearFilters = () => {
    setSearchQuery('');
    setRoleFilter('all');
    setStatusFilter('all');
    setCurrentNotifications(safeNotifications);
  };



  return (
    <AdminLayout pageTitle="Notifications System" showRightSidebar={false}>
      <Head title="Notifications System" />
      <div className="container py-6">

        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Notifications System', href: '/admin/notifications' },
              { title: 'Notifications', href: '/admin/notifications' },
            ]}
          />
        </div>
        {/* Urgent / Action Required Section */}
        <UrgentActions urgentActions={safeUrgentActions} />

        {/* Tab Navigation */}
        <div className="flex items-center justify-between mb-6">
          <div className="flex space-x-1 bg-gray-100 p-1 rounded-lg">
            <button
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${activeTab === 'history'
                ? 'bg-teal-600 text-white shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
                }`}
              onClick={() => setActiveTab('history')}
            >
              Notification History
            </button>
            <button
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${activeTab === 'scheduled'
                ? 'bg-teal-600 text-white shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
                }`}
              onClick={() => setActiveTab('scheduled')}
            >
              Scheduled Notifications
            </button>
            <button
              className={`px-4 py-2 text-sm font-medium rounded-md transition-colors ${activeTab === 'completed'
                ? 'bg-teal-600 text-white shadow-sm'
                : 'text-gray-600 hover:text-gray-900'
                }`}
              onClick={() => setActiveTab('completed')}
            >
              Completed Classes
            </button>
          </div>

          <div className="flex gap-2">
            <Button
              variant="outline"
              onClick={() => router.visit('/admin/notifications/auto-triggers')}
              className="border-teal-600 text-teal-600 hover:bg-teal-50"
            >
              Auto-Notification Table
            </Button>
            <Button
              onClick={() => router.visit('/admin/notifications/create')}
              className="bg-teal-600 hover:bg-teal-700 text-white"
            >
              Create New Notification
            </Button>
          </div>
        </div>

        {/* Tab Content */}
        {activeTab === 'history' && (
          <NotificationHistory
            notifications={currentNotifications}
            selectedNotifications={selectedNotifications}
            onSelectAll={handleSelectAll}
            onSelectNotification={handleSelectNotification}
            onSearch={handleSearch}
            onClearFilters={handleClearFilters}
            searchQuery={searchQuery}
            setSearchQuery={setSearchQuery}
            roleFilter={roleFilter}
            setRoleFilter={setRoleFilter}
            statusFilter={statusFilter}
            setStatusFilter={setStatusFilter}
            isLoading={isLoading}
          />
        )}

        {activeTab === 'scheduled' && <ScheduledNotifications scheduledNotifications={scheduledNotifications} templates={templates} users={users} />}

        {activeTab === 'completed' && <CompletedClasses completedClasses={completedClasses} />}

        {/* Selected Actions */}
        {selectedNotifications.length > 0 && (
          <div className="mt-4 p-4 bg-gray-50 rounded-md border">
            <div className="flex items-center justify-between">
              <span className="text-sm text-gray-600">
                {selectedNotifications.length} notification(s) selected
              </span>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  Mark as Read
                </Button>
                <Button variant="outline" size="sm">
                  Resend
                </Button>
                <Button variant="outline" size="sm" className="text-red-600">
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