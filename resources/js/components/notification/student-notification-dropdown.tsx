import React from 'react';
import NotificationDropdown from '@/components/notification-dropdown';
import { cn } from '@/lib/utils';
import { BookOpen, GraduationCap, BarChart, Calendar } from 'lucide-react';

interface StudentNotificationDropdownProps {
  className?: string;
  triggerClassName?: string;
}

export default function StudentNotificationDropdown({
  className = '',
  triggerClassName = ''
}: StudentNotificationDropdownProps) {
  // Student-specific styling
  const studentStyles = {
    dropdownClass: cn(
      'border-green-200 shadow-lg shadow-green-100',
      className
    ),
    triggerClass: cn(
      'bg-green-50 hover:bg-green-100 text-green-600',
      triggerClassName
    )
  };

  // Student-specific notification type colors
  const studentTypeColors = {
    'lesson': 'bg-green-100 text-green-800',
    'assignment': 'bg-emerald-100 text-emerald-800',
    'progress': 'bg-lime-100 text-lime-800',
    'schedule': 'bg-teal-100 text-teal-800'
  };

  // Student-specific notification icons
  const studentIcons = {
    'lesson': <BookOpen className="h-5 w-5 text-green-500" />,
    'assignment': <GraduationCap className="h-5 w-5 text-emerald-500" />,
    'progress': <BarChart className="h-5 w-5 text-lime-500" />,
    'schedule': <Calendar className="h-5 w-5 text-teal-500" />
  };

  return (
    <NotificationDropdown
      userRole="student"
      viewAllLink="/student/notifications"
      notificationDetailBaseUrl="/student/notification"
      className={studentStyles.dropdownClass}
      triggerClassName={studentStyles.triggerClass}
      customTypeColors={studentTypeColors}
      customIcons={studentIcons}
      headerTitle="Learning Updates"
      emptyStateMessage="No learning updates"
    />
  );
} 