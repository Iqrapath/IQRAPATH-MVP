import React from 'react';
import { Head } from '@inertiajs/react';
import AdminLayout from '@/layouts/admin/admin-layout';
import StudentProfileHeader from './show-components/student-profile-header';
import StudentPlanOverview from './show-components/student-plan-overview';
import StudentClassHistory from './show-components/student-class-history';
import StudentAttendanceSummary from './show-components/student-attendance-summary';
import StudentActionButtons from './show-components/student-action-buttons';
import { Breadcrumbs } from '@/components/breadcrumbs';

const breadcrumbs = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Students', href: '/admin/students' },
    { title: 'Learning Progress', href: '/admin/students/learning-progress' },
];

interface Student {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    avatar: string | null;
    role: string;
    status: string;
    registration_date: string | null;
    location: string | null;
    guardian?: {
        id: number;
        name: string;
        email: string;
        phone: string | null;
    } | null;
    profile?: {
        date_of_birth: string | null;
        gender: string | null;
        grade_level: string | null;
        school_name: string | null;
        learning_goals: string | null;
        subjects_of_interest: string[] | null;
        preferred_learning_times: string[] | null;
        teaching_mode: string | null;
        additional_notes: string | null;
        location: string | null;
    } | null;
}

interface Props {
    student: Student;
    learningSessions?: any[];
}

export default function StudentLearningProgress({ student, learningSessions = [] }: Props) {
    return (
        <AdminLayout pageTitle={`${student.name} - Learning Progress`} showRightSidebar={false}>
            <Head title={`${student.name} - Learning Progress`} />

            <Breadcrumbs breadcrumbs={breadcrumbs} />
            
            <div className="py-6">
                <StudentProfileHeader student={student} />
                
                <div className="mt-6">
                    <StudentPlanOverview subscription={null} />
                </div>

                <div className="mt-6">
                    <StudentClassHistory classHistory={[]} />
                </div>

                <div className="mt-6">
                    <StudentAttendanceSummary attendanceStats={null} />
                </div>

                <div className="mt-6"> <StudentActionButtons student={student} /> </div>
            </div>
        </AdminLayout>
    );
}
