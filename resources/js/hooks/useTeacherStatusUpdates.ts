import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { toast } from 'sonner';

interface TeacherStatusUpdate {
  teacher_id: number;
  status: string;
  can_approve: boolean;
  approval_block_reason: string | null;
  verification_request_id: number | null;
  last_updated: string;
  action: string;
  timestamp: string;
}

interface UseTeacherStatusUpdatesProps {
  onStatusUpdate?: (update: TeacherStatusUpdate) => void;
  onTeacherApproved?: (teacherId: number) => void;
  onTeacherRejected?: (teacherId: number) => void;
}

export function useTeacherStatusUpdates({
  onStatusUpdate,
  onTeacherApproved,
  onTeacherRejected,
}: UseTeacherStatusUpdatesProps = {}) {
  const { auth } = usePage().props;
  const [isConnected, setIsConnected] = useState(false);
  const [connectionError, setConnectionError] = useState<string | null>(null);

  useEffect(() => {
    // Only connect for admin users
    if (auth.user?.role !== 'admin' && auth.user?.role !== 'super-admin') {
      return;
    }

    // Initialize Echo if available
    if (typeof window !== 'undefined' && window.Echo) {
      const channel = window.Echo.private('admin.teachers');

      channel
        .listen('.teacher.status.updated', (data: TeacherStatusUpdate) => {
          console.log('Teacher status updated:', data);
          
          // Call custom handler if provided
          onStatusUpdate?.(data);
          
          // Handle specific actions
          switch (data.action) {
            case 'approved':
              toast.success(`Teacher #${data.teacher_id} has been approved`);
              onTeacherApproved?.(data.teacher_id);
              break;
            case 'rejected':
              toast.error(`Teacher #${data.teacher_id} has been rejected`);
              onTeacherRejected?.(data.teacher_id);
              break;
            case 'updated':
              // Silent update for status changes
              break;
            default:
              console.log('Unknown action:', data.action);
          }
        })
        .error((error: any) => {
          console.error('WebSocket error:', error);
          setConnectionError('Failed to connect to real-time updates');
          setIsConnected(false);
        });

      setIsConnected(true);
      setConnectionError(null);

      return () => {
        channel.stopListening('.teacher.status.updated');
        window.Echo.leave('admin.teachers');
        setIsConnected(false);
      };
    } else {
      console.warn('Echo not available - real-time updates disabled');
      setConnectionError('Real-time updates not available');
    }
  }, [auth.user?.role, onStatusUpdate, onTeacherApproved, onTeacherRejected]);

  return {
    isConnected,
    connectionError,
  };
}

// Hook for individual teacher updates
export function useTeacherStatusUpdate(teacherId: number) {
  const [statusData, setStatusData] = useState<TeacherStatusUpdate | null>(null);

  useEffect(() => {
    if (typeof window !== 'undefined' && window.Echo) {
      const channel = window.Echo.private(`teacher.${teacherId}`);

      channel
        .listen('.teacher.status.updated', (data: TeacherStatusUpdate) => {
          if (data.teacher_id === teacherId) {
            setStatusData(data);
          }
        })
        .error((error: any) => {
          console.error(`WebSocket error for teacher ${teacherId}:`, error);
        });

      return () => {
        channel.stopListening('.teacher.status.updated');
        window.Echo.leave(`teacher.${teacherId}`);
      };
    }
  }, [teacherId]);

  return statusData;
}

// Utility function to refresh teacher status
export function refreshTeacherStatus(teacherId: number) {
  if (typeof window !== 'undefined' && window.axios) {
    return window.axios.get(`/api/teachers/${teacherId}/status`);
  }
  return Promise.resolve(null);
}
