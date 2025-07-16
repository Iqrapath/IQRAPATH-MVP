import React from 'react';
import NotificationDropdown from '@/components/notification-dropdown';
import { cn } from '@/lib/utils';
import { Calendar, BookOpen, Clock, Users } from 'lucide-react';

interface TeacherNotificationDropdownProps {
  className?: string;
  triggerClassName?: string;
}

export default function TeacherNotificationDropdown({
  className = '',
  triggerClassName = ''
}: TeacherNotificationDropdownProps) {
  // Teacher-specific styling
  const teacherStyles = {
    dropdownClass: cn(
      'border-blue-200 shadow-lg shadow-blue-100',
      className
    ),
    triggerClass: cn(
      'bg-blue-50 hover:bg-blue-100 text-blue-600',
      triggerClassName
    )
  };

  // Teacher-specific notification type colors
  const teacherTypeColors = {
    'session': 'bg-blue-100 text-blue-800',
    'request': 'bg-teal-100 text-teal-800',
    'teaching': 'bg-cyan-100 text-cyan-800',
    'schedule': 'bg-sky-100 text-sky-800'
  };

  // Teacher-specific notification icons
  const teacherIcons = {
    'session': <Users className="h-5 w-5 text-blue-500" />,
    'request': <BookOpen className="h-5 w-5 text-teal-500" />,
    'teaching': <BookOpen className="h-5 w-5 text-cyan-500" />,
    'schedule': <Calendar className="h-5 w-5 text-sky-500" />
  };

  return (
    <NotificationDropdown
      userRole="teacher"
      viewAllLink="/teacher/notifications"
      notificationDetailBaseUrl="/teacher/notification"
      className={teacherStyles.dropdownClass}
      triggerClassName={teacherStyles.triggerClass}
      customTypeColors={teacherTypeColors}
      customIcons={teacherIcons}
      headerTitle="Teaching Notifications"
      emptyStateMessage="No teaching notifications"
    />
  );
} 