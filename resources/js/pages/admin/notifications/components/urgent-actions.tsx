import React from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';

interface UrgentAction {
  id: number;
  title: string;
  count: number;
  actionText: string;
  actionUrl: string;
  status?: string; // Optional status like "Pending Approval", "Awaiting Verification", etc.
}

interface Props {
  urgentActions: UrgentAction[];
}

export default function UrgentActions({ urgentActions }: Props) {
  const safeUrgentActions = urgentActions || [];

  return (
    <div className="mb-8">
      <div className="mb-4">
        <h2 className="text-xl font-semibold text-gray-900">Urgent / Action Required</h2>
      </div>
      
      <div>
        <div className="divide-y divide-gray-200">
          {safeUrgentActions.map((action, index) => (
            <div key={action.id} className="p-4">
              <div className="flex items-center justify-between">
                <div className="flex-1">
                  <p className="text-base text-gray-900">
                    {action.count} {action.title}
                  </p>
                </div>
                <div className="ml-4">
                  <Button
                    variant="link"
                    className="text-teal-600 hover:text-teal-700 p-0 h-auto font-medium"
                    onClick={() => router.get(action.actionUrl)}
                  >
                    {action.actionText}
                  </Button>
                </div>
              </div>
            </div>
          ))}
        </div>
        
        {safeUrgentActions.length === 0 && (
          <div className="p-6 text-center text-gray-500">
            No urgent actions required
          </div>
        )}
      </div>
    </div>
  );
}
