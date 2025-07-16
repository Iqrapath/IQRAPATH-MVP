import React from 'react';
import NotificationDropdown from '@/components/notification-dropdown';
import { cn } from '@/lib/utils';
import { ShieldAlert, Shield, Bell, BellRing } from 'lucide-react';

interface AdminNotificationDropdownProps {
  className?: string;
  triggerClassName?: string;
}

export default function AdminNotificationDropdown({
  className = '',
  triggerClassName = ''
}: AdminNotificationDropdownProps) {
  // Admin-specific styling
  const adminStyles = {
    dropdownClass: cn(
      'border-primary/20 shadow-lg shadow-primary/10',
      className
    ),
    triggerClass: cn(
      'bg-primary/5 hover:bg-primary/10 text-primary',
      triggerClassName
    )
  };

  // Admin-specific notification type colors
  const adminTypeColors = {
    'admin': 'bg-purple-100 text-purple-800',
    'system': 'bg-indigo-100 text-indigo-800',
    'alert': 'bg-red-100 text-red-800',
    'security': 'bg-orange-100 text-orange-800'
  };

  // Admin-specific notification icons
  const adminIcons = {
    'admin': <Shield className="h-5 w-5 text-purple-500" />,
    'system': <Bell className="h-5 w-5 text-indigo-500" />,
    'alert': <ShieldAlert className="h-5 w-5 text-red-500" />,
    'security': <BellRing className="h-5 w-5 text-orange-500" />
  };

  return (
    <NotificationDropdown
      userRole="admin"
      viewAllLink="/admin/notification"
      notificationDetailBaseUrl="/admin/notification"
      className={adminStyles.dropdownClass}
      triggerClassName={adminStyles.triggerClass}
      customTypeColors={adminTypeColors}
      customIcons={adminIcons}
      headerTitle="Admin Notifications"
      emptyStateMessage="No administrative notifications"
    />
  );
} 