import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { ArrowLeft } from 'lucide-react';
import {
  TeacherProfileHeader,
  TeacherContactDetails,
  TeacherAboutSection,
  TeacherSubjectsSpecializations,
  TeacherDocumentsSection,
  TeacherPerformanceStats,
  TeacherActionButtons
} from './show-components';

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
}

interface TeachingSession {
  id: number;
  session_date: string;
  start_time: string;
  end_time: string;
  status: string;
  student: {
    id: number;
    name: string;
  };
  subject: {
    id: number;
    name: string;
  };
}

interface Props {
  teacher: Teacher;
  profile: TeacherProfile | null;
  earnings: TeacherEarnings | null;
  availabilities: any[];
  documents: any;
  sessions_stats: {
    total: number;
    completed: number;
    upcoming: number;
    cancelled: number;
  };
  upcoming_sessions: TeachingSession[];
  verification_status: {
    docs_status: 'pending' | 'verified' | 'rejected';
    video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
  };
}

export default function TeacherShow({ 
  teacher, 
  profile, 
  earnings, 
  availabilities, 
  documents, 
  sessions_stats, 
  upcoming_sessions,
  verification_status
}: Props) {
  const totalSessions = sessions_stats.completed || 0;

  return (
    <AdminLayout pageTitle={`Teacher Profile - ${teacher.name}`} showRightSidebar={false}>
      <Head title={`Teacher Profile - ${teacher.name}`} />
      
      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Teachers', href: '/admin/teachers' },
              { title: teacher.name, href: `/admin/teachers/${teacher.id}` },
            ]}
          />
        </div>

        {/* Back Button */}
        <div className="mb-6">
          <Link href="/admin/teachers">
            <Button variant="outline" className="flex items-center gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to Teachers
            </Button>
          </Link>
        </div>

        {/* Profile Header Section */}
        <div className="relative mb-8">
          <TeacherProfileHeader 
            teacher={teacher} 
            profile={profile} 
            earnings={earnings} 
            verificationStatus={verification_status}
          />
        </div>

        {/* Contact and Professional Details */}
        <TeacherContactDetails 
          teacher={teacher} 
          profile={profile} 
          totalSessions={totalSessions} 
        />
        
        {/* About Section */}
        <TeacherAboutSection profile={profile} teacherName={teacher.name} />

        {/* Subjects and Specializations Section */}
        <TeacherSubjectsSpecializations 
          profile={profile} 
          availabilities={availabilities}
          teacherId={teacher.id}
        />
        
        {/* Documents Section */}
        <TeacherDocumentsSection documents={documents} teacherId={teacher.id} />

        {/* Performance Stats Section */}
        <TeacherPerformanceStats 
          totalSessions={sessions_stats.total}
          averageRating={profile?.rating || 0}
          totalReviews={profile?.reviews_count || 0}
          upcomingSessions={upcoming_sessions}
        />

        {/* Action Buttons Section */}
        <div className="mt-8 mb-8">
          <TeacherActionButtons 
            teacherId={teacher.id}
            verificationStatus={verification_status}
            onSendMessage={() => {
              // Handle send message action
              console.log('Send message to teacher:', teacher.id);
            }}
            onRefresh={() => {
              // Refresh the page to get updated verification status
              window.location.reload();
            }}
          />
        </div>

      </div>
    </AdminLayout>
  );
}
