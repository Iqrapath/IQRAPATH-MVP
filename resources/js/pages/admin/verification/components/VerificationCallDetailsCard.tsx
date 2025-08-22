import React from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

interface Props {
  call: {
    id: number | string;
    scheduled_at: string;
    platform: string;
    meeting_link?: string;
    notes?: string;
    status: string;
  } | null;
}

export default function VerificationCallDetailsCard({ call }: Props) {
  if (!call) return null;

  const formatDateTime = (iso: string) => {
    const d = new Date(iso);
    return d.toLocaleString();
  };

  const copyLink = async () => {
    if (call?.meeting_link) {
      await navigator.clipboard.writeText(call.meeting_link);
      // Simple toast substitute
      alert('Meeting link copied');
    }
  };

  return (
    <Card className="mb-8 shadow-sm">
      <CardContent className="p-6">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Verification Call</h3>
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
        <div className="flex gap-3">
          {call.meeting_link && (
            <a href={call.meeting_link} target="_blank" rel="noreferrer">
              <Button className="bg-teal-600 hover:bg-teal-700">Join/Start</Button>
            </a>
          )}
        </div>
      </CardContent>
    </Card>
  );
}
