import React from 'react';
import { Head } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import NotificationCreate from '@/components/notification/notification-create';

interface User {
  id: number;
  name: string;
  email: string;
  role?: string;
  avatar?: string;
}

interface Template {
  id: number;
  name: string;
  title: string;
  body: string;
  type: string;
}

interface NotificationCreatePageProps {
  templates?: Template[];
  preselectedUsers?: User[];
}

export default function StudentNotificationCreatePage({ 
  templates = [],
  preselectedUsers = []
}: NotificationCreatePageProps) {
  return (
    <StudentLayout pageTitle="Create Message">
      <Head title="Create Message" />
      
      <NotificationCreate
        userRole="student"
        templates={templates}
        preselectedUsers={preselectedUsers}
        defaultType="message"
        isMessage={true}
        backUrl="/student/notifications"
        submitEndpoint="/student/notifications"
      />
    </StudentLayout>
  );
} 