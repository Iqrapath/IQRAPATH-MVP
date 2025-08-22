import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from "@/layouts/admin/admin-layout";
import { VerificationHeader, TeacherProfileHeader, TeacherContactDetails, VerificationDocumentsSection, VerificationActionButtons, VerificationCallDetailsCard } from './components';

interface Teacher {
  id: number;
  name: string;
  email: string;
  phone: string;
  avatar: string | null;
  location: string;
  created_at: string;
  status: string;
  last_active: string;
}

interface TeacherProfile {
  id: number;
  bio: string;
  experience_years: number;
  verified: boolean;
  languages: string[];
  teaching_type: string;
  teaching_mode: string;
  subjects: any[];
  rating?: number;
  reviews_count?: number;
}

interface TeacherEarnings {
  wallet_balance: number;
  total_earned: number;
  total_withdrawn: number;
  pending_payouts: number;
  recent_transactions: any[];
  pending_payout_requests: any[];
  calculated_at: string;
}

interface VerificationStatus {
  docs_status: 'pending' | 'verified' | 'rejected';
  video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
}

interface Props {
  verificationRequest: any;
  teacher: Teacher;
  profile: TeacherProfile | null;
  earnings: TeacherEarnings;
  availabilities: any[];
  documents: any[]; // flat list
  documents_grouped: any; // grouped for cards
  sessions_stats: {
    total: number;
    completed: number;
    upcoming: number;
    cancelled: number;
  };
  upcoming_sessions: any[];
  verification_status: VerificationStatus;
  latest_call?: {
    id: number | string;
    scheduled_at: string;
    platform: string;
    meeting_link?: string;
    notes?: string;
    status: string;
  } | null;
}

export default function VerificationShow({ 
  verificationRequest, 
  teacher, 
  profile, 
  earnings, 
  availabilities, 
  documents, 
  documents_grouped,
  sessions_stats, 
  upcoming_sessions,
  verification_status,
  latest_call
}: Props) {
  return (
    <AdminLayout pageTitle="Verification Request Details" showRightSidebar={false}>
      <Head title="Verification Request Details" />
      
      <div className="py-6">
        <VerificationHeader teacherName={teacher?.name} />
        
        {/* Profile Header Section */}
        <div className="relative mb-8">
          <TeacherProfileHeader 
            teacher={teacher}
            profile={profile}
            earnings={earnings}
            verificationStatus={verification_status}
          />
        </div>
        
        {/* Contact and Verification Details Section */}
        <div className="mb-8">
          <TeacherContactDetails 
            teacher={teacher}
            profile={profile}
            sessions_stats={sessions_stats}
            verificationRequest={verificationRequest}
            verification_status={verification_status}
          />
        </div>
        
        {/* Call Details Card */}
        <VerificationCallDetailsCard 
          call={latest_call || null} 
          verificationRequestId={verificationRequest.id}
          videoStatus={verification_status?.video_status as any}
          requestStatus={verificationRequest?.status as any}
        />

        {/* Documents Review Panel + Document Section */}
        <div className="mb-8">
          <VerificationDocumentsSection documentsFlat={documents} documentsGrouped={documents_grouped} teacherId={teacher.id} />
        </div>
        
        
        {/* Action Buttons Section */}
        <div className="mb-8">
          <VerificationActionButtons 
            verificationRequestId={verificationRequest.id}
            verificationStatus={verification_status}
            onApproved={() => window.location.reload()}
            onRejected={() => window.location.reload()}
          />
        </div>
        
        
        {/* Additional content will be added here */}
      </div>
    </AdminLayout>
  );
}
