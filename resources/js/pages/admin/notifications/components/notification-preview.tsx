import React, { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Checkbox } from '@/components/ui/checkbox';
import { Bell, Mail, MessageSquare, CheckCircle, XCircle, Eye, Edit, Trash2, RefreshCw } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

interface NotificationPreviewProps {
  title: string;
  body: string;
  level: 'info' | 'success' | 'warning' | 'error';
  actionText?: string;
  actionUrl?: string;
  channels: string[];
  recipientCount?: number;
  deliveryDate?: string;
  deliveryStatus?: 'delivered' | 'pending' | 'failed';
  openRate?: number;
  clickThroughRate?: number;
  recipients?: Array<{
    id: string;
    name: string;
    email: string;
    status: 'delivered' | 'read' | 'failed';
  }>;
  notificationId?: string;
}

export default function NotificationPreview({ 
  title, 
  body, 
  level, 
  actionText, 
  actionUrl, 
  channels,
  recipientCount = 0,
  deliveryDate,
  deliveryStatus = 'delivered',
  openRate = 0,
  clickThroughRate = 0,
  recipients = [],
  notificationId
}: NotificationPreviewProps) {
  const [isResending, setIsResending] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);

  const getDeliveryStatusColor = (status: string) => {
    switch (status) {
      case 'delivered':
      case 'read':
        return 'bg-green-100 text-green-800 border-green-200';
      case 'failed':
        return 'bg-red-100 text-red-800 border-red-200';
      default:
        return 'bg-yellow-100 text-yellow-800 border-yellow-200';
    }
  };

  const getDeliveryStatusIcon = (status: string) => {
    switch (status) {
      case 'delivered':
      case 'read':
        return <CheckCircle className="w-4 h-4 text-green-600" />;
      case 'failed':
        return <XCircle className="w-4 h-4 text-red-600" />;
      default:
        return <Eye className="w-4 h-4 text-yellow-600" />;
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

  const handleResendMessage = async () => {
    if (!notificationId) {
      toast.error('Notification ID not found');
      return;
    }

    setIsResending(true);
    try {
      const getCookie = (name: string) => {
        const match = document.cookie.match(new RegExp('(^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[2]) : undefined;
      };

      // Try meta first
      let csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      // Fallback to cookie
      if (!csrf) csrf = getCookie('XSRF-TOKEN') || '';

      // If still missing, request Sanctum CSRF cookie then read again
      if (!csrf) {
        await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' });
        csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || getCookie('XSRF-TOKEN') || '';
      }

      const response = await fetch(`/admin/notifications/${notificationId}/resend`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          ...(csrf ? { 'X-XSRF-TOKEN': csrf } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify({}),
      });

      if (!response.ok) {
        const errText = await response.text();
        throw new Error(errText || 'Failed to resend');
      }
      const data = await response.json();
      toast.success(data.message || 'Message resent successfully');
      router.reload({ only: ['notification'] });
    } catch (error: any) {
      toast.error(error?.message || 'Failed to resend message');
    } finally {
      setIsResending(false);
    }
  };

  const handleEditReschedule = () => {
    if (!notificationId) {
      toast.error('Notification ID not found');
      return;
    }

    // Navigate to edit page with notification data
    router.visit(`/admin/notifications/${notificationId}/edit`, {
      data: {
        title,
        body,
        level,
        actionText,
        actionUrl,
        channels
      }
    });
  };

  const handleDeleteNotification = async () => {
    if (!notificationId) {
      toast.error('Notification ID not found');
      return;
    }

    if (!confirm('Are you sure you want to delete this notification record? This action cannot be undone.')) {
      return;
    }

    setIsDeleting(true);
    try {
      const getCookie = (name: string) => {
        const match = document.cookie.match(new RegExp('(^|; )' + name.replace(/([.$?*|{}()\[\]\\\/\+^])/g, '\\$1') + '=([^;]*)'));
        return match ? decodeURIComponent(match[2]) : undefined;
      };

      let csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      if (!csrf) csrf = getCookie('XSRF-TOKEN') || '';
      if (!csrf) {
        await fetch('/sanctum/csrf-cookie', { method: 'GET', credentials: 'same-origin' });
        csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || getCookie('XSRF-TOKEN') || '';
      }

      const resp = await fetch(`/admin/notifications/${notificationId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          ...(csrf ? { 'X-CSRF-TOKEN': csrf } : {}),
          ...(csrf ? { 'X-XSRF-TOKEN': csrf } : {}),
        },
        credentials: 'same-origin',
      });
      if (!resp.ok) {
        const errText = await resp.text();
        throw new Error(errText || 'Failed to delete');
      }
      const data = await resp.json().catch(() => ({}));
      toast.success((data as any).message || 'Notification deleted successfully');
      router.visit('/admin/notifications');
    } catch (error: any) {
      toast.error(error?.message || 'Failed to delete notification');
    } finally {
      setIsDeleting(false);
    }
  };

  return (
    <div className="space-y-6">
      {/* Page Header */}
      <div className="border-b border-gray-200 pb-4">
        <div className="text-sm text-gray-500 mb-2">Dashboard • Notifications System</div>
        <h1 className="text-2xl font-bold text-gray-900">Notification details</h1>
      </div>

      {/* System Notification Section */}
      <Card className="shadow-sm">
        <CardHeader className="pb-4">
          <div className="flex items-center justify-between">
            <CardTitle className="text-xl font-semibold text-gray-900">{title}</CardTitle>
            <Badge className={getDeliveryStatusColor(deliveryStatus)}>
              {deliveryStatus.charAt(0).toUpperCase() + deliveryStatus.slice(1)}
            </Badge>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          <p className="text-gray-700 leading-relaxed">{body}</p>
          
          <div className="flex items-center justify-between text-sm text-gray-600">
            <span>
              Sent To: {recipientCount} users 
              <button 
                className="text-teal-600 hover:underline ml-1"
                onClick={() => {
                  const analyticsSection = document.getElementById('delivery-analytics');
                  if (analyticsSection) {
                    analyticsSection.scrollIntoView({ behavior: 'smooth' });
                  }
                }}
              >
                (view recipients)
              </button>
            </span>
            <span>
              Delivery Date: {deliveryDate || 'April 13, 2025 - 10:15 AM'}
            </span>
          </div>

          {actionText && actionUrl && (
            <div className="pt-2">
              <Button variant="outline" size="sm" className="text-teal-600 border-teal-600 hover:bg-teal-50">
                {actionText}
              </Button>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Quick Action Section */}
      <div>
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Quick Action</h3>
        <div className="flex gap-3">
          <Button 
            className="bg-teal-600 hover:bg-teal-700 text-white"
            onClick={handleResendMessage}
            disabled={isResending}
          >
            <RefreshCw className={`w-4 h-4 mr-2 ${isResending ? 'animate-spin' : ''}`} />
            {isResending ? 'Resending...' : 'Resend Message'}
          </Button>
          <Button 
            variant="outline" 
            className="border-teal-600 text-teal-600 hover:bg-teal-50"
            onClick={handleEditReschedule}
          >
            <Edit className="w-4 h-4 mr-2" />
            Edit & Re-Schedule
          </Button>
          <Button 
            variant="ghost" 
            className="text-red-600 hover:bg-red-50"
            onClick={handleDeleteNotification}
            disabled={isDeleting}
          >
            <Trash2 className="w-4 h-4 mr-2" />
            {isDeleting ? 'Deleting...' : 'Delete Notification Record'}
          </Button>
        </div>
      </div>

      {/* Delivery Analytics Section */}
      <div id="delivery-analytics">
        <h3 className="text-lg font-semibold text-gray-900 mb-4">Delivery Analytics:</h3>
        
        {/* Recipient Delivery Status Table */}
        <Card className="mb-6">
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="bg-gray-50">
                  <TableHead className="w-12 px-4 py-3">
                    <Checkbox />
                  </TableHead>
                  <TableHead className="px-4 py-3 text-left font-medium text-gray-900">Name</TableHead>
                  <TableHead className="px-4 py-3 text-left font-medium text-gray-900">Email</TableHead>
                  <TableHead className="px-4 py-3 text-left font-medium text-gray-900">Delivery Status</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {recipients.length > 0 ? (
                  recipients.map((recipient) => (
                    <TableRow key={recipient.id} className="border-b border-gray-100">
                      <TableCell className="px-4 py-3">
                        <Checkbox />
                      </TableCell>
                      <TableCell className="px-4 py-3 font-medium text-gray-900">
                        {recipient.name}
                      </TableCell>
                      <TableCell className="px-4 py-3 text-gray-600">
                        {recipient.email}
                      </TableCell>
                      <TableCell className="px-4 py-3">
                        <div className="flex items-center gap-2">
                          {getDeliveryStatusIcon(recipient.status)}
                          <span className={getDeliveryStatusColor(recipient.status).replace('bg-', 'text-').split(' ')[0]}>
                            {recipient.status.charAt(0).toUpperCase() + recipient.status.slice(1)}
                          </span>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={4} className="px-4 py-8 text-center text-gray-500">
                      No recipients found for this notification
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>

        {/* Summary Statistics Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Card className="bg-green-50 border-green-200">
            <CardContent className="p-6 text-center">
              <h4 className="text-sm font-medium text-green-800 mb-2">Open Rate</h4>
              <div className="text-3xl font-bold text-green-900">{openRate}%</div>
            </CardContent>
          </Card>
          
          <Card className="bg-purple-50 border-purple-200">
            <CardContent className="p-6 text-center">
              <h4 className="text-sm font-medium text-purple-800 mb-2">Click-Through Rate</h4>
              <div className="text-3xl font-bold text-purple-900">{clickThroughRate}%</div>
            </CardContent>
          </Card>
        </div>
      </div>
    </div>
  );
}
