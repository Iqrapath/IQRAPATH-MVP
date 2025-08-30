import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { type StudentProfile, type User, type SharedData, type StudentStats, type UpcomingSession, type RecommendedTeacher } from '@/types';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import StudentLayout from '@/layouts/student/student-layout';
import StudentOnboardingModal from '@/components/onboarding/student-onboarding-modal';
import HeroBanner from './components/HeroBanner';
import StudentStatsCard from './components/StudentStatsCard';
import UpcomingClasses from './components/UpcomingClasses';
import RecommendedTeachers from './components/RecommendedTeachers';
import { BookOpen, Clock, Star, Trophy, Users, Calendar, FileText, MoreHorizontal } from 'lucide-react';
import { TrainingClassIcon } from '@/components/icons/training-class-icon';
import { StudentIcon } from '@/components/icons/student-icon';
import { PendingIcon } from '@/components/icons/pending-icon';


interface StudentDashboardProps {
    studentProfile: StudentProfile;
    user?: User;
    availableSubjects: string[];
    showOnboarding?: boolean;
    // Real data from backend
    studentStats: StudentStats;
    upcomingSessions: UpcomingSession[];
    recommendedTeachers: RecommendedTeacher[];
}

export default function StudentDashboard({ 
    studentProfile, 
    user, 
    availableSubjects, 
    showOnboarding = false,
    studentStats,
    upcomingSessions,
    recommendedTeachers 
}: StudentDashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const [isOnboardingOpen, setIsOnboardingOpen] = useState(showOnboarding);

    // Use user from props if available, otherwise from auth
    const currentUser = user || auth.user;

    // Transform real data into format expected by components
    const stats = [
        {
            title: "Total Class",
            value: studentStats.totalSessions.toString(),
            icon: <TrainingClassIcon className="w-6 h-6" />,
            gradient: "from-purple-50",
            href: "/student/sessions?filter=all"
        },
        {
            title: "Class Completed",
            value: studentStats.completedSessions.toString(),
            icon: <StudentIcon className="w-6 h-6" />,
            gradient: "from-green-50",
            href: "/student/sessions?filter=completed"
        },
        {
            title: "Upcoming Class",
            value: studentStats.upcomingSessions.toString(),
            icon: <PendingIcon className="w-6 h-6" />,
            gradient: "from-yellow-50",
            href: "/student/sessions?filter=upcoming"
        }
    ];



    return (
        <StudentLayout pageTitle="Student Dashboard">
            <Head title="Student Dashboard" />

            <div className="space-y-8">
                {/* Hero Banner */}
                <div className="">

                    {/* Hero Banner */}
                    <HeroBanner
                        name={currentUser?.name || "Ahmed"}
                        subtitle="Ready to start learning?"
                    />

                    {/* Stats card overlapping the hero */}
                    <div className="relative -mt-14 md:-mt-30 max-w-[700px] items-center justify-center mx-auto">
                        {/* Stats Card */}
                        <StudentStatsCard stats={stats} />
                    </div>
                </div>

                {/* Upcoming Classes */}
                <UpcomingClasses classes={upcomingSessions} />

                {/* Top Rated Teachers */}
                <RecommendedTeachers teachers={recommendedTeachers} />
            </div>

            {/* Onboarding Modal */}
            {currentUser && (
                <StudentOnboardingModal
                    isOpen={isOnboardingOpen}
                    onClose={() => setIsOnboardingOpen(false)}
                    user={currentUser}
                    studentProfile={studentProfile}
                    availableSubjects={availableSubjects}
                />
            )}
        </StudentLayout>
    );
} 