import React, { useState } from 'react';
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

export default function VerificationCallDetailsCard({ 
  call, 
  verificationRequestId, 
  videoStatus, 
  requestStatus
}: Props) {
  const [completionModalOpen, setCompletionModalOpen] = useState(false);
  const [verificationNotes, setVerificationNotes] = useState('');
  const [pendingOutcome, setPendingOutcome] = useState<'passed' | 'failed' | null>(null);
  const [isProcessing, setIsProcessing] = useState(false);

  const formatDateTime = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleString();
  };

  const copyLink = async () => {
    if (call?.meeting_link) {
      await navigator.clipboard.writeText(call.meeting_link);
      toast.success('Meeting link copied');
    }
  };

  const startLive = async () => {
    if (!verificationRequestId) return;
    
    setIsProcessing(true);
    
    try {
      await router.post(`/admin/verification/${verificationRequestId}/start-video`);
      
      toast.success('Verification call marked as live');
      
      router.reload({ only: ['verificationRequest', 'verification_status', 'latest_call'] });
      
    } catch (e) {
      toast.error('Failed to start live verification');
    } finally {
      setIsProcessing(false);
    }
  };

  const openCompletionModal = (outcome: 'passed' | 'failed') => {
    setPendingOutcome(outcome);
    setCompletionModalOpen(true);
  };

  const completeWith = async () => {
    if (!verificationRequestId || !pendingOutcome) return;
    
    setIsProcessing(true);
    
    try {
      await router.patch(`/admin/verification/${verificationRequestId}/complete-video`, {
        verification_result: pendingOutcome,
        verification_notes: verificationNotes,
      });
      
      toast.success(`Verification ${pendingOutcome}!`);
      setCompletionModalOpen(false);
      setVerificationNotes('');
      setPendingOutcome(null);
      
      router.reload({ only: ['verificationRequest', 'verification_status', 'latest_call'] });
      
    } catch (e) {
      toast.error('Failed to complete verification');
    } finally {
      setIsProcessing(false);
    }
  };

  return (
    <>
      <Card className="shadow-sm">
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
                <div className="flex items-center gap-2">
                  <a 
                    href={call.meeting_link} 
                    target="_blank" 
                    rel="noopener noreferrer"
                    className="text-blue-600 hover:text-blue-800 underline truncate"
                  >
                    {call.meeting_link}
                  </a>
                  <Button 
                    size="sm" 
                    variant="outline" 
                    onClick={copyLink}
                    className="shrink-0"
                  >
                    Copy
                  </Button>
                </div>
              </div>
              {call.notes && (
                <div className="md:col-span-2">
                  <div className="text-sm text-gray-600">Notes</div>
                  <div className="text-gray-900">{call.notes}</div>
                </div>
              )}
            </div>
          ) : (
            <div className="text-center py-8 text-gray-500">
              <div className="text-lg mb-2">📞</div>
              <div>No verification call scheduled yet</div>
            </div>
          )}

          <div className="flex flex-wrap gap-2 mt-4">
            {call && videoStatus === 'scheduled' && requestStatus !== 'live_video' && (
              <Button 
                onClick={startLive}
                disabled={isProcessing}
                className="rounded-full bg-teal-600 hover:bg-teal-700 text-white hover:text-white cursor-pointer"
              >
                {isProcessing ? 'Starting...' : 'Start Live Verification'}
              </Button>
            )}
            
            {requestStatus === 'live_video' && videoStatus !== 'passed' && videoStatus !== 'failed' && (
              <>
                <Button 
                  onClick={() => openCompletionModal('passed')}
                  disabled={isProcessing}
                  className="rounded-full bg-green-600 hover:bg-green-700 text-white hover:text-white cursor-pointer"
                >
                  Mark as Passed
                </Button>
                <Button 
                  onClick={() => openCompletionModal('failed')}
                  disabled={isProcessing}
                  className="rounded-full bg-red-600 hover:bg-red-700 text-white hover:text-white cursor-pointer"
                >
                  Mark as Failed
                </Button>
              </>
            )}
          </div>

          <div className="mt-4 pt-4 border-t">
            {videoStatus === 'scheduled' && requestStatus !== 'live_video' && (
              <div className="text-teal-600 font-medium">📅 Verification call scheduled</div>
            )}
            {requestStatus === 'live_video' && (
              <div className="text-orange-600 font-medium">🔴 Live verification in progress</div>
            )}
            {videoStatus === 'passed' && (
              <div className="text-teal-600 font-medium">✅ Video verification passed</div>
            )}
            {videoStatus === 'failed' && (
              <div className="text-red-600 font-medium">❌ Video verification failed</div>
            )}
          </div>
        </CardContent>
      </Card>

      <Dialog open={completionModalOpen} onOpenChange={setCompletionModalOpen}>
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
            <Button variant="outline" onClick={() => setCompletionModalOpen(false)} disabled={isProcessing}>
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