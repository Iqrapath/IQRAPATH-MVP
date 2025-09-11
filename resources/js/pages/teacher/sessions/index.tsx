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
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Sessions</h1>
                        <p className="text-gray-600 mt-1">Manage your teaching sessions</p>
                    </div>
                    <Button className="bg-teal-600 hover:bg-teal-700">
                        <Plus className="w-4 h-4 mr-2" />
                        Schedule Session
                    </Button>
                </div>

                {/* Search and Filter */}
                <div className="flex items-center space-x-4">
                    <div className="relative flex-1 max-w-md">
                        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 w-4 h-4" />
                        <input
                            type="text"
                            placeholder="Search sessions..."
                            value={searchTerm}
                            onChange={(e) => setSearchTerm(e.target.value)}
                            className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                        />
                    </div>
                    <select
                        value={filterType}
                        onChange={(e) => setFilterType(e.target.value as 'all' | 'online' | 'in-person')}
                        className="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-teal-500 focus:border-transparent"
                    >
                        <option value="all">All Types</option>
                        <option value="online">Online</option>
                        <option value="in-person">In-Person</option>
                    </select>
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
