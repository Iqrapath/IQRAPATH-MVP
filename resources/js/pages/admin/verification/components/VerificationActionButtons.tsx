import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { CheckCircle, XCircle, Clock, AlertCircle } from 'lucide-react';

interface VerificationStatus {
  docs_status: 'pending' | 'verified' | 'rejected';
  video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
}

interface Props {
  verificationRequestId: number | string;
  verificationStatus: VerificationStatus;
  onApproved?: () => void;
  onRejected?: () => void;
}

export default function VerificationActionButtons({
  verificationRequestId,
  verificationStatus,
  onApproved,
  onRejected,
}: Props) {
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  const canApprove =
    verificationStatus.docs_status === 'verified' &&
    verificationStatus.video_status === 'passed';

  const getApprovalBlockReason = (): string => {
    if (verificationStatus.docs_status === 'rejected') {
      return 'Documents have been rejected. Teacher must resubmit.';
    }
    if (verificationStatus.docs_status !== 'verified') {
      return 'All documents must be verified first.';
    }
    if (verificationStatus.video_status === 'failed') {
      return 'Video verification failed. Retake required.';
    }
    if (verificationStatus.video_status === 'completed') {
      return 'Video completed but not passed. Review and mark as passed.';
    }
    if (verificationStatus.video_status === 'scheduled') {
      return 'Video verification is scheduled but not completed.';
    }
    if (verificationStatus.video_status === 'not_scheduled') {
      return 'Video verification not scheduled yet.';
    }
    return '';
  };

  const getButtonText = (): string => {
    if (verificationStatus.docs_status !== 'verified') return 'Documents Pending';
    if (verificationStatus.video_status !== 'passed') return 'Video Pending';
    return 'Approve';
  };

  const handleApprove = async () => {
    if (!canApprove) {
      toast.error(getApprovalBlockReason());
      return;
    }
    try {
      setIsLoading(true);
      await router.patch(route('admin.verification.approve', verificationRequestId));
      toast.success('Verification approved successfully');
      onApproved?.();
    } catch (e) {
      toast.error('Failed to approve verification');
    } finally {
      setIsLoading(false);
    }
  };

  const handleReject = async () => {
    if (!rejectionReason.trim()) {
      toast.error('Please provide a rejection reason');
      return;
    }
    try {
      setIsLoading(true);
      await router.patch(route('admin.verification.reject', verificationRequestId), {
        rejection_reason: rejectionReason.trim(),
      });
      toast.success('Verification rejected');
      setShowRejectModal(false);
      setRejectionReason('');
      onRejected?.();
    } catch (e) {
      toast.error('Failed to reject verification');
    } finally {
      setIsLoading(false);
    }
  };

  const handleDeleteAccount = async () => {
    toast.info('Delete account action is not yet implemented');
  };

  return (
    <>
      <div className="flex items-start justify-start gap-6">
        <Button
          onClick={handleApprove}
          disabled={!canApprove || isLoading}
          className={`px-6 py-2 rounded-full ${
            canApprove
              ? 'bg-teal-600 hover:bg-teal-700 text-white'
              : verificationStatus.docs_status === 'rejected' || verificationStatus.video_status === 'failed'
              ? 'bg-red-100 text-red-600 border border-red-300 cursor-not-allowed'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          }`}
          title={!canApprove ? getApprovalBlockReason() : 'Approve Teacher'}
        >
          {isLoading ? 'Processing...' : getButtonText()}
        </Button>

        <Button
          variant="outline"
          className="border-teal-600 text-teal-600 hover:bg-teal-50 px-6 py-2 rounded-full"
          onClick={() => toast.info('Messaging not implemented yet')}
          disabled={isLoading}
        >
          Send Message
        </Button>

        <button
          onClick={() => setShowRejectModal(true)}
          disabled={isLoading}
          className="text-gray-700 hover:text-gray-900 font-medium disabled:opacity-50"
        >
          Reject
        </button>

        <button
          onClick={handleDeleteAccount}
          disabled={isLoading}
          className="text-red-600 hover:text-red-700 font-medium disabled:opacity-50"
        >
          Delete Account
        </button>
      </div>

      <Dialog open={showRejectModal} onOpenChange={setShowRejectModal}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Reject Verification</DialogTitle>
            <DialogDescription>
              Provide a reason for rejection. The teacher will be notified.
            </DialogDescription>
          </DialogHeader>
          <div className="grid gap-4 py-4">
            <div className="grid gap-2">
              <Label htmlFor="rejection-reason">Rejection Reason</Label>
              <Textarea
                id="rejection-reason"
                placeholder="Enter the reason for rejection..."
                value={rejectionReason}
                onChange={(e) => setRejectionReason(e.target.value)}
                className="min-h-[100px]"
                maxLength={1000}
              />
              <div className="text-sm text-gray-500 text-right">
                {rejectionReason.length}/1000 characters
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={() => setShowRejectModal(false)} disabled={isLoading}>
              Cancel
            </Button>
            <Button onClick={handleReject} disabled={isLoading || !rejectionReason.trim()} className="bg-red-600 hover:bg-red-700">
              {isLoading ? 'Processing...' : 'Reject'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
