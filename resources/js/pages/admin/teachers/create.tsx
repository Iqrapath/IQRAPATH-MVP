import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import { Button } from '@/components/ui/button';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { ArrowLeft, Save, XCircle } from 'lucide-react';
import { toast } from 'sonner';

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

interface Props {
  subjects?: Array<{ id: number; name: string }>;
}

export default function TeacherCreate({ subjects = [] }: Props) {
  const [isSaving, setIsSaving] = useState(false);

  // Create a default teacher object for the form
  const defaultTeacher: Teacher = {
    id: 0, // Will be set after creation
    name: '',
    email: '',
    phone: '',
    avatar: null,
    location: '',
    created_at: new Date().toISOString(),
    status: 'active',
    last_active: new Date().toISOString(),
  };

  // Create default profile object
  const defaultProfile: TeacherProfile = {
    id: 0,
    bio: '',
    experience_years: 0,
    verified: false,
    languages: [],
    teaching_type: 'individual',
    teaching_mode: 'online',
    subjects: [],
    rating: 0,
    reviews_count: 0,
  };

  // Create default earnings object
  const defaultEarnings: TeacherEarnings = {
    wallet_balance: 0,
    total_earned: 0,
    total_withdrawn: 0,
    pending_payouts: 0,
  };

  const handleSaveAll = async () => {
    setIsSaving(true);
    try {
      // This is a placeholder. In a real scenario, you would collect data from all editable sections
      // and send it to the backend via Inertia.js post request.
      // For now, we'll just simulate a save.
      toast.success('Teacher created successfully!');
      router.visit(route('admin.teachers.index')); // Redirect to teachers list after creation
    } catch (error) {
      toast.error('Failed to create teacher.');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <AdminLayout pageTitle="Create New Teacher" showRightSidebar={false}>
      <Head title="Create New Teacher" />

      <div className="container py-6">
        {/* Breadcrumb */}
        <div className="mb-8">
          <Breadcrumbs
            breadcrumbs={[
              { title: 'Dashboard', href: '/admin/dashboard' },
              { title: 'Teachers', href: '/admin/teachers' },
              { title: 'Create', href: '/admin/teachers/create' },
            ]}
          />
        </div>

        {/* Header with Action Buttons */}
        <div className="flex items-center justify-between mb-6">
          <Link href={route('admin.teachers.index')}>
            <Button variant="outline" className="flex items-center gap-2">
              <ArrowLeft className="h-4 w-4" />
              Back to Teachers
            </Button>
          </Link>
          <div className="flex gap-3">
            <Button
              variant="outline"
              onClick={() => router.visit(route('admin.teachers.index'))}
              disabled={isSaving}
              className="flex items-center gap-2"
            >
              <XCircle className="h-4 w-4" />
              Cancel
            </Button>
            <Button
              onClick={handleSaveAll}
              disabled={isSaving}
              className="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white"
            >
              {isSaving ? 'Creating...' : <Save className="h-4 w-4" />}
              {isSaving ? 'Creating...' : 'Create Teacher'}
            </Button>
          </div>
        </div>

        {/* Profile Header Section (Editable in create view) */}
        <div className="relative mb-8">
        <TeacherProfileHeader
          teacher={defaultTeacher}
          profile={defaultProfile}
          earnings={defaultEarnings}
          verificationStatus={undefined}
        />
        </div>

        {/* Contact and Professional Details (Editable) */}
        <TeacherContactDetails
          teacher={defaultTeacher}
          profile={defaultProfile}
          totalSessions={0}
        />

        {/* About Section (Editable) */}
        <TeacherAboutSection profile={defaultProfile} teacherName={defaultTeacher.name} />

        {/* Subjects and Specializations Section (Editable) */}
        <TeacherSubjectsSpecializations
          profile={defaultProfile}
          availabilities={[]}
          teacherId={0}
        />

        {/* Documents Section (Empty for new teacher) */}
        <TeacherDocumentsSection documents={{ id_verifications: [], certificates: [], resume: null }} teacherId={0} />

        {/* Performance Stats Section (Empty for new teacher) */}
        <TeacherPerformanceStats
          totalSessions={0}
          averageRating={0}
          totalReviews={0}
          upcomingSessions={[]}
        />

        {/* Footer Action Buttons */}
        <div className="mt-8 flex justify-end gap-3">
          <Button
            variant="outline"
            onClick={() => router.visit(route('admin.teachers.index'))}
            disabled={isSaving}
            className="flex items-center gap-2"
          >
            <XCircle className="h-4 w-4" />
            Cancel
          </Button>
          <Button
            onClick={handleSaveAll}
            disabled={isSaving}
            className="flex items-center gap-2 bg-teal-600 hover:bg-teal-700 text-white"
          >
            {isSaving ? 'Creating...' : <Save className="h-4 w-4" />}
            {isSaving ? 'Creating...' : 'Create Teacher'}
          </Button>
        </div>
      </div>
    </AdminLayout>
  );
}
