import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
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

interface Props {
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

export default function VerificationCallDetailsCard({ call, verificationRequestId, videoStatus, requestStatus }: Props) {
  const [isModalOpen, setIsModalOpen] = useState(false);
  const [verificationNotes, setVerificationNotes] = useState('');
  const [pendingOutcome, setPendingOutcome] = useState<'passed' | 'failed' | null>(null);
  const [localVideoStatus, setLocalVideoStatus] = useState(videoStatus);
  const [localRequestStatus, setLocalRequestStatus] = useState(requestStatus);
  const [isProcessing, setIsProcessing] = useState(false);

  // Sync local state with props when they change
  useEffect(() => {
    setLocalVideoStatus(videoStatus);
  }, [videoStatus]);

  useEffect(() => {
    setLocalRequestStatus(requestStatus);
  }, [requestStatus]);



  // Show component if there's a call OR if video status indicates there should be a scheduled call
  if (!call && localVideoStatus === 'not_scheduled') return null;

  const formatDateTime = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleString();
  };

  const copyLink = async () => {
    if (call?.meeting_link) {
      await navigator.clipboard.writeText(call.meeting_link);
      // Simple toast substitute
      toast.success('Meeting link copied');
    }
  };

  const startLive = async () => {
    if (!verificationRequestId) return;
    
    setIsProcessing(true);
    
    try {
      await router.post(`/admin/verification/${verificationRequestId}/start-video`);
      
      // Update local state immediately
      setLocalRequestStatus('live_video');
      
      toast.success('Verification call marked as live');
      
      // Background sync with server
      router.reload({ only: ['verificationRequest', 'verification_status', 'latest_call', 'verification_status'] });
      
    } catch (e) {
      toast.error('Failed to start live verification');
    } finally {
      setIsProcessing(false);
    }
  };

  const openCompletionModal = (outcome: 'passed' | 'failed') => {
    setPendingOutcome(outcome);
    setIsModalOpen(true);
  };

  const completeWith = async () => {
    if (!verificationRequestId || !pendingOutcome) return;
    
    setIsProcessing(true);
    
    try {
      await router.patch(`/admin/verification/${verificationRequestId}/complete-video`, {
        verification_result: pendingOutcome,
        verification_notes: verificationNotes.trim() || null,
      });
      
      // Update local state immediately
      setLocalVideoStatus(pendingOutcome);
      
      toast.success(`Verification ${pendingOutcome}!`);
      setIsModalOpen(false);
      setVerificationNotes('');
      setPendingOutcome(null);
      
      // Background sync with server
      router.reload({ only: ['verificationRequest', 'verification_status', 'latest_call'] });
      
    } catch (e) {
      toast.error('Failed to complete verification');
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <>
      <Card className="mb-8 shadow-sm">
        <CardContent className="p-6">
          <h3 className="text-lg font-semibold text-gray-900 mb-4">Verification Call</h3>
          
          {call ? (
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
              <div>
                <div className="text-sm text-gray-600">Scheduled At</div>
                <div className="text-gray-900">{formatDateTime(call.scheduled_at)}</div>
              </div>
              <div>
                <div className="text-sm text-gray-600">Platform</div>
                <div className="text-gray-900 capitalize">{call.platform.replace('_', ' ')}</div>
              </div>
              <div className="md:col-span-2">
                <div className="text-sm text-gray-600">Meeting Link</div>
                <div className="flex items-center gap-3">
                  <a href={call.meeting_link} target="_blank" rel="noreferrer" className="text-teal-700 underline break-all">
                    {call.meeting_link || '—'}
                  </a>
                  {call.meeting_link && (
                    <Button variant="outline" size="sm" onClick={copyLink}>Copy</Button>
                  )}
                </div>
              </div>
              {call.notes && (
                <div className="md:col-span-2">
                  <div className="text-sm text-gray-600">Notes</div>
                  <div className="text-gray-900 whitespace-pre-wrap">{call.notes}</div>
                </div>
              )}
            </div>
          ) : (
            <div className="mb-4 p-4 bg-blue-50 rounded-lg">
              <div className="text-blue-800 text-sm">
                {localVideoStatus === 'scheduled' 
                  ? '🔄 Loading call details...' 
                  : 'No verification call scheduled yet.'}
              </div>
            </div>
          )}
          <div className="flex gap-3 items-center">
            {call?.meeting_link && (
              <a href={call.meeting_link} target="_blank" rel="noreferrer">
                <Button className="bg-teal-600 hover:bg-teal-700">Join/Start</Button>
              </a>
            )}
            {localRequestStatus !== 'live_video' && localVideoStatus === 'scheduled' && (
              <Button variant="outline" onClick={startLive} disabled={isProcessing}>
                {isProcessing ? 'Starting...' : 'Start Live Verification'}
              </Button>
            )}
            {localRequestStatus === 'live_video' && localVideoStatus === 'scheduled' && (
              <>
                <div className="text-blue-600 font-medium mb-2">🔄 Live verification in progress...</div>
                <Button 
                  className="bg-green-600 hover:bg-green-700" 
                  onClick={() => openCompletionModal('passed')}
                  disabled={isProcessing}
                >
                  {isProcessing ? 'Processing...' : 'Mark as Passed'}
                </Button>
                <Button 
                  variant="destructive" 
                  onClick={() => openCompletionModal('failed')}
                  disabled={isProcessing}
                >
                  {isProcessing ? 'Processing...' : 'Mark as Failed'}
                </Button>
              </>
            )}
            {localVideoStatus === 'passed' && (
              <div className="text-green-600 font-medium">✅ Video verification passed</div>
            )}
            {localVideoStatus === 'failed' && (
              <div className="text-red-600 font-medium">❌ Video verification failed</div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Verification Completion Modal */}
      <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Complete Video Verification</DialogTitle>
            <DialogDescription>
              Mark this verification as {pendingOutcome} and add any notes about the session.
            </DialogDescription>
          </DialogHeader>
          
          <div className="space-y-4">
            <div>
              <Label htmlFor="verification-notes">Verification Notes (Optional)</Label>
              <Textarea
                id="verification-notes"
                placeholder="Enter any notes about the verification session..."
                value={verificationNotes}
                onChange={(e) => setVerificationNotes(e.target.value)}
                rows={4}
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={() => setIsModalOpen(false)} disabled={isProcessing}>
              Cancel
            </Button>
            <Button 
              onClick={completeWith}
              disabled={isProcessing}
              className={pendingOutcome === 'passed' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}
            >
              {isProcessing ? 'Processing...' : `Mark as ${pendingOutcome === 'passed' ? 'Passed' : 'Failed'}`}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
