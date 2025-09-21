import { Head } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Breadcrumbs } from '@/components/breadcrumbs';
import { Check, X, Minus, RefreshCw } from 'lucide-react';
import { Button } from '@/components/ui/button';
import WeeklyClassAttendance from '../components/WeeklyClassAttendance';
import UpcomingGoal from '../components/UpcomingGoal';
import LearningProgressCard from '../components/LearningProgressCard';
import MemorizationProgress from '../components/MemorizationProgress';

const breadcrumbs = [
    { title: "Dashboard", href: route("guardian.dashboard") },
    { title: "Children Details", href: route("guardian.children.index") },
    { title: "View Progress", href: "#", className: "text-[#338078]" }
];

interface ProgressData {
    childName: string;
    weeklyProgress: {
        monday: 'attended' | 'missed' | 'no-session';
        tuesday: 'attended' | 'missed' | 'no-session';
        wednesday: 'attended' | 'missed' | 'no-session';
        thursday: 'attended' | 'missed' | 'no-session';
        friday: 'attended' | 'missed' | 'no-session';
        saturday: 'attended' | 'missed' | 'no-session';
        sunday: 'attended' | 'missed' | 'no-session';
    };
    weeklyAttendanceData: {
        monday: number;
        tuesday: number;
        wednesday: number;
        thursday: number;
        friday: number;
        saturday: number;
        sunday: number;
    };
    totalSessions: number;
    attendedSessions: number;
    missedSessions: number;
    attendanceRate: number;
    upcomingGoal: string;
    learningProgress: {
        currentJuz: string;
        progressPercentage: number;
        subjects: Array<{
            name: string;
            status: string;
            color: 'yellow' | 'green' | 'none';
        }>;
    };
}

interface ProgressPageProps {
    childId: number;
    progressData: ProgressData;
}

const getStatusIcon = (status: 'attended' | 'missed' | 'no-session') => {
    switch (status) {
        case 'attended':
            return <Check className="w-4 h-4 text-white" />;
        case 'missed':
            return <X className="w-4 h-4 text-white" />;
        case 'no-session':
            return <Minus className="w-4 h-4 text-gray-400" />;
        default:
            return <Minus className="w-4 h-4 text-gray-400" />;
    }
};

const getStatusColor = (status: 'attended' | 'missed' | 'no-session') => {
    switch (status) {
        case 'attended':
            return 'bg-green-500';
        case 'missed':
            return 'bg-red-500';
        case 'no-session':
            return 'bg-gray-200';
        default:
            return 'bg-gray-200';
    }
};

const daysOfWeek = [
    { key: 'monday', label: 'Mon' },
    { key: 'tuesday', label: 'Tue' },
    { key: 'wednesday', label: 'Wed' },
    { key: 'thursday', label: 'Thu' },
    { key: 'friday', label: 'Fri' },
    { key: 'saturday', label: 'Sat' },
    { key: 'sunday', label: 'Sun' },
];

export default function ChildProgress({ childId, progressData: initialProgressData }: ProgressPageProps) {
    const [progressData, setProgressData] = useState(initialProgressData);
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [lastUpdated, setLastUpdated] = useState(new Date());

    const refreshData = async () => {
        setIsRefreshing(true);
        try {
            const response = await fetch(route('guardian.children.progress.refresh', childId));
            const result = await response.json();
            
            if (result.success) {
                setProgressData(result.data);
                setLastUpdated(new Date(result.lastUpdated));
            }
        } catch (error) {
            console.error('Failed to refresh progress data:', error);
        } finally {
            setIsRefreshing(false);
        }
    };

    // Auto-refresh every 5 minutes
    useEffect(() => {
        const interval = setInterval(refreshData, 5 * 60 * 1000);
        return () => clearInterval(interval);
    }, [childId]);

    return (
        <GuardianLayout pageTitle="Progress Overview">
            <Head title="Progress Overview" />

            <div className="max-w-4xl mx-auto p-6">
                <div className="mb-6">
                    <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Header Section */}
                <div className="mb-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900 mb-3">
                                Progress Overview
                            </h1>
                            <p className="text-gray-600">
                                Track your child's Quran learning journey — attendance, memorization, and teacher feedback all in one glance.
                            </p>
                        </div>
                        <div className="flex items-center space-x-4">
                            <div className="text-sm text-gray-500">
                                Last updated: {lastUpdated.toLocaleTimeString()}
                            </div>
                            <Button
                                onClick={refreshData}
                                disabled={isRefreshing}
                                variant="outline"
                                size="sm"
                                className="flex items-center space-x-2"
                            >
                                <RefreshCw className={`w-4 h-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                                <span>{isRefreshing ? 'Refreshing...' : 'Refresh'}</span>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Progress Card */}
                <div className="bg-white rounded-3xl border border-gray-100 shadow-sm p-8 mb-8">
                    {/* Days of Week Header */}
                    <div className="grid grid-cols-7 gap-4 mb-4">
                        {daysOfWeek.map((day) => (
                            <div key={day.key} className="text-center">
                                <div className="text-sm font-medium text-gray-500">
                                    {day.label}
                                </div>
                            </div>
                        ))}
                    </div>

                    {/* Progress Indicators */}
                    <div className="grid grid-cols-7 gap-4">
                        {daysOfWeek.map((day) => {
                            const status = progressData.weeklyProgress[day.key as keyof ProgressData['weeklyProgress']];
                            return (
                                <div key={day.key} className="text-center">
                                    <div className={`w-8 h-8 mx-auto rounded-md flex items-center justify-center ${getStatusColor(status)}`}>
                                        {getStatusIcon(status)}
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>

                {/* Weekly Class Attendance Chart */}
                <WeeklyClassAttendance 
                    attendanceData={progressData.weeklyAttendanceData}
                    totalSessions={progressData.totalSessions}
                    attendedSessions={progressData.attendedSessions}
                />

                {/* Upcoming Goal */}
                <div className="mt-6">
                    <UpcomingGoal goal={progressData.upcomingGoal} />
                </div>

                {/* Memorization Progress */}
                <div className="mt-6">
                    <MemorizationProgress 
                        currentJuz={progressData.learningProgress.currentJuz}
                        progressPercentage={progressData.learningProgress.progressPercentage}
                        subjects={progressData.learningProgress.subjects}
                    />
                </div>
            </div>
        </GuardianLayout>
    );
}
