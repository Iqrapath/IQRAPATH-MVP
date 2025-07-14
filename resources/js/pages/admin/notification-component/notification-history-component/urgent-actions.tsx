import { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import axios from 'axios';

interface UrgentActionsProps {
  // Optional initial data that might be passed from the server
  urgentActions?: {
    withdrawalRequests: number;
    teacherApplications: number;
    pendingSessions: number;
    reportedDisputes: number;
  };
}

export default function UrgentActions({ urgentActions: initialData }: UrgentActionsProps) {
  const [urgentActions, setUrgentActions] = useState(initialData || {
    withdrawalRequests: 0,
    teacherApplications: 0,
    pendingSessions: 0,
    reportedDisputes: 0
  });
  const [loading, setLoading] = useState(!initialData);

  useEffect(() => {
    if (!initialData) {
      fetchUrgentActions();
    }
  }, [initialData]);

  const fetchUrgentActions = async () => {
    try {
      setLoading(true);
      const response = await axios.get('/api/admin/urgent-actions');
      setUrgentActions(response.data);
    } catch (error) {
      console.error('Failed to fetch urgent actions:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-gray-50 rounded-lg p-6 mb-8">
      <div className="flex justify-between items-center mb-4">
        <h2 className="text-lg font-semibold">Urgent / Action Required</h2>
        {loading && <span className="text-sm text-gray-500">Loading...</span>}
      </div>
      
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