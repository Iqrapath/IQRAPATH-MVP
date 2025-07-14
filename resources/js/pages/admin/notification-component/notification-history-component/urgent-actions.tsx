import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface UrgentActionsProps {
  urgentActions: {
    withdrawalRequests: number;
    teacherApplications: number;
    pendingSessions: number;
    reportedDisputes: number;
  };
}

export default function UrgentActions({ urgentActions }: UrgentActionsProps) {
  return (
    <div className="bg-gray-50 rounded-lg p-6 mb-8">
      <h2 className="text-lg font-semibold mb-4">Urgent / Action Required</h2>
      
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <span className="text-gray-700">{urgentActions.withdrawalRequests} Withdrawal Requests Pending Approval</span>
          <Link href="/admin/withdrawal-requests">
            <Button variant="link" className="text-teal-600 hover:text-teal-700">
              View Requests
            </Button>
          </Link>
        </div>
        
        <div className="flex items-center justify-between">
          <span className="text-gray-700">{urgentActions.teacherApplications} Teacher Applications Awaiting Verification</span>
          <Link href="/admin/teacher-applications">
            <Button variant="link" className="text-teal-600 hover:text-teal-700">
              Review Now
            </Button>
          </Link>
        </div>
        
        <div className="flex items-center justify-between">
          <span className="text-gray-700">{urgentActions.pendingSessions} Sessions Pending Teacher Assignment</span>
          <Link href="/admin/pending-sessions">
            <Button variant="link" className="text-teal-600 hover:text-teal-700">
              Assign Teachers
            </Button>
          </Link>
        </div>
        
        <div className="flex items-center justify-between">
          <span className="text-gray-700">{urgentActions.reportedDisputes} Reported Dispute Requires Resolution</span>
          <Link href="/admin/disputes">
            <Button variant="link" className="text-teal-600 hover:text-teal-700">
              Open Dispute
            </Button>
          </Link>
        </div>
      </div>
    </div>
  );
} 