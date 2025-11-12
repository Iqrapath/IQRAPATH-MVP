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
  teacherId: number;
  verificationStatus: VerificationStatus;
  onApprove?: () => void;
  onSendMessage?: () => void;
  onReject?: () => void;
  onDeleteAccount?: () => void;
  onRefresh?: () => void;
}

export default function TeacherActionButtons({ 
  teacherId, 
  verificationStatus,
  onApprove, 
  onSendMessage, 
  onReject, 
  onDeleteAccount,
  onRefresh
}: Props) {
  const [showRejectModal, setShowRejectModal] = useState(false);
  const [rejectionReason, setRejectionReason] = useState('');
  const [isLoading, setIsLoading] = useState(false);

  // Check if teacher can be approved
  const canApprove = verificationStatus.docs_status === 'verified' && 
                     verificationStatus.video_status === 'passed';

  // Get approval block reason with more detailed information
  const getApprovalBlockReason = (): string => {
    if (verificationStatus.docs_status === 'rejected') {
      return 'Documents have been rejected. Teacher must resubmit documents.';
    }
    if (verificationStatus.docs_status === 'pending') {
      return 'Documents are pending verification. Please verify all required documents first.';
    }
    if (verificationStatus.docs_status !== 'verified') {
      return 'All documents must be verified first';
    }
    if (verificationStatus.video_status === 'failed') {
      return 'Video verification failed. Teacher must retake the video verification.';
    }
    if (verificationStatus.video_status === 'not_scheduled') {
      return 'Video verification has not been scheduled yet.';
    }
    if (verificationStatus.video_status === 'scheduled') {
      return 'Video verification is scheduled but not completed.';
    }
    if (verificationStatus.video_status === 'completed') {
      return 'Video verification completed but not yet passed. Please review and mark as passed.';
    }
    if (verificationStatus.video_status !== 'passed') {
      return 'Video verification must be completed and passed';
    }
    return '';
  };

  // Get button text based on verification status
  const getButtonText = (): string => {
    if (verificationStatus.docs_status === 'rejected') {
      return 'Documents Rejected';
    }
    if (verificationStatus.docs_status === 'pending') {
      return 'Documents Pending';
    }
    if (verificationStatus.docs_status !== 'verified') {
      return 'Documents Required';
    }
    if (verificationStatus.video_status === 'failed') {
      return 'Video Failed';
    }
    if (verificationStatus.video_status === 'not_scheduled') {
      return 'Video Not Scheduled';
    }
    if (verificationStatus.video_status === 'scheduled') {
      return 'Video Scheduled';
    }
    if (verificationStatus.video_status === 'completed') {
      return 'Video Completed';
    }
    if (verificationStatus.video_status !== 'passed') {
      return 'Video Required';
    }
    return 'Approve';
  };

  const handleApprove = async () => {
    if (!canApprove) {
      toast.error(getApprovalBlockReason());
      return;
    }

    try {
      setIsLoading(true);
      await router.patch(`/admin/teachers/${teacherId}/approve`);
      toast.success('Teacher approved successfully!');
      if (onApprove) onApprove();
    } catch (error) {
      toast.error('Failed to approve teacher');
      console.error('Approve error:', error);
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
      await router.patch(`/admin/teachers/${teacherId}/reject`, {
        rejection_reason: rejectionReason.trim()
      });
      toast.success('Teacher rejected successfully!');
      setShowRejectModal(false);
      setRejectionReason('');
      if (onReject) onReject();
      // Refresh the page to show updated verification status
      if (onRefresh) onRefresh();
    } catch (error) {
      toast.error('Failed to reject teacher');
      console.error('Reject error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  const handleDeleteAccount = async () => {
    if (!confirm('Are you sure you want to delete this teacher account? This action cannot be undone.')) {
      return;
    }

    try {
      setIsLoading(true);
      // TODO: Implement delete account functionality
      toast.info('Delete account functionality coming soon');
      if (onDeleteAccount) onDeleteAccount();
    } catch (error) {
      toast.error('Failed to delete account');
      console.error('Delete error:', error);
    } finally {
      setIsLoading(false);
    }
  };

  return (
    <>
      <div className="flex items-start justify-start gap-6 ">
        {/* Approve Button - Disabled until conditions met */}
        <Button 
          onClick={handleApprove}
          disabled={!canApprove || isLoading}
          className={`px-6 py-2 rounded-full ${
            canApprove 
              ? 'bg-teal-600 hover:bg-teal-700 text-white cursor-pointer' 
              : verificationStatus.docs_status === 'rejected' || verificationStatus.video_status === 'failed'
              ? 'bg-red-100 text-red-600 border border-red-300 cursor-not-allowed'
              : 'bg-gray-300 text-gray-500 cursor-not-allowed'
          }`}
          title={!canApprove ? getApprovalBlockReason() : 'Approve Teacher'}
        >
          {isLoading ? 'Processing...' : getButtonText()}
        </Button>

        {/* Send Message Button - Teal outlined */}
        <Button 
          onClick={onSendMessage}
          disabled={isLoading}
          variant="outline"
          className="border-teal-600 text-teal-600 hover:bg-teal-50 px-6 py-2 rounded-full cursor-pointer"
        >
          Send Message
        </Button>

        {/* Reject Link - Dark gray text */}
        <button 
          onClick={() => setShowRejectModal(true)}
          disabled={isLoading}
          className="text-gray-700 hover:text-gray-900 font-medium disabled:opacity-50 cursor-pointer"
        >
          Reject
        </button>

        {/* Delete Account Link - Red text */}
        <button 
          onClick={handleDeleteAccount}
          disabled={isLoading}
          className="text-red-600 hover:text-red-700 font-medium disabled:opacity-50 cursor-pointer"
        >
          Delete Account
        </button>
      </div>

      {/* Verification Status Display */}
      <div className="mt-4 p-4 bg-gray-50 rounded-lg">
        <div className="flex items-center justify-between mb-2">
          <h4 className="text-sm font-medium text-gray-700">Verification Status:</h4>
          {onRefresh && (
            <button
              onClick={onRefresh}
              className="text-xs text-blue-600 hover:text-blue-700 font-medium"
              title="Refresh verification status"
            >
              ↻ Refresh
            </button>
          )}
        </div>
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <span className="font-medium">Documents: </span>
            <span className={`px-2 py-1 rounded text-xs font-medium ${
              verificationStatus.docs_status === 'verified' 
                ? 'bg-green-100 text-green-800' 
                : verificationStatus.docs_status === 'rejected'
                ? 'bg-red-100 text-red-800'
                : 'bg-yellow-100 text-yellow-800'
            }`}>
              {verificationStatus.docs_status.charAt(0).toUpperCase() + verificationStatus.docs_status.slice(1)}
            </span>
            {verificationStatus.docs_status === 'rejected' && (
              <div className="text-xs text-red-600 mt-1">
                Teacher must resubmit documents
              </div>
            )}
            {verificationStatus.docs_status === 'pending' && (
              <div className="text-xs text-yellow-600 mt-1">
                Documents need verification
              </div>
            )}
          </div>
          <div>
            <span className="font-medium">Video: </span>
            <span className={`px-2 py-1 rounded text-xs font-medium ${
              verificationStatus.video_status === 'passed' 
                ? 'bg-green-100 text-green-800' 
                : verificationStatus.video_status === 'failed'
                ? 'bg-red-100 text-red-800'
                : verificationStatus.video_status === 'completed'
                ? 'bg-blue-100 text-blue-800'
                : verificationStatus.video_status === 'scheduled'
                ? 'bg-yellow-100 text-yellow-800'
                : 'bg-gray-100 text-gray-800'
            }`}>
              {verificationStatus.video_status.replace('_', ' ').charAt(0).toUpperCase() + verificationStatus.video_status.replace('_', ' ').slice(1)}
            </span>
            {verificationStatus.video_status === 'failed' && (
              <div className="text-xs text-red-600 mt-1">
                Teacher must retake verification
              </div>
            )}
            {verificationStatus.video_status === 'completed' && (
              <div className="text-xs text-blue-600 mt-1">
                Review and mark as passed
              </div>
            )}
            {verificationStatus.video_status === 'not_scheduled' && (
              <div className="text-xs text-gray-600 mt-1">
                Schedule video verification
              </div>
            )}
          </div>
        </div>
        
        {/* Overall Status Summary */}
        <div className="mt-3 pt-3 border-t border-gray-200">
          <div className="flex items-center gap-2 text-xs text-gray-600">
            {canApprove ? (
              <>
                <CheckCircle className="h-4 w-4 text-green-600" />
                <span className="font-medium">Overall Status: </span>
                <span className="text-green-600 font-medium">Ready for Approval</span>
              </>
            ) : (
              <>
                <AlertCircle className="h-4 w-4 text-yellow-600" />
                <span className="font-medium">Overall Status: </span>
                <span className="text-yellow-600 font-medium">Pending Requirements</span>
              </>
            )}
          </div>
          {!canApprove && (
            <div className="text-xs text-gray-500 mt-1 flex items-start gap-1">
              <Clock className="h-3 w-3 mt-0.5 flex-shrink-0" />
              <span>{getApprovalBlockReason()}</span>
            </div>
          )}
        </div>
      </div>

      {/* Rejection Reason Modal */}
      <Dialog open={showRejectModal} onOpenChange={setShowRejectModal}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Reject Teacher</DialogTitle>
            <DialogDescription>
              Please provide a reason for rejecting this teacher. This will be recorded in the system and the teacher will be notified.
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
                maxLength={500}
              />
              <div className="text-sm text-gray-500 text-right">
                {rejectionReason.length}/500 characters
              </div>
            </div>
          </div>
          <DialogFooter>
            <Button 
              type="button" 
              variant="outline" 
              onClick={() => setShowRejectModal(false)}
              disabled={isLoading}
            >
              Cancel
            </Button>
            <Button
              type="button"
              onClick={handleReject}
              disabled={isLoading || !rejectionReason.trim()}
              className="bg-red-600 hover:bg-red-700"
            >
              {isLoading ? 'Processing...' : 'Reject Teacher'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
