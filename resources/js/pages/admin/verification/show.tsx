import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, Download, Eye, CheckCircle, XCircle, Video, Calendar, Clock, User, Mail, Phone, MapPin } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Label } from '@/components/ui/label';
import { format } from 'date-fns';
import AdminLayout from "@/layouts/admin/admin-layout";
import { Breadcrumbs } from "@/components/breadcrumbs";

interface Document {
  id: number;
  type: string;
  name: string;
  status: string;
  url: string;
  verified_at?: string;
  verified_by?: number;
}

interface VerificationRequest {
  id: string;
  status: 'pending' | 'verified' | 'rejected' | 'live_video';
  docs_status: 'pending' | 'verified' | 'rejected';
  video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
  scheduled_call_at?: string;
  video_platform?: string;
  meeting_link?: string;
  notes?: string;
  submitted_at: string;
  reviewed_by?: number;
  reviewed_at?: string;
  rejection_reason?: string;
  teacher_profile: {
    user: {
      id: number;
      name: string;
      email: string;
      phone?: string;
      avatar?: string;
    };
    documents: Document[];
    subjects?: string[];
    bio?: string;
    experience_years?: string;
    hourly_rate?: number;
  };
}

interface Props {
  verificationRequest: VerificationRequest;
  teacher: any;
  documents: Document[];
}

