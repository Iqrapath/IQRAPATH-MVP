import React, { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Mail, Phone, FileText, Calendar, Star, Video } from 'lucide-react';
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
                  {getVideoStatusBadge(verification_status.video_status)}
                </div>
              </div>

              {/* Action Button */}
              <div className="flex justify-center">
                <Button 
                  className="bg-white text-teal-700 border border-teal-700 hover:bg-teal-50 px-4 py-2 rounded-lg text-sm"
                  onClick={() => setOpenSchedule(true)}
                >
                  Schedule Verification Call
                </Button>
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
        onScheduled={() => window.location.reload()}
      />
    </>
  );
}
