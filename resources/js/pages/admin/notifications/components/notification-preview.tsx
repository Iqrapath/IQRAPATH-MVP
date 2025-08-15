import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Bell, Mail, MessageSquare } from 'lucide-react';

interface NotificationPreviewProps {
  title: string;
  body: string;
  level: 'info' | 'success' | 'warning' | 'error';
  actionText?: string;
  actionUrl?: string;
  channels: string[];
}

export default function NotificationPreview({ 
  title, 
  body, 
  level, 
  actionText, 
  actionUrl, 
  channels 
}: NotificationPreviewProps) {
  const getLevelColor = (level: string) => {
    switch (level) {
      case 'success':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'warning':
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
      case 'error':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-blue-100 text-blue-800 border-blue-200';
    }
  };

  const getLevelIcon = (level: string) => {
    switch (level) {
      case 'success':
        return '✅';
      case 'warning':
        return '⚠️';
      case 'error':
        return '❌';
      default:
        return 'ℹ️';
    }
  };

  const getChannelIcon = (channel: string) => {
    switch (channel) {
      case 'email':
        return <Mail className="w-4 h-4" />;
      case 'sms':
        return <MessageSquare className="w-4 h-4" />;
      default:
        return <Bell className="w-4 h-4" />;
    }
  };

  return (
    <Card className="max-w-md mx-auto">
      <CardHeader>
        <CardTitle className="flex items-center justify-between">
          <span>Notification Preview</span>
          <div className="flex gap-1">
            {channels.map(channel => (
              <div key={channel} className="p-1 bg-gray-100 rounded">
                {getChannelIcon(channel)}
              </div>
            ))}
          </div>
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-4">
        {/* In-App Notification Preview */}
        <div className="border rounded-lg p-4 bg-white shadow-sm">
          <div className="flex items-start gap-3">
            <div className="text-lg">{getLevelIcon(level)}</div>
            <div className="flex-1">
              <div className="flex items-center gap-2 mb-2">
                <h4 className="font-semibold text-gray-900">{title}</h4>
                <Badge className={getLevelColor(level)}>
                  {level.charAt(0).toUpperCase() + level.slice(1)}
                </Badge>
              </div>
              <p className="text-gray-600 text-sm mb-3">{body}</p>
              {actionText && actionUrl && (
                <Button 
                  variant="outline" 
                  size="sm"
                  className="text-teal-600 border-teal-600 hover:bg-teal-50"
                >
                  {actionText}
                </Button>
              )}
            </div>
          </div>
        </div>

        {/* Email Preview */}
        {channels.includes('email') && (
          <div className="border rounded-lg p-4 bg-gray-50">
            <div className="flex items-center gap-2 mb-3">
              <Mail className="w-4 h-4 text-gray-500" />
              <span className="text-sm font-medium text-gray-700">Email Preview</span>
            </div>
            <div className="space-y-2">
              <div className="text-sm">
                <span className="font-medium">Subject:</span> {title}
              </div>
              <div className="text-sm text-gray-600">
                <span className="font-medium">Content:</span> {body}
              </div>
              {actionText && actionUrl && (
                <div className="text-sm">
                  <span className="font-medium">Action:</span> {actionText} → {actionUrl}
                </div>
              )}
            </div>
          </div>
        )}

        {/* SMS Preview */}
        {channels.includes('sms') && (
          <div className="border rounded-lg p-4 bg-gray-50">
            <div className="flex items-center gap-2 mb-3">
              <MessageSquare className="w-4 h-4 text-gray-500" />
              <span className="text-sm font-medium text-gray-700">SMS Preview</span>
            </div>
            <div className="bg-white border rounded p-3 text-sm">
              <div className="text-gray-500 mb-1">IqraPath:</div>
              <div>{title}</div>
              <div className="text-gray-600 mt-1">{body}</div>
              {actionText && actionUrl && (
                <div className="text-teal-600 mt-1">
                  {actionText}: {actionUrl}
                </div>
              )}
            </div>
          </div>
        )}

        {/* Delivery Channels Summary */}
        <div className="text-xs text-gray-500 text-center">
          Will be delivered via: {channels.join(', ')}
        </div>
      </CardContent>
    </Card>
  );
}
