import { Head } from '@inertiajs/react';
import { useState } from 'react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { TabNavigation } from './TabNavigation';
import { ActiveStudentTab } from './ActiveStudentTab';
import { UpcomingSessionTab } from './UpcomingSessionTab';
import { PendingRequestTab } from './PendingRequestTab';
import { 
    Calendar, 
    Clock, 
    Users, 
    Video, 
    MessageCircle, 
    MoreVertical,
    Filter,
    Search,
    Plus
} from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';

const breadcrumbs = [
    { title: 'Dashboard', href: '/teacher/dashboard' },
    { title: 'Sessions', href: '/teacher/sessions' }
];

interface Session {
    id: number;
    student: {
        id: number;
        name: string;
        avatar?: string;
        isOnline?: boolean;
    };
    subject: string;
    date: string;
    time: string;
    duration: string;
    status: 'upcoming' | 'ongoing' | 'completed' | 'cancelled';
    type: 'online' | 'in-person';
    meetingLink?: string;
    notes?: string;
}

interface Student {
    id: number;
    name: string;
    avatar?: string;
    level: string;
    sessionsCompleted: number;
    progress: number;
    rating: number;
    lastActive: string;
}

interface UpcomingSession {
    id: number;
    student_name: string;
    subject: string;
    date: string;
    time: string;
    status: string;
}

interface PendingRequest {
    id: number;
    student: {
        name: string;
        avatar?: string;
    };
    note: string;
    subject: string;
    requestedDate: string;
    requestedTime: string;
    status: 'pending';
}

interface TeacherSessionsProps {
    user?: any;
    teacherProfile?: any;
    activeStudents: Student[];
    upcomingSessions: UpcomingSession[];
    pendingRequests: PendingRequest[];
}

export default function TeacherSessions({ user, teacherProfile, activeStudents, upcomingSessions, pendingRequests }: TeacherSessionsProps) {
    const [activeTab, setActiveTab] = useState<'upcoming' | 'ongoing' | 'completed'>('upcoming');
    const [searchTerm, setSearchTerm] = useState('');
    const [filterType, setFilterType] = useState<'all' | 'online' | 'in-person'>('all');


    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'upcoming':
                return 'bg-blue-100 text-blue-800';
            case 'ongoing':
                return 'bg-green-100 text-green-800';
            case 'completed':
                return 'bg-gray-100 text-gray-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getTypeIcon = (type: string) => {
        return type === 'online' ? Video : Users;
    };

    const tabs = [
        { id: 'upcoming', label: 'Active Student', count: activeStudents.length },
        { id: 'ongoing', label: 'Upcoming Session', count: upcomingSessions.length },
        { id: 'completed', label: 'Pending Request', count: pendingRequests.length }
    ];

    return (
        <TeacherLayout pageTitle="Sessions">
            <Head title="Sessions" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                   <Breadcrumbs breadcrumbs={breadcrumbs} />
                </div>

                {/* Tabs */}
                <TabNavigation
                    tabs={tabs}
                    activeTab={activeTab}
                    onTabChange={(tabId) => setActiveTab(tabId as any)}
                />

                {/* Tab Content */}
                {activeTab === 'upcoming' && (
                    <ActiveStudentTab
                        students={activeStudents}
                        onViewProfile={(student) => console.log('View profile:', student)}
                        onChat={(student) => console.log('Chat with:', student)}
                        onVideoCall={(student) => console.log('Video call with:', student)}
                    />
                )}
                
                {activeTab === 'ongoing' && (
                    <UpcomingSessionTab
                        sessions={upcomingSessions}
                        onViewDetails={(session) => console.log('View details for session:', session)}
                    />
                )}
                
                {activeTab === 'completed' && (
                    <PendingRequestTab
                        requests={pendingRequests}
                        onAccept={(request) => console.log('Accept request:', request)}
                        onDecline={(request) => console.log('Decline request:', request)}
                        onViewDetails={(request) => console.log('View request details:', request)}
                    />
                )}
            </div>
        </TeacherLayout>
    );
}