export default function VerificationShow({ verificationRequest, teacher, documents }: Props) {
  const [rejectionReason, setRejectionReason] = useState('');
  const [videoDate, setVideoDate] = useState('');
  const [videoTime, setVideoTime] = useState('');
  const [videoPlatform, setVideoPlatform] = useState('');
  const [meetingLink, setMeetingLink] = useState('');
  const [videoNotes, setVideoNotes] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const handleApprove = () => {
    if (confirm('Are you sure you want to approve this teacher verification?')) {
      router.patch(route('admin.verification.approve', verificationRequest.id));
    }
  };

  const handleReject = () => {
    if (!rejectionReason.trim()) {
      alert('Please provide a rejection reason');
      return;
    }
    
    if (confirm('Are you sure you want to reject this teacher verification?')) {
      router.patch(route('admin.verification.reject', verificationRequest.id), {
        rejection_reason: rejectionReason
      });
    }
  };

  const handleVideoVerification = () => {
    if (!videoDate || !videoTime || !videoPlatform) {
      alert('Please fill in all required fields');
      return;
    }

    const scheduledCallAt = new Date(`${videoDate}T${videoTime}`);
    
    setIsSubmitting(true);
    router.post(route('admin.verification.request-video', verificationRequest.id), {
      scheduled_call_at: scheduledCallAt.toISOString(),
      video_platform: videoPlatform,
      meeting_link: meetingLink,
      notes: videoNotes
    }, {
      onFinish: () => setIsSubmitting(false)
    });
  };

  const getStatusBadge = (status: string) => {
    switch (status) {
      case "pending":
        return (
          <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200">
            Pending
          </Badge>
        );
      case "verified":
        return (
          <Badge className="bg-green-100 text-green-800 border-green-200">
            Verified
          </Badge>
        );
      case "rejected":
        return (
          <Badge className="bg-red-100 text-red-800 border-red-200">
            Rejected
          </Badge>
        );
      case "live_video":
        return (
          <Badge className="bg-blue-100 text-blue-800 border-blue-200">
            Live
          </Badge>
        );
      default:
        return (
          <Badge variant="outline">
            {status}
          </Badge>
        );
    }
  };

  const getDocumentStatusBadge = (status: string) => {
    switch (status) {
      case "pending":
        return (
          <Badge className="bg-yellow-100 text-yellow-800 border-yellow-200">
            Pending
          </Badge>
        );
      case "verified":
        return (
          <Badge className="bg-green-100 text-green-800 border-green-200">
            Verified
          </Badge>
        );
      case "rejected":
        return (
          <Badge className="bg-red-100 text-red-800 border-red-200">
            Rejected
          </Badge>
        );
      default:
        return (
          <Badge variant="outline">
            {status.charAt(0).toUpperCase() + status.slice(1)}
          </Badge>
        );
    }
  };

  const canApprove = verificationRequest.status === 'pending' && 
                    verificationRequest.docs_status === 'verified' &&
                    documents.every(doc => doc.status === 'verified');

  const canReject = verificationRequest.status === 'pending';
  const canRequestVideo = verificationRequest.status === 'pending';

  const breadcrumbs = [
    { title: "Dashboard", href: route("admin.dashboard") },
    { title: "Verification Requests", href: route("admin.verification.index") },
    { title: teacher.name, href: "#" }
  ];

  return (
    <AdminLayout pageTitle="Verification Request Details" showRightSidebar={false}>
      <Head title={`Verification Request - ${teacher.name}`} />
      
      <div className="py-6">
        <div className="mb-6">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>

        {/* Header */}
        <div className="flex items-center gap-4 mb-6">
          <Link
            href={route('admin.verification.index')}
            className="flex items-center gap-2 text-muted-foreground hover:text-foreground"
          >
            <ArrowLeft className="h-4 w-4" />
            Back to Verification Requests
          </Link>
        </div>

        <div className="flex items-center justify-between mb-6">
          <div>
            <h1 className="text-2xl font-semibold text-gray-900 mb-2">Verification Request</h1>
            <p className="text-gray-600">
              Review teacher verification details and take action
            </p>
          </div>
          <div className="flex items-center gap-2">
            {getStatusBadge(verificationRequest.status)}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-3">
          {/* Main Content */}
          <div className="lg:col-span-2 space-y-6">
            {/* Teacher Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  <User className="h-5 w-5" />
                  Teacher Information
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center gap-4">
                  <Avatar className="h-16 w-16 border">
                    <AvatarImage src={teacher.avatar} />
                    <AvatarFallback className="text-lg bg-gray-200 text-gray-600">
                      {teacher.name.split(' ').map((n: string) => n[0]).join('')}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <h3 className="text-xl font-semibold">{teacher.name}</h3>
                    <div className="flex items-center gap-4 text-sm text-muted-foreground">
                      <span className="flex items-center gap-1">
                        <Mail className="h-4 w-4" />
                        {teacher.email}
                      </span>
                      {teacher.phone && (
                        <span className="flex items-center gap-1">
                          <Phone className="h-4 w-4" />
                          {teacher.phone}
                        </span>
                      )}
                    </div>
                  </div>
                </div>

                {verificationRequest.teacher_profile.bio && (
                  <div>
                    <h4 className="font-medium mb-2">Bio</h4>
                    <p className="text-sm text-muted-foreground">{verificationRequest.teacher_profile.bio}</p>
                  </div>
                )}

                <div className="grid grid-cols-2 gap-4">
                  <div>
                    <h4 className="font-medium mb-1">Experience</h4>
                    <p className="text-sm text-muted-foreground">
                      {verificationRequest.teacher_profile.experience_years || 'Not specified'}
                    </p>
                  </div>
                  <div>
                    <h4 className="font-medium mb-1">Hourly Rate</h4>
                    <p className="text-sm text-muted-foreground">
                      ${verificationRequest.teacher_profile.hourly_rate || 'Not specified'}
                    </p>
                  </div>
                </div>

                {verificationRequest.teacher_profile.subjects && verificationRequest.teacher_profile.subjects.length > 0 && (
                  <div>
                    <h4 className="font-medium mb-2">Subjects</h4>
                    <div className="flex flex-wrap gap-2">
                      {verificationRequest.teacher_profile.subjects.map((subject: string, index: number) => (
                        <Badge key={index} variant="outline">{subject}</Badge>
                      ))}
                    </div>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Documents */}
            <Card>
              <CardHeader>
                <CardTitle>Documents</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-3">
                  {documents.map((document) => (
                    <div key={document.id} className="flex items-center justify-between p-3 border rounded-lg">
                      <div className="flex items-center gap-3">
                        <div className="p-2 bg-muted rounded">
                          <Download className="h-4 w-4" />
                        </div>
                        <div>
                          <p className="font-medium">{document.name}</p>
                          <p className="text-sm text-muted-foreground">{document.type}</p>
                        </div>
                      </div>
                      <div className="flex items-center gap-2">
                        {getDocumentStatusBadge(document.status)}
                        <Button variant="outline" size="sm" asChild>
                          <a href={document.url} target="_blank" rel="noopener noreferrer">
                            <Eye className="h-4 w-4" />
                          </a>
                        </Button>
                      </div>
                    </div>
                  ))}
                  
                  {documents.length === 0 && (
                    <p className="text-center text-muted-foreground py-8">
                      No documents uploaded yet
                    </p>
                  )}
                </div>
              </CardContent>
            </Card>
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Action Buttons */}
            <Card>
              <CardHeader>
                <CardTitle>Actions</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                {canApprove && (
                  <Button 
                    onClick={handleApprove} 
                    className="w-full bg-green-600 hover:bg-green-700"
                    disabled={isSubmitting}
                  >
                    <CheckCircle className="mr-2 h-4 w-4" />
                    Approve Verification
                  </Button>
                )}

                {canReject && (
                  <Dialog>
                    <DialogTrigger asChild>
                      <Button variant="destructive" className="w-full">
                        <XCircle className="mr-2 h-4 w-4" />
                        Reject Verification
                      </Button>
                    </DialogTrigger>
                    <DialogContent>
                      <DialogHeader>
                        <DialogTitle>Reject Verification</DialogTitle>
                      </DialogHeader>
                      <div className="space-y-4">
                        <div>
                          <Label htmlFor="rejection-reason">Rejection Reason</Label>
                          <Textarea
                            id="rejection-reason"
                            placeholder="Please provide a reason for rejection..."
                            value={rejectionReason}
                            onChange={(e) => setRejectionReason(e.target.value)}
                            rows={4}
                          />
                        </div>
                        <Button onClick={handleReject} variant="destructive" className="w-full">
                          Confirm Rejection
                        </Button>
                      </div>
                    </DialogContent>
                  </Dialog>
                )}

                {canRequestVideo && (
                  <Dialog>
                    <DialogTrigger asChild>
                      <Button variant="outline" className="w-full">
                        <Video className="mr-2 h-4 w-4" />
                        Request Video Verification
                      </Button>
                    </DialogTrigger>
                    <DialogContent className="max-w-md">
                      <DialogHeader>
                        <DialogTitle>Schedule Video Verification</DialogTitle>
                      </DialogHeader>
                      <div className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                          <div>
                            <Label htmlFor="video-date">Date</Label>
                            <Input
                              id="video-date"
                              type="date"
                              value={videoDate}
                              onChange={(e) => setVideoDate(e.target.value)}
                            />
                          </div>
                          <div>
                            <Label htmlFor="video-time">Time</Label>
                            <Input
                              id="video-time"
                              type="time"
                              value={videoTime}
                              onChange={(e) => setVideoTime(e.target.value)}
                            />
                          </div>
                        </div>
                        
                        <div>
                          <Label htmlFor="video-platform">Platform</Label>
                          <Select value={videoPlatform} onValueChange={setVideoPlatform}>
                            <SelectTrigger>
                              <SelectValue placeholder="Select platform" />
                            </SelectTrigger>
                            <SelectContent>
                              <SelectItem value="zoom">Zoom</SelectItem>
                              <SelectItem value="google_meet">Google Meet</SelectItem>
                              <SelectItem value="other">Other</SelectItem>
                            </SelectContent>
                          </Select>
                        </div>

                        <div>
                          <Label htmlFor="meeting-link">Meeting Link (Optional)</Label>
                          <Input
                            id="meeting-link"
                            type="url"
                            placeholder="https://..."
                            value={meetingLink}
                            onChange={(e) => setMeetingLink(e.target.value)}
                          />
                        </div>

                        <div>
                          <Label htmlFor="video-notes">Notes (Optional)</Label>
                          <Textarea
                            id="video-notes"
                            placeholder="Additional notes for the teacher..."
                            value={videoNotes}
                            onChange={(e) => setVideoNotes(e.target.value)}
                            rows={3}
                          />
                        </div>

                        <Button 
                          onClick={handleVideoVerification} 
                          className="w-full"
                          disabled={isSubmitting}
                        >
                          {isSubmitting ? 'Scheduling...' : 'Schedule Video Call'}
                        </Button>
                      </div>
                    </DialogContent>
                  </Dialog>
                )}
              </CardContent>
            </Card>

            {/* Verification Details */}
            <Card>
              <CardHeader>
                <CardTitle>Verification Details</CardTitle>
              </CardHeader>
              <CardContent className="space-y-3">
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Submitted</span>
                  <span className="text-sm font-medium">
                    {verificationRequest.submitted_at ? 
                      format(new Date(verificationRequest.submitted_at), 'MMM dd, yyyy') : 
                      'N/A'
                    }
                  </span>
                </div>
                
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Documents Status</span>
                  <Badge variant="outline">
                    {verificationRequest.docs_status.charAt(0).toUpperCase() + verificationRequest.docs_status.slice(1)}
                  </Badge>
                </div>
                
                <div className="flex justify-between">
                  <span className="text-sm text-muted-foreground">Video Status</span>
                  <Badge variant="outline">
                    {verificationRequest.video_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())}
                  </Badge>
                </div>

                {verificationRequest.scheduled_call_at && (
                  <div className="flex justify-between">
                    <span className="text-sm text-muted-foreground">Scheduled Call</span>
                    <span className="text-sm font-medium">
                      {format(new Date(verificationRequest.scheduled_call_at), 'MMM dd, yyyy HH:mm')}
                    </span>
                  </div>
                )}

                {verificationRequest.rejection_reason && (
                  <div className="pt-3 border-t">
                    <span className="text-sm text-muted-foreground">Rejection Reason</span>
                    <p className="text-sm mt-1 p-2 bg-red-50 rounded border border-red-200 text-red-800">
                      {verificationRequest.rejection_reason}
                    </p>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AdminLayout>
  );
}
