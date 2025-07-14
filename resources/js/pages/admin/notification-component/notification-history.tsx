import { Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Head } from '@inertiajs/react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { type BreadcrumbItem } from '@/types';
import History from './notification-history-component/history';
import Scheduled from './notification-history-component/scheduled';
import Completed from './notification-history-component/completed';
import { Button } from '@/components/ui/button';
import { Tabs, TabsList, TabsTrigger, TabsContent } from '@/components/ui/tabs';
import UrgentActions from './notification-history-component/urgent-actions';

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
  scheduledNotifications?: {
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
  completedClasses?: {
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
  scheduledNotifications = { data: [] },
  completedClasses = { data: [] },
  urgentActions = {
    withdrawalRequests: 0,
    teacherApplications: 0,
    pendingSessions: 0,
    reportedDisputes: 0
  },
  filters = {} 
}: NotificationHistoryProps) {
  // Breadcrumb items
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Notifications', href: '/admin/notifications' },
    // { title: 'Notification History', href: '/admin/notification-history' },
    { title: 'Notification History', href: '#' },
  ];

  return (
    <AdminLayout pageTitle="Notification History" showRightSidebar={false}>
      <Head title="Notification History" />
      <div className="py-6">
        {/* Breadcrumbs */}
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
      
        {/* Urgent Action Required Section */}
        <UrgentActions urgentActions={urgentActions} />

        {/* Notification Tabs */}
        <Tabs defaultValue="history" className="mb-8">
          <TabsList className="bg-white h-16 p-3 rounded-lg space-x-4 shadow-md">
            <TabsTrigger 
              value="history" 
              className="font-medium text-base rounded-lg data-[state=active]:bg-[#338078] data-[state=active]:text-white data-[state=active]:shadow-md"
            >
              Notification History
            </TabsTrigger>
            <TabsTrigger 
              value="scheduled" 
              className="font-medium text-base rounded-lg data-[state=active]:bg-[#338078] data-[state=active]:text-white data-[state=active]:shadow-md"
            >
              Scheduled Notifications
            </TabsTrigger>
            <TabsTrigger 
              value="completed" 
              className="font-medium text-base rounded-lg data-[state=active]:bg-[#338078] data-[state=active]:text-white data-[state=active]:shadow-md"
            >
              Completed Classes
            </TabsTrigger>
          </TabsList>
          
          <TabsContent value="history" className="mt-4">
            <History notifications={notifications} filters={filters} />
          </TabsContent>

          <TabsContent value="scheduled" className="mt-4">
            <Scheduled scheduledNotifications={scheduledNotifications} filters={filters} />
          </TabsContent>

          <TabsContent value="completed" className="mt-4">
            <Completed completedClasses={completedClasses} filters={filters} />
          </TabsContent>
        </Tabs>
      </div>
    </AdminLayout>
  );
}
