import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { ArrowLeft, Save, X } from 'lucide-react';
import {
  TeacherProfileHeader,
  TeacherContactDetails,
  TeacherAboutSection,
  TeacherSubjectsSpecializations,
  TeacherDocumentsSection,
  TeacherPerformanceStats,
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

interface VerificationStatus {
  docs_status: 'pending' | 'verified' | 'rejected';
  video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
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
  upcoming_sessions: any[];
  verification_status: {
    docs_status: 'pending' | 'verified' | 'rejected';
    video_status: 'not_scheduled' | 'scheduled' | 'completed' | 'passed' | 'failed';
  };
}

export default function TeacherEdit({ 
  teacher, 
  profile, 
  earnings, 
  availabilities, 
  documents, 
  sessions_stats, 
  upcoming_sessions,
  verification_status
}: Props) {
  const totalSessions = sessions_stats?.completed || 0;

  return (
    <AdminLayout pageTitle={`Edit Teacher - ${teacher.name}`} showRightSidebar={false}>
      <Head title={`Edit Teacher - ${teacher.name}`} />
      
      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Teachers', href: '/admin/teachers' },
              { title: teacher.name, href: `/admin/teachers/${teacher.id}` },
              { title: 'Edit', href: `/admin/teachers/${teacher.id}/edit` },
            ]}
          />
        </div>

        {/* Header with Back and Action Buttons */}
        <div className="mb-6 flex items-center justify-between">
          <Link href={`/admin/teachers/${teacher.id}`}>
            <Button variant="outline" className="flex items-center gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to Profile
            </Button>
          </Link>
          
          <div className="flex items-center gap-3">
            <Link href={`/admin/teachers/${teacher.id}`}>
              <Button variant="outline" className="flex items-center gap-2">
                <X className="h-4 w-4" />
                Cancel
              </Button>
            </Link>
            <Button className="flex items-center gap-2 bg-teal-600 hover:bg-teal-700">
              <Save className="h-4 w-4" />
              Save Changes
            </Button>
          </div>
        </div>

        {/* Profile Header Section - Read Only */}
        <div className="relative mb-8">
          <TeacherProfileHeader 
            teacher={teacher} 
            profile={profile} 
            earnings={earnings} 
            verificationStatus={verification_status}
          />
        </div>

        {/* Contact and Professional Details - Editable */}
        <TeacherContactDetails 
          teacher={teacher} 
          profile={profile} 
          totalSessions={totalSessions} 
        />
        
        {/* About Section - Editable */}
        <TeacherAboutSection profile={profile} teacherName={teacher.name} />

        {/* Subjects and Specializations Section - Editable */}
        <TeacherSubjectsSpecializations 
          profile={profile} 
          availabilities={availabilities || []}
          teacherId={teacher.id}
        />
        
        {/* Documents Section - Read Only */}
        <TeacherDocumentsSection documents={documents || []} teacherId={teacher.id} />

        {/* Performance Stats Section - Read Only */}
        <TeacherPerformanceStats 
          totalSessions={sessions_stats?.total || 0}
          averageRating={profile?.rating || 0}
          totalReviews={profile?.reviews_count || 0}
          upcomingSessions={upcoming_sessions || []}
        />

        {/* Bottom Action Buttons */}
        <div className="mt-8 mb-8 flex justify-end gap-3">
          <Link href={`/admin/teachers/${teacher.id}`}>
            <Button variant="outline" className="flex items-center gap-2">
              <X className="h-4 w-4" />
              Cancel
            </Button>
          </Link>
          <Button className="flex items-center gap-2 bg-teal-600 hover:bg-teal-700">
            <Save className="h-4 w-4" />
            Save All Changes
          </Button>
        </div>

      </div>
    </AdminLayout>
  );
}
