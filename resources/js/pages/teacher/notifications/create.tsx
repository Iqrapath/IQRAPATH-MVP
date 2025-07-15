import React from 'react';
import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
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

export default function TeacherNotificationCreatePage({ 
  templates = [],
  preselectedUsers = []
}: NotificationCreatePageProps) {
  return (
    <TeacherLayout pageTitle="Create Message">
      <Head title="Create Message" />
      
      <NotificationCreate
        userRole="teacher"
        templates={templates}
        preselectedUsers={preselectedUsers}
        defaultType="message"
        isMessage={true}
        backUrl="/teacher/notifications"
        submitEndpoint="/teacher/notifications"
      />
    </TeacherLayout>
  );
} 