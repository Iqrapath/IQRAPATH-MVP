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
      alert('Please select date and time first.');
      return;
    }
    if (platform !== 'zoom') {
      generateMeetLink();
      return;
    }
    try {
      setIsGenerating(true);
      const res = await fetch(route('admin.verification.generate-meeting', verificationRequestId), {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content || ''
        },
        body: JSON.stringify({ scheduled_call_at: scheduledAt, video_platform: platform })
      });
      const data = await res.json().catch(() => ({}));
      if (res.ok && data.success && data.meeting_link) {
        setMeetingLink(data.meeting_link);
      } else if (res.status === 422) {
        alert('Select a future date/time to generate a link.');
      } else {
        alert(data.message || 'Failed to generate meeting link. Enter manually.');
      }
    } catch (e) {
      console.error(e);
      alert('Failed to generate meeting link');
    } finally {
      setIsGenerating(false);
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
          <Select value={platform} onValueChange={(v) => setPlatform(v as any)}>
            <SelectTrigger>
              <SelectValue placeholder="Select platform" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="google_meet">Google Meet</SelectItem>
              <SelectItem value="zoom">Zoom</SelectItem>
              <SelectItem value="other">Other</SelectItem>
            </SelectContent>
          </Select>
        </div>

        <div className="space-y-2">
          <Label>Meeting Link</Label>
          <Input placeholder="Meeting Link" value={meetingLink} onChange={(e) => setMeetingLink(e.target.value)} />
          <div className="text-xs text-gray-600">
            <span className="mr-1">Meeting Link:</span>
            <button type="button" className="text-green-600 hover:underline disabled:text-gray-400" onClick={generateProviderLink} disabled={!scheduledAt || isGenerating}>
              {isGenerating ? 'Generating...' : 'Click Generate Meeting Link'}
            </button>
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
