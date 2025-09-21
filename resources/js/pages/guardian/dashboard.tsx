import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { type GuardianProfile, type User, type SharedData } from '@/types';
import GuardianOnboardingModal from '@/components/onboarding/guardian-onboarding-modal';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Monitor, GraduationCap, MessageCircleMore } from 'lucide-react';
import HeroBanner from './components/HeroBanner';
import GuardianStatsCard from './components/GuardianStatsCard';
import GuardianOverviewCard from './components/GuardianOverviewCard';
import UpcomingClasses from './components/UpcomingClasses';
import LearningProgressCard from './components/LearningProgressCard';
import TopRatedTeachers from './components/TopRatedTeachers';
import { StudentIcon } from '@/components/icons/student-icon';
import { PendingIcon } from '@/components/icons/pending-icon';
import { TrainingClassIcon } from '@/components/icons/training-class-icon';

type DaySchedule = {
    enabled: boolean;
    from: string;
    to: string;
};

interface Child {
    id: number;
    name: string;
    age: string;
    gender: string;
    preferred_subjects: string[];
    preferred_learning_times: {
        monday: DaySchedule;
        tuesday: DaySchedule;
        wednesday: DaySchedule;
        thursday: DaySchedule;
        friday: DaySchedule;
        saturday: DaySchedule;
        sunday: DaySchedule;
    };
}

interface Notification {
    id: string;
    sender: string;
    message: string;
    timestamp: string;
    avatar?: string | null;
    type: string;
    is_read: boolean;
}

interface GuardianDashboardProps {
    guardianProfile: GuardianProfile;
    children: Child[];
    students: Array<{
        user: {
            id: number;
            name: string;
            email?: string;
        };
        grade_level?: string;
        school_name?: string;
    }>;
    stats: {
        total_classes: number;
        completed_classes: number;
        upcoming_classes: number;
    };
    overviewData: {
        guardian_name: string;
        email: string;
        registered_children: number;
        active_plan: string;
    };
    upcomingClasses: Array<{
        id: number;
        title: string;
        teacher: string;
        date: string;
        time: string;
        status: 'Confirmed' | 'Pending';
        imageUrl: string;
    }>;
    learningProgressData: Array<{
        child_name: string;
        overall_percent: number;
        subjects: Array<{
            label: string;
            status: string;
            dot_color: 'yellow' | 'green';
        }>;
    }>;
    topRatedTeachers: Array<{
        id: number;
        name: string;
        subjects: string;
        location: string;
        rating: number;
        price: string;
        avatarUrl: string;
    }>;
    notifications: Notification[];
    availableSubjects: string[];
    showOnboarding?: boolean;
}

export default function GuardianDashboard({ guardianProfile, children, students, stats, overviewData, upcomingClasses, learningProgressData, topRatedTeachers, notifications, availableSubjects, showOnboarding = false }: GuardianDashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const [isOnboardingOpen, setIsOnboardingOpen] = useState(showOnboarding);
    
    return (
        <GuardianLayout pageTitle="Guardian Dashboard" notifications={notifications}>
            <Head title="Guardian Dashboard" />
            
            <div className="min-h-screen">
                <div className="">
                    {/* Hero */}
                    <HeroBanner
                        name={auth.user?.name}
                        subtitle={"Easily manage your children's Quran learning journey — add, <br> book, and track each child's progress from one place."}
                    />

                    {/* Stats card overlapping the hero */}
                    <div className="relative -mt-14 md:-mt-30 max-w-[700px] items-center justify-center mx-auto">
                        <GuardianStatsCard
                            headerAction={<button className="text-[#2c7870] hover:text-[#236158] font-medium">Browse Teachers</button>}
                            stats={[
                                { title: 'Total Class', value: stats.total_classes, icon: <TrainingClassIcon className="w-8 h-8" />, gradient: 'from-[#eef1fb]' },
                                { title: 'Class Completed', value: stats.completed_classes, icon: <StudentIcon className="w-8 h-8" />, gradient: 'from-[#e6fffb]' },
                                { title: 'Upcoming Class', value: stats.upcoming_classes, icon: <PendingIcon className="w-8 h-8" />, gradient: 'from-[#fff5da]' },
                            ]}
                        />
                    </div>

                    {/* Overview section matching screenshot */}
                    <div className="mt-6 md:mt-8">
                        <GuardianOverviewCard
                            guardianName={overviewData.guardian_name}
                            email={overviewData.email}
                            registeredChildren={overviewData.registered_children}
                            activePlan={overviewData.active_plan}
                        />
                    </div>

                    {/* Upcoming classes */}
                    <div className="mt-6 md:mt-8">
                        <UpcomingClasses classes={upcomingClasses} />
                    </div>

                    {/* Learning progress */}
                    <div className="mt-6 md:mt-8 mb-10 space-y-6">
                        {learningProgressData.length > 0 ? (
                            learningProgressData.map((child, index) => (
                                <LearningProgressCard
                                    key={index}
                                    juzName={child.child_name}
                                    percent={child.overall_percent}
                                    subjects={child.subjects.map(subject => ({
                                        label: subject.label,
                                        status: subject.status,
                                        dotColor: subject.dot_color
                                    }))}
                                />
                            ))
                        ) : (
                            <div className="rounded-[28px] bg-white shadow-sm border border-gray-100 p-6 md:p-8 max-w-6xl mx-auto text-center">
                                <h3 className="text-xl font-semibold text-gray-900 mb-2">No Learning Progress</h3>
                                <p className="text-gray-500">No children registered or learning progress data available.</p>
                            </div>
                        )}
                    </div>

                    {/* Top rated teachers */}
                    <div className="mt-6 md:mt-8">
                        <TopRatedTeachers teachers={topRatedTeachers} />
                    </div>
                </div>
            </div>

            {/* Onboarding Modal */}
            <GuardianOnboardingModal 
                isOpen={isOnboardingOpen}
                onClose={() => setIsOnboardingOpen(false)}
                user={auth.user}
                guardianProfile={guardianProfile}
                children={children}
                availableSubjects={availableSubjects}
            />
        </GuardianLayout>
    );
}