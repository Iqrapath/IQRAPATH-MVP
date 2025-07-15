import React from 'react';
import { Head } from '@inertiajs/react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
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

export default function GuardianNotificationCreatePage({ 
  templates = [],
  preselectedUsers = []
}: NotificationCreatePageProps) {
  return (
    <GuardianLayout pageTitle="Create Message">
      <Head title="Create Message" />
      
      <NotificationCreate
        userRole="guardian"
        templates={templates}
        preselectedUsers={preselectedUsers}
        defaultType="message"
        isMessage={true}
        backUrl="/guardian/notifications"
        submitEndpoint="/guardian/notifications"
      />
    </GuardianLayout>
  );
} 