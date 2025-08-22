import React, { useMemo, useState } from 'react';
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
import { toast } from 'sonner';

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
  const [isBatching, setIsBatching] = useState<null | 'resend' | 'delete' | 'read'>(null);

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

  const fetchWithCsrf = async (url: string, init: RequestInit) => {
    const getCookie = (name: string) => {
      const match = document.cookie.match(new RegExp('(^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
      return match ? decodeURIComponent(match[2]) : undefined;
    };
    let csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || '';
    if (!csrf) csrf = getCookie('XSRF-TOKEN') || '';
    if (!csrf) {
      await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' });
      csrf = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content || getCookie('XSRF-TOKEN') || '';
    }
    const headers = new Headers(init.headers as HeadersInit);
    if (csrf) {
      headers.set('X-CSRF-TOKEN', csrf);
      headers.set('X-XSRF-TOKEN', csrf);
    }
    headers.set('X-Requested-With', 'XMLHttpRequest');
    if (!headers.has('Accept')) headers.set('Accept', 'application/json');
    return fetch(url, { ...init, headers, credentials: 'same-origin' });
  };

  const handleSearch = async () => {
    setIsLoading(true);
    try {
      const response = await fetch(`/admin/notifications/search?${new URLSearchParams({
        search: searchQuery,
        role: roleFilter,
        status: statusFilter,
        page: '1',
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

  const handlePreview = (n: Notification) => {
    router.visit(`/admin/notifications/${n.id}/details`);
  };

  const handlePageChange = async (page: number) => {
    if (page < 1 || page > (currentNotifications?.last_page || 1)) return;
    setIsLoading(true);
    try {
      const response = await fetch(`/admin/notifications/search?${new URLSearchParams({
        search: searchQuery,
        role: roleFilter,
        status: statusFilter,
        page: String(page),
      })}`);
      if (response.ok) {
        const data = await response.json();
        setCurrentNotifications(data);
        setSelectedNotifications([]);
      }
    } catch (error) {
      console.error('Error changing page:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const refreshCurrentPage = async () => {
    await handlePageChange(currentNotifications.current_page || 1);
  };

  const handleBatchResend = async () => {
    if (selectedNotifications.length === 0) return;
    setIsBatching('resend');
    try {
      await Promise.allSettled(
        selectedNotifications.map(id => fetchWithCsrf(`/admin/notifications/${id}/resend`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) }))
      );
      toast.success('Resend triggered for selected notifications');
    } catch (e) {
      toast.error('Failed to resend some notifications');
    } finally {
      setIsBatching(null);
    }
  };

  const handleBatchDelete = async () => {
    if (selectedNotifications.length === 0) return;
    if (!confirm(`Delete ${selectedNotifications.length} selected notification(s)? This cannot be undone.`)) return;
    setIsBatching('delete');
    try {
      await Promise.allSettled(
        selectedNotifications.map(id => fetchWithCsrf(`/admin/notifications/${id}`, { method: 'DELETE' }))
      );
      toast.success('Selected notifications deleted');
      setSelectedNotifications([]);
      await refreshCurrentPage();
    } catch (e) {
      toast.error('Failed to delete some notifications');
    } finally {
      setIsBatching(null);
    }
  };

  const handleBatchMarkRead = async () => {
    setIsBatching('read');
    toast.message('Mark as Read is not yet implemented');
    setIsBatching(null);
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
            onPreview={handlePreview}
            onPageChange={handlePageChange}
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
                <Button variant="outline" size="sm" onClick={handleBatchMarkRead} disabled={!!isBatching}>
                  {isBatching === 'read' ? 'Marking...' : 'Mark as Read'}
                </Button>
                <Button variant="outline" size="sm" onClick={handleBatchResend} disabled={!!isBatching}>
                  {isBatching === 'resend' ? 'Resending...' : 'Resend'}
                </Button>
                <Button variant="outline" size="sm" className="text-red-600" onClick={handleBatchDelete} disabled={!!isBatching}>
                  {isBatching === 'delete' ? 'Deleting...' : 'Delete Selected'}
                </Button>
              </div>
            </div>
          </div>
        )}
      </div>

      
    </AdminLayout>
  );
} 