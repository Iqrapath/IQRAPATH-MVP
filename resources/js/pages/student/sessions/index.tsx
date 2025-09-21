/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=387-22195&t=O1w7ozri9pYud8IO-0
 * Export: Sessions index page with tab navigation and session cards
 * 
 * EXACT SPECS FROM FIGMA:
 * - Tab navigation with underline indicators
 * - Session cards with subject icons, progress bars, ratings
 * - Button styling with rounded corners and specific colors
 * - Spacing and typography as per design system
 */
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MessageCircle, Video } from 'lucide-react';
import StudentLayout from '@/layouts/student/student-layout';
import { Button } from '@/components/ui/button';
import { 
    Breadcrumb,
    BreadcrumbList,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { useState } from 'react';
import CompletedClassCard from './components/CompletedClassCard';
import OngoingClassCard from './components/OngoingClassCard';
import UpcomingClassCard from './components/UpcomingClassCard';

interface SessionListItem {
    id: number;
    session_uuid: string;
    title: string;
    teacher: string;
    teacher_avatar: string;
    subject: string;
    date: string;
    time: string;
    duration: number;
    status: string;
    meeting_link?: string;
    completion_date?: string;
    progress?: number;
    rating?: number;
    imageUrl?: string;
}

interface SessionStats {
    totalSessions: number;
    completedSessions: number;
    upcomingSessions: number;
}

interface SessionsIndexProps {
    sessions: {
        data: SessionListItem[];
        links: any[];
        current_page: number;
        last_page: number;
        total: number;
    };
    filter: string;
    stats: SessionStats;
}

export default function SessionsIndex({ sessions, filter, stats }: SessionsIndexProps) {
    const [activeTab, setActiveTab] = useState(filter || 'all');

    const handleTabChange = (newFilter: string) => {
        setActiveTab(newFilter);
        router.get('/student/sessions', { filter: newFilter }, { preserveState: true });
    };

    const getProgressColor = (progress: number = 0) => {
        if (progress >= 80) return '#10B981'; // Green
        if (progress >= 60) return '#F59E0B'; // Yellow
        return '#EF4444'; // Red
    };

    const getSubjectIcon = (subject: string) => {
        // Return appropriate icon based on subject
        if (subject.toLowerCase().includes('tajweed') || subject.toLowerCase().includes('quran')) {
            return '/assets/images/quran-icon.png';
        }
        if (subject.toLowerCase().includes('hadith')) {
            return '/assets/images/hadith-icon.png';
        }
        if (subject.toLowerCase().includes('fiqh')) {
            return '/assets/images/fiqh-icon.png';
        }
        return '/assets/images/default-subject-icon.png';
    };

    const renderStars = (rating: number = 0) => {
        return Array.from({ length: 5 }, (_, i) => (
            <span key={i} className={i < rating ? 'text-yellow-400' : 'text-gray-300'}>
                ★
            </span>
        ));
    };

    return (
        <StudentLayout pageTitle="My Sessions">
            <Head title="My Sessions" />

            <div className="min-h-screen bg-gray-50 p-4">
                {/* Breadcrumb */}
                <div className="mb-6">
                    <Breadcrumb>
                        <BreadcrumbList>
                            <BreadcrumbItem>
                                <BreadcrumbLink asChild>
                                    <Link href="/student/dashboard">Dashboard</Link>
                                </BreadcrumbLink>
                            </BreadcrumbItem>
                            <BreadcrumbSeparator />
                            <BreadcrumbItem>
                                <BreadcrumbPage>Quick start</BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                </div>

                {/* Tab Navigation */}
                <div className="bg-white rounded-2xl p-4 shadow-sm border border-gray-100 flex space-x-8 mb-8">
                    <button
                        onClick={() => handleTabChange('all')}
                        className={`pb-2 text-base font-medium relative transition-colors ${
                            activeTab === 'all'
                                ? 'text-[#2C7870]'
                                : 'text-gray-400 hover:text-gray-600'
                        }`}
                    >
                        Total Class ({stats.totalSessions})
                        {activeTab === 'all' && (
                            <span 
                                className="absolute bottom-0 left-0 h-[3px] bg-[#2C7870] rounded-full transition-all duration-300"
                                style={{ width: '50px' }}
                            ></span>
                        )}
                    </button>
                    <button
                        onClick={() => handleTabChange('completed')}
                        className={`pb-2 text-base font-medium relative transition-colors ${
                            activeTab === 'completed'
                                ? 'text-[#2C7870]'
                                : 'text-gray-400 hover:text-gray-600'
                        }`}
                    >
                        Completed Class
                        {activeTab === 'completed' && (
                            <span 
                                className="absolute bottom-0 left-0 h-[3px] bg-[#2C7870] rounded-full transition-all duration-300"
                                style={{ width: '50px' }}
                            ></span>
                        )}
                    </button>
                    <button
                        onClick={() => handleTabChange('upcoming')}
                        className={`pb-2 text-base font-medium relative transition-colors ${
                            activeTab === 'upcoming'
                                ? 'text-[#2C7870]'
                                : 'text-gray-400 hover:text-gray-600'
                        }`}
                    >
                        Upcoming class
                        {activeTab === 'upcoming' && (
                            <span 
                                className="absolute bottom-0 left-0 h-[3px] bg-[#2C7870] rounded-full transition-all duration-300"
                                style={{ width: '50px' }}
                            ></span>
                        )}
                    </button>
                </div>

                {/* Sessions List */}
                <div className="space-y-4">
                    {sessions.data && sessions.data.length > 0 ? (
                        // Backend service already filters the data, so just render it directly
                        sessions.data.map((session) => {
                            // Determine which card component to use based on session data
                            const isCompleted = session.status?.toLowerCase() === 'completed' || 
                                              (session.completion_date && (session.progress ?? 0) >= 100);
                            const isOngoing = session.status?.toLowerCase() === 'ongoing' || 
                                            session.status?.toLowerCase() === 'in_progress' ||
                                            ((session.progress ?? 0) > 0 && (session.progress ?? 0) < 100);
                            const isUpcoming = session.status?.toLowerCase() === 'upcoming' || 
                                             session.status?.toLowerCase() === 'scheduled' || 
                                             session.status?.toLowerCase() === 'confirmed' ||
                                             session.status?.toLowerCase() === 'pending' ||
                                             session.status?.toLowerCase() === 'approved';

                            if (isCompleted) {
                                return (
                                    <CompletedClassCard
                                        key={session.id}
                                        session={session}
                                        getSubjectIcon={getSubjectIcon}
                                        getProgressColor={getProgressColor}
                                        renderStars={renderStars}
                                    />
                                );
                            } else if (isOngoing) {
                                return (
                                    <OngoingClassCard
                                        key={session.id}
                                        session={session}
                                        getSubjectIcon={getSubjectIcon}
                                        getProgressColor={getProgressColor}
                                        renderStars={renderStars}
                                    />
                                );
                            } else if (isUpcoming) {
                                return (
                                    <UpcomingClassCard
                                        key={session.id}
                                        session={session}
                                        getSubjectIcon={getSubjectIcon}
                                        getProgressColor={getProgressColor}
                                        renderStars={renderStars}
                                    />
                                );
                            }
                            
                            // Default fallback - render as upcoming
                            return (
                                <UpcomingClassCard
                                    key={session.id}
                                    session={session}
                                    getSubjectIcon={getSubjectIcon}
                                    getProgressColor={getProgressColor}
                                    renderStars={renderStars}
                                />
                            );
                        })
                    ) : (
                        <div className="bg-white rounded-2xl p-12 text-center shadow-sm border border-gray-100">
                            <div className="text-gray-400 mb-4">
                                <span className="text-4xl">📚</span>
                            </div>
                            <h3 className="text-lg font-medium text-gray-900 mb-2">No sessions found</h3>
                            <p className="text-gray-600 mb-6">
                                {activeTab === 'completed' 
                                    ? "You haven't completed any sessions yet." 
                                    : activeTab === 'upcoming'
                                    ? "You don't have any upcoming sessions."
                                    : "You don't have any sessions yet."}
                            </p>
                            <Link href="/student/browse-teachers">
                                <Button className="bg-[#2C7870] hover:bg-[#236158]">
                                    Find Teachers
                                </Button>
                            </Link>
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {sessions.last_page > 1 && (
                    <div className="flex justify-center mt-8">
                        <div className="flex items-center space-x-2">
                            {sessions.links.map((link, index) => (
                                link.url ? (
                                    <Link
                                        key={index}
                                        href={link.url}
                                        className={`px-3 py-2 text-sm rounded-md ${
                                            link.active 
                                                ? 'bg-[#2C7870] text-white' 
                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        key={index}
                                        className="px-3 py-2 text-sm text-gray-400"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </StudentLayout>
    );
}
