import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import { type TeacherProfile, type User } from '@/types';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { TeacherStatsOverview } from './components/TeacherStatsOverview';
import { TeacherUpcomingSessions } from './components/TeacherUpcomingSessions';
import { RecommendedStudents } from './components/RecommendedStudents';
import TeacherVerificationSuccessModal from '@/components/teacher/TeacherVerificationSuccessModal';

interface UpcomingSession {
    id: number;
    student_name: string;
    subject: string;
    date: string;
    time: string;
    status: string;
}

interface TeacherDashboardProps {
    teacherProfile: TeacherProfile;
    user?: User;
    stats: {
        activeStudents: number;
        upcomingSessions: number;
        pendingRequests: number;
    };
    upcomingSessions: UpcomingSession[];
    showVerificationSuccess?: boolean;
}

export default function TeacherDashboard({ 
    teacherProfile, 
    user, 
    stats, 
    upcomingSessions,
    showVerificationSuccess = false 
}: TeacherDashboardProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);

    useEffect(() => {
        if (showVerificationSuccess) {
            setIsModalOpen(true);
        }
    }, [showVerificationSuccess]);

    return (
        <TeacherLayout pageTitle="Teacher Dashboard">
            <Head title="Teacher Dashboard" />
            
            <div className="space-y-8">
                {/* Welcome Header */}
                <div className="flex items-center space-x-3">
                    {/* <div className="w-12 h-12 bg-gray-200 rounded-full flex items-center justify-center">
                        <span className="text-gray-600 text-sm font-medium">
                            {user?.name ? user.name.charAt(0).toUpperCase() : 'T'}
                        </span>
                    </div> */}
                    <div>
                        <span className="text-2xl text-gray-700">Welcome</span>
                        <span className="text-3xl font-bold text-gray-800 ml-2">{user?.name || 'Teacher'}</span>
                    </div>
                </div>

                {/* Stats Overview */}
                <TeacherStatsOverview stats={stats} />

                {/* Upcoming Sessions */}
                <TeacherUpcomingSessions sessions={upcomingSessions} />

                {/* Recommended Students */}
                <RecommendedStudents 
                    teacherId={user?.id || 0}
                    teacherSubjects={teacherProfile?.subjects || []}
                    teacherSpecializations={teacherProfile?.specialization ? [teacherProfile.specialization] : []}
                />
            </div>

            {/* Teacher Verification Success Modal */}
            <TeacherVerificationSuccessModal
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
                teacherName={user?.name || 'Teacher'}
            />
        </TeacherLayout>
    );
} 