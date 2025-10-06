import React, { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Mail, Phone, FileText, Calendar, Star, Video } from 'lucide-react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import ScheduleVerificationModal from './ScheduleVerificationModal';

interface TeacherContactDetailsProps {
  teacher: {
    email: string;
    phone: string;
  };
  profile: {
    subjects: any[];
    rating?: number;
    reviews_count?: number;
  } | null;
  sessions_stats: {
    total: number;
  };
  verificationRequest: {
    id: number | string;
    created_at: string;
    status: string;
  };
  verification_status: {
    video_status: string;
  };
}

export default function TeacherContactDetails({ 
  teacher, 
  profile, 
  sessions_stats, 
  verificationRequest, 
  verification_status 
}: TeacherContactDetailsProps) {
  const [openSchedule, setOpenSchedule] = useState(false);
  const [localVideoStatus, setLocalVideoStatus] = useState(verification_status.video_status);

  const handleScheduled = () => {
    // Update local state immediately for instant feedback
    setLocalVideoStatus('scheduled');
    setOpenSchedule(false);
    
    // Show success message
    toast.success('Verification call scheduled successfully!');
    
    // Background sync with server
    router.reload({ only: ['latest_call', 'verification_status', 'verificationRequest'] });
  };

  const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  };

  const getVideoStatusBadge = (status: string) => {
    switch (status) {
      case 'scheduled':
        return (
          <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-blue-100 text-blue-800">
            <Video className="h-4 w-4 mr-1" />
            Scheduled
          </span>
        );
      case 'completed':
        return (
          <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-orange-100 text-orange-800">
            <Video className="h-4 w-4 mr-1" />
            Completed
          </span>
        );
      case 'passed':
        return (
          <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-green-100 text-green-800">
            <Video className="h-4 w-4 mr-1" />
            Passed
          </span>
        );
      case 'failed':
        return (
          <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-red-100 text-red-800">
            <Video className="h-4 w-4 mr-1" />
            Failed
          </span>
        );
      default:
        return (
          <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-blue-100 text-blue-800">
            <Video className="h-4 w-4 mr-1" />
            Not Scheduled
          </span>
        );
    }
  };

  const subjectsText = profile?.subjects?.map(subject => subject.name).join(', ') || 'No subjects assigned';

  return (
    <>
      <Card className="mb-8 shadow-sm">
        <CardContent className="p-6">
          <div className="flex items-center justify-between">
            <div className="space-y-6">
              {/* First Row: Email and Phone */}
              <div className="flex items-center gap-6">
                <div className="flex items-center gap-3">
                  <Mail className="h-5 w-5 text-teal-600" />
                  <span className="text-gray-700">{teacher.email}</span>
                </div>
                <div className="flex items-center gap-1">
                  <Phone className="h-5 w-5 text-teal-600" />
                  <span className="text-gray-700">{teacher.phone || 'Phone not provided'}</span>
                </div>
              </div>

              {/* Second Row: Subjects, Sessions */}
              <div className="flex items-center gap-6">
                <div className="flex items-center gap-3">
                  <FileText className="h-5 w-5 text-teal-600" />
                  <span className="text-gray-700">Subjects: {subjectsText}</span>
                </div>
                <div className="flex items-center gap-6">
                  <div className="flex items-center gap-2">
                    <Calendar className="h-5 w-5 text-teal-600" />
                    <span className="text-gray-700">{sessions_stats.total} Sessions</span>
                  </div>
                </div>
              </div>

              {/* Third Row: Rating and Reviews */}
              <div className="flex items-center">
                <div className="flex items-center gap-3">
                  <Star className="h-5 w-5 text-teal-600" />
                  <span className="text-gray-700">
                    {profile?.rating ? `${profile.rating.toFixed(1)} (${profile.reviews_count || 0} Reviews)` : 'No reviews yet'}
                  </span>
                </div>
              </div>

              {/* Divider */}
              <div className="border-t border-gray-200 pt-4"></div>

              {/* Fourth Row: Verification Status */}
              <div className="flex justify-between items-center mb-6">
                <div className="flex items-center gap-2">
                  <span className="text-gray-700 font-medium">Submitted On:</span>
                  <span className="inline-flex items-center px-2 py-1 rounded-md text-sm font-medium bg-gray-100 text-gray-700">
                    {formatDate(verificationRequest.created_at)}
                  </span>
                </div>
                <div className="flex items-center gap-2">
                  <span className="text-gray-700 font-medium">Live Video Status:</span>
                  {getVideoStatusBadge(localVideoStatus)}
                </div>
              </div>

              {/* Action Button */}
              <div className="flex justify-center">
                {verificationRequest.status === 'rejected' ? (
                  <div className="text-center">
                    <div className="text-red-600 text-sm font-medium mb-2">
                      ❌ Verification Request Rejected
                    </div>
                    <div className="text-gray-600 text-xs mb-3">
                      Cannot schedule video verification for rejected requests.
                    </div>
                    <div className="space-y-2">
                      <Button 
                        variant="outline"
                        className="text-gray-600 border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm w-full"
                        disabled
                      >
                        Schedule Verification Call
                      </Button>
                      <Button 
                        variant="outline"
                        className="text-blue-600 border-blue-300 hover:bg-blue-50 px-4 py-2 rounded-lg text-sm w-full"
                        onClick={() => {
                          if (confirm('Are you sure you want to reopen this verification request? This will allow the teacher to resubmit documents and schedule video verification.')) {
                            // TODO: Implement reopen functionality
                            alert('Reopen functionality will be implemented');
                          }
                        }}
                      >
                        Reopen Verification Request
                      </Button>
                    </div>
                  </div>
                ) : verificationRequest.status === 'verified' ? (
                  <div className="text-center">
                    <div className="text-green-600 text-sm font-medium mb-2">
                      ✅ Verification Completed
                    </div>
                    <div className="text-gray-600 text-xs mb-3">
                      Teacher has been successfully verified.
                    </div>
                    <Button 
                      variant="outline"
                      className="text-gray-600 border-gray-300 hover:bg-gray-50 px-4 py-2 rounded-lg text-sm"
                      disabled
                    >
                      Schedule Verification Call
                    </Button>
                  </div>
                ) : (
                  <Button 
                    className="bg-white text-teal-700 border border-teal-700 hover:bg-teal-50 px-4 py-2 rounded-lg text-sm"
                    onClick={() => setOpenSchedule(true)}
                  >
                    Schedule Verification Call
                  </Button>
                )}
              </div>
            </div>
            <div className="text-right">
              <Button variant="link" className="text-sm p-0 h-auto cursor-pointer" disabled>
                Edit
              </Button>
            </div>
          </div>
        </CardContent>
      </Card>

      <ScheduleVerificationModal 
        isOpen={openSchedule}
        onOpenChange={setOpenSchedule}
        verificationRequestId={verificationRequest.id}
        verificationStatus={verificationRequest.status}
        onScheduled={handleScheduled}
      />
    </>
  );
}
