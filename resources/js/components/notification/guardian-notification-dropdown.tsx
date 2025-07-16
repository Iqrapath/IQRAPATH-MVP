import React from 'react';
import NotificationDropdown from '@/components/notification-dropdown';
import { cn } from '@/lib/utils';
import { Users, CreditCard, BarChart, Clock } from 'lucide-react';

interface GuardianNotificationDropdownProps {
  className?: string;
  triggerClassName?: string;
}

export default function GuardianNotificationDropdown({
  className = '',
  triggerClassName = ''
}: GuardianNotificationDropdownProps) {
  // Guardian-specific styling
  const guardianStyles = {
    dropdownClass: cn(
      'border-amber-200 shadow-lg shadow-amber-100',
      className
    ),
    triggerClass: cn(
      'bg-amber-50 hover:bg-amber-100 text-amber-600',
      triggerClassName
    )
  };

  // Guardian-specific notification type colors
  const guardianTypeColors = {
    'child': 'bg-amber-100 text-amber-800',
    'payment': 'bg-yellow-100 text-yellow-800',
    'progress': 'bg-orange-100 text-orange-800',
    'attendance': 'bg-amber-100 text-amber-800'
  };

  // Guardian-specific notification icons
  const guardianIcons = {
    'child': <Users className="h-5 w-5 text-amber-500" />,
    'payment': <CreditCard className="h-5 w-5 text-yellow-500" />,
    'progress': <BarChart className="h-5 w-5 text-orange-500" />,
    'attendance': <Clock className="h-5 w-5 text-amber-500" />
  };

  return (
    <NotificationDropdown
      userRole="guardian"
      viewAllLink="/guardian/notifications"
      notificationDetailBaseUrl="/guardian/notification"
      className={guardianStyles.dropdownClass}
      triggerClassName={guardianStyles.triggerClass}
      customTypeColors={guardianTypeColors}
      customIcons={guardianIcons}
      headerTitle="Guardian Updates"
      emptyStateMessage="No guardian updates"
    />
  );
} 