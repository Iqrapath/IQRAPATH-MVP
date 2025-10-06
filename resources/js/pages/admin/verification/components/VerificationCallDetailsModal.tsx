import React from 'react';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import VerificationCallDetailsCard from './VerificationCallDetailsCard';

interface Props {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  call: {
    id: number | string;
    scheduled_at: string;
    platform: string;
    meeting_link?: string;
    notes?: string;
    status: string;
  } | null;
  verificationRequestId?: number | string;
  videoStatus?: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
  requestStatus?: 'pending' | 'verified' | 'rejected' | 'live_video';
}

export default function VerificationCallDetailsModal({
  isOpen,
  onOpenChange,
  call,
  verificationRequestId,
  videoStatus,
  requestStatus
}: Props) {
  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="max-w-2xl">
        <DialogHeader>
          <DialogTitle>Verification Call Details</DialogTitle>
          <DialogDescription>
            View and manage the scheduled verification call details.
          </DialogDescription>
        </DialogHeader>
        
        <VerificationCallDetailsCard
          call={call}
          verificationRequestId={verificationRequestId}
          videoStatus={videoStatus}
          requestStatus={requestStatus}
        />
      </DialogContent>
    </Dialog>
  );
}
