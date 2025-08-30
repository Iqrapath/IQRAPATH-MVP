/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=387-22195&t=O1w7ozri9pYud8IO-0
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
                        sessions.data.map((session) => (
                            <div key={session.id} className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
                                <div className="flex items-center justify-between">
                                    {/* Left Section - Subject Icon and Info */}
                                    <div className="flex items-center space-x-4">
                                        <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center overflow-hidden">
                                            <img 
                                                src={session.imageUrl || getSubjectIcon(session.subject)} 
                                                alt={session.title}
                                                className="w-12 h-12 object-cover"
                                            />
                                        </div>
                                        <div className="flex-1">
                                            <h3 className="text-lg font-semibold text-gray-900 mb-1">
                                                {session.title}
                                            </h3>
                                            <div className="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                                                <span className="w-2 h-2 bg-[#2C7870] rounded-full"></span>
                                                <span>{session.teacher}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Center Section - Session Details */}
                                    <div className="flex-1 px-6">
                                        <div className="space-y-2">
                                            <div className="flex items-center space-x-2 text-sm">
                                                <span className="text-red-500">📅</span>
                                                <span className="font-medium">{session.date}</span>
                                            </div>
                                            <div className="flex items-center space-x-2 text-sm">
                                                <span className="text-red-500">🕐</span>
                                                <span className="font-medium">{session.time}</span>
                                            </div>
                                            <div className="text-sm text-gray-600">
                                                Progress
                                            </div>
                                            
                                            {/* Progress Bar */}
                                            <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                                                <div 
                                                    className="h-2 rounded-full transition-all duration-300"
                                                    style={{ 
                                                        width: `${session.progress || 0}%`,
                                                        backgroundColor: getProgressColor(session.progress || 0)
                                                    }}
                                                ></div>
                                            </div>

                                            {/* Star Rating */}
                                            <div className="flex items-center space-x-1">
                                                {renderStars(session.rating || 0)}
                                                <span className="text-sm text-gray-600 ml-2">
                                                    {session.rating || 0}/5
                                                </span>
                                            </div>

                                            <div className="text-xs text-gray-500">
                                                Your Review - Great lesson, very knowledgeable teacher!
                                            </div>
                                        </div>
                                    </div>

                                    {/* Right Section - Action Buttons */}
                                    <div className="flex items-center space-x-3">
                                        {session.status === 'completed' ? (
                                            <button className="flex items-center space-x-2 px-4 py-2 text-[#2C7870] border border-[#2C7870] rounded-lg hover:bg-[#2C7870] hover:text-white transition-colors">
                                                <MessageCircle className="w-4 h-4" />
                                                <span>Give Feedback</span>
                                            </button>
                                        ) : (
                                            <button className="flex items-center space-x-2 px-4 py-2 text-[#2C7870] border border-[#2C7870] rounded-lg hover:bg-[#2C7870] hover:text-white transition-colors">
                                                <span>Book Another Class</span>
                                            </button>
                                        )}
                                        
                                        {session.meeting_link && session.status !== 'completed' && (
                                            <button 
                                                onClick={() => window.open(session.meeting_link, '_blank')}
                                                className="flex items-center space-x-2 px-4 py-2 bg-[#2C7870] text-white rounded-lg hover:bg-[#236158] transition-colors"
                                            >
                                                <Video className="w-4 h-4" />
                                            </button>
                                        )}

                                        <button className="flex items-center space-x-2 px-4 py-2 bg-[#2C7870] text-white rounded-lg hover:bg-[#236158] transition-colors">
                                            <MessageCircle className="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>
                        ))
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
                            <Link href="/find-teachers">
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
