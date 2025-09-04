import React, { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface Props {
  isOpen: boolean;
  onOpenChange: (open: boolean) => void;
  verificationRequestId: number | string;
  onScheduled?: () => void;
}

export default function ScheduleVerificationModal({ isOpen, onOpenChange, verificationRequestId, onScheduled }: Props) {
  const [date, setDate] = useState<string>('');
  const [time, setTime] = useState<string>('');
  const [platform, setPlatform] = useState<'zoom' | 'google_meet' | 'other'>('google_meet');
  const [meetingLink, setMeetingLink] = useState<string>('');
  const [notes, setNotes] = useState<string>('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isGenerating, setIsGenerating] = useState(false);

  const scheduledAt = useMemo(() => {
    if (!date || !time) return '';
    // Combine into ISO-like string in local timezone; backend will parse
    return `${date} ${time}`;
  }, [date, time]);

  const canSubmit = Boolean(date && time && platform && !isSubmitting);

  const generateMeetLink = () => {
    if (platform === 'google_meet') {
      const random = Math.random().toString(36).slice(2, 8);
      setMeetingLink(`https://meet.google.com/${random}`);
    }
  };

  const generateProviderLink = async () => {
    if (!scheduledAt) {
      toast.error('Please select date and time first.');
      return;
    }
    
    // For platforms that support automatic generation (Zoom and Google Meet)
    if (platform === 'zoom' || platform === 'google_meet') {
      try {
        setIsGenerating(true);
        const res = await fetch(route('admin.verification.generate-meeting', verificationRequestId), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
          },
          body: JSON.stringify({ 
            scheduled_call_at: scheduledAt, 
            video_platform: platform,
            duration_minutes: 30 // Default duration for verification calls
          })
        });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.success && data.meeting_link) {
          setMeetingLink(data.meeting_link);
          toast.success(`${platform === 'google_meet' ? 'Google Meet' : 'Zoom'} meeting created successfully!`);
        } else if (res.status === 422) {
          if (data.message && data.message.includes('not configured')) {
            toast.error('Google Meet integration is not configured. Please use Zoom or enter a link manually.');
          } else {
            toast.error('Select a future date/time to generate a link.');
          }
        } else {
          toast.error(data.message || 'Failed to generate meeting link. Enter manually.');
        }
      } catch (e) {
        console.error(e);
        toast.error('Failed to generate meeting link');
      } finally {
        setIsGenerating(false);
      }
    } else {
      // For 'other' platform, generate a simple placeholder
      generateMeetLink();
    }
  };

  const handleSubmit = async () => {
    if (!canSubmit) return;
    setIsSubmitting(true);
    router.post(route('admin.verification.request-video', verificationRequestId), {
      scheduled_call_at: scheduledAt,
      video_platform: platform,
      meeting_link: meetingLink || null,
      notes: notes || null,
    }, {
      onSuccess: () => {
        setIsSubmitting(false);
        onOpenChange(false);
        onScheduled?.();
        toast.success('Invite sent');
      },
      onError: () => {
        setIsSubmitting(false);
        toast.error('Failed to schedule verification call');
      }
    });
  };

  return (
    <Dialog open={isOpen} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-[560px]">
        <DialogHeader>
          <DialogTitle>Schedule Verification Call</DialogTitle>
          <DialogDescription>
            Pick a date/time, choose a platform, and optionally include a meeting link and notes.
          </DialogDescription>
        </DialogHeader>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div className="space-y-2">
            <Label>Select Date</Label>
            <Input type="date" value={date} onChange={(e) => setDate(e.target.value)} />
          </div>
          <div className="space-y-2">
            <Label>Select Time</Label>
            <Input type="time" value={time} onChange={(e) => setTime(e.target.value)} />
          </div>
        </div>

        <div className="space-y-2 mt-2">
          <Label>Choose Video Platform</Label>
          <Select value={platform} onValueChange={(v) => {
            setPlatform(v as any);
            setMeetingLink(''); // Clear meeting link when platform changes
          }}>
            <SelectTrigger>
              <SelectValue placeholder="Select platform" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="google_meet">
                <div className="flex items-center gap-2">
                  <span>Google Meet</span>
                  <span className="text-xs text-green-600">(Recommended)</span>
                </div>
              </SelectItem>
              <SelectItem value="zoom">
                <div className="flex items-center gap-2">
                  <span>Zoom</span>
                  <span className="text-xs text-blue-600">(Alternative)</span>
                </div>
              </SelectItem>
              <SelectItem value="other">Other Platform</SelectItem>
            </SelectContent>
          </Select>
          {platform === 'google_meet' && (
            <div className="text-xs text-green-600 bg-green-50 p-2 rounded">
              ✓ Google Meet integration creates calendar events automatically
              <br />
              <span className="text-gray-500">Note: Requires Google Cloud Console setup</span>
            </div>
          )}
          {platform === 'zoom' && (
            <div className="text-xs text-blue-600 bg-blue-50 p-2 rounded">
              ✓ Zoom integration creates meetings with join links automatically
            </div>
          )}
          {platform === 'other' && (
            <div className="text-xs text-gray-600 bg-gray-50 p-2 rounded">
              ℹ You'll need to provide the meeting link manually
            </div>
          )}
        </div>

        <div className="space-y-2">
          <Label>Meeting Link</Label>
          <Input 
            placeholder={
              platform === 'google_meet' 
                ? 'Google Meet link will be generated automatically' 
                : platform === 'zoom' 
                ? 'Zoom meeting link will be generated automatically'
                : 'Enter meeting link manually'
            } 
            value={meetingLink} 
            onChange={(e) => setMeetingLink(e.target.value)} 
          />
          <div className="text-xs text-gray-600">
            {platform === 'google_meet' || platform === 'zoom' ? (
              <>
                <span className="mr-1">Auto-generate {platform === 'google_meet' ? 'Google Meet' : 'Zoom'} meeting:</span>
                <button 
                  type="button" 
                  className="text-green-600 hover:underline disabled:text-gray-400" 
                  onClick={generateProviderLink} 
                  disabled={!scheduledAt || isGenerating}
                >
                  {isGenerating ? 'Generating...' : 'Generate Meeting Link'}
                </button>
                <div className="mt-1 text-gray-500">
                  {platform === 'google_meet' 
                    ? 'Creates a Google Calendar event with Google Meet link'
                    : 'Creates a Zoom meeting with join link'
                  }
                </div>
              </>
            ) : (
              <>
                <span className="mr-1">Manual link:</span>
                <button 
                  type="button" 
                  className="text-blue-600 hover:underline" 
                  onClick={generateMeetLink}
                >
                  Generate placeholder link
                </button>
              </>
            )}
          </div>
        </div>

        <div className="space-y-2">
          <Label>Notes/Instructions (optional)</Label>
          <Textarea placeholder="Write any note to the teacher here..." value={notes} onChange={(e) => setNotes(e.target.value)} />
        </div>

        <DialogFooter>
          <Button variant="ghost" onClick={() => onOpenChange(false)} className="text-red-600 hover:text-red-700">
            Cancel
          </Button>
          <Button onClick={handleSubmit} disabled={!canSubmit} className="bg-teal-600 hover:bg-teal-700">
            {isSubmitting ? 'Scheduling...' : 'Send Invite & Schedule Call'}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
