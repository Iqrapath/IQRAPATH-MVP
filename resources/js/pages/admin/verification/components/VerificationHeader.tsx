import React from 'react';
import { Breadcrumbs } from '@/components/breadcrumbs';

interface VerificationHeaderProps {
  teacherName?: string;
}

export default function VerificationHeader({ teacherName }: VerificationHeaderProps) {
  const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Verification Requests', href: '/admin/verification' },
    ...(teacherName ? [{ title: teacherName, href: '#' }] : [])
  ];

  return (
    <div className="mb-6">
      {/* Breadcrumbs */}
      <div className="mb-6">
        <Breadcrumbs breadcrumbs={breadcrumbs} />
      </div>

      {/* Page Description */}
      <div className="mb-6">
        <h1 className="text-2xl font-semibold text-gray-900 mb-2">Verification Request</h1>
        <p className="text-gray-600">
          Review teacher documents and conduct live video verification before approving full access to the platform.
        </p>
      </div>
    </div>
  );
}
