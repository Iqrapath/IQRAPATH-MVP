import { useState } from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight, Calendar, Clock } from 'lucide-react';
import { SessionDetailsModal } from '@/components/SessionDetailsModal';

interface UpcomingSession {
    id: number;
    session_uuid?: string;
    student_name: string;
    student_avatar?: string;
    subject: string;
    teacher_name?: string;
    teacher_avatar?: string;
    date: string;
    start_time?: string;
    end_time?: string;
    time: string;
    duration?: string;
    status: string;
    meeting_platform?: 'zoom' | 'google_meet';
    meeting_link?: string;
    zoom_join_url?: string;
    google_meet_link?: string;
    student_notes?: string;
    teacher_notes?: string;
    student_rating?: number;
    teacher_rating?: number;
    student_review?: string;
    teacher_review?: string;
}

interface TeacherUpcomingSessionsProps {
    sessions: UpcomingSession[];
}

export function TeacherUpcomingSessions({ sessions }: TeacherUpcomingSessionsProps) {
    // State for selected month and year
    const [selectedMonth, setSelectedMonth] = useState(new Date().getMonth());
    const [selectedYear, setSelectedYear] = useState(new Date().getFullYear());
    
    // Modal state
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [selectedSession, setSelectedSession] = useState<UpcomingSession | null>(null);
    
    // Modal handlers
    const handleViewDetails = (session: UpcomingSession) => {
        setSelectedSession(session);
        setIsModalOpen(true);
    };
    
    const handleCloseModal = () => {
        setIsModalOpen(false);
        setSelectedSession(null);
    };
    
    const handleJoinSession = (sessionId: number) => {
        // TODO: Implement join session logic
        console.log('Joining session:', sessionId);
    };
    
    const handleStartChat = (sessionId: number) => {
        // TODO: Implement start chat logic
        console.log('Starting chat for session:', sessionId);
    };
    
    const handleStartVideoCall = (sessionId: number) => {
        // TODO: Implement start video call logic
        console.log('Starting video call for session:', sessionId);
    };
    
    const handleAddTeacherNotes = (sessionId: number, notes: string) => {
        // TODO: Implement add teacher notes logic
        console.log('Adding teacher notes for session:', sessionId, notes);
    };
    
    const handleRateSession = (sessionId: number, rating: number, review: string) => {
        // TODO: Implement rate session logic
        console.log('Rating session:', sessionId, rating, review);
    };
    
    // Generate all 12 months
    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];
    
    // Day labels
    const dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    
    // Get days for selected month
    const getDaysInMonth = (month: number, year: number) => {
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();
        
        const days = [];
        
        // Add empty cells for days before the first day of the month
        for (let i = 0; i < startingDayOfWeek; i++) {
            days.push(null);
        }
        
        // Add all days of the month
        for (let day = 1; day <= daysInMonth; day++) {
            days.push(day);
        }
        
        return days;
    };
    
    const daysInSelectedMonth = getDaysInMonth(selectedMonth, selectedYear);
    
    // Get session dates for highlighting
    const sessionDates = sessions.map(session => {
        const date = new Date(session.date);
        return {
            month: date.getMonth(),
            year: date.getFullYear(),
            day: date.getDate(),
            monthName: date.toLocaleString('default', { month: 'long' })
        };
    });
    
    // Check if a specific date has a session
    const hasSession = (month: number, year: number, day: number) => {
        return sessionDates.some(session => 
            session.month === month && session.year === year && session.day === day
        );
    };
    
    // Navigate to previous month
    const goToPreviousMonth = () => {
        if (selectedMonth === 0) {
            setSelectedMonth(11);
            setSelectedYear(selectedYear - 1);
        } else {
            setSelectedMonth(selectedMonth - 1);
        }
    };
    
    // Navigate to next month
    const goToNextMonth = () => {
        if (selectedMonth === 11) {
            setSelectedMonth(0);
            setSelectedYear(selectedYear + 1);
        } else {
            setSelectedMonth(selectedMonth + 1);
        }
    };
    
    // Format session date for display
    const formatSessionDate = (dateString: string) => {
        const date = new Date(dateString);
        return {
            month: date.toLocaleString('default', { month: 'long' }),
            day: date.getDate()
        };
    };

    return (
        <div className="space-y-6 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:p-8 max-w-6xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between">
                <h2 className="text-2xl font-bold text-gray-800">Your Upcoming Sessions</h2>
                <Button variant="link" className="text-teal-600 hover:text-teal-700 p-0">
                    Manage Availability
                </Button>
            </div>

            {/* Calendar Card */}
            <Card className="bg-white rounded-2xl shadow-sm border border-gray-100">
                <CardContent className="p-6">
                    {/* Month/Year Header */}
                    <div className="text-center mb-6">
                        <h3 className="text-xl font-bold text-gray-800">{months[selectedMonth]} {selectedYear}</h3>
                    </div>

                    {/* Month Navigation - Horizontal Scroll */}
                    <div className="mb-4">
                        <div className="flex space-x-3 overflow-x-auto scrollbar-hide pb-2">
                            {months.map((month, index) => (
                                <button
                                    key={month}
                                    onClick={() => setSelectedMonth(index)}
                                    className={`px-4 py-2 rounded-lg text-sm font-medium transition-colors whitespace-nowrap flex-shrink-0 ${
                                        index === selectedMonth
                                            ? 'bg-teal-100 text-teal-700 border border-teal-200'
                                            : 'text-gray-600 hover:text-gray-800 hover:bg-gray-100'
                                    }`}
                                >
                                    {month}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Day Navigation */}
                    <div className="flex items-center justify-between mb-4">
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            className="p-2"
                            onClick={goToPreviousMonth}
                        >
                            <ChevronLeft className="h-4 w-4 text-gray-500" />
                        </Button>
                        
                        <div className="flex-1 mx-4">
                            <div className="grid grid-cols-7 gap-1">
                                {dayLabels.map((label) => (
                                    <div key={label} className="text-center text-xs text-gray-500 font-medium py-2">
                                        {label}
                                    </div>
                                ))}
                                {daysInSelectedMonth.map((day, index) => {
                                    if (day === null) {
                                        return <div key={`empty-${index}`} className="h-8"></div>;
                                    }
                                    
                                    const hasUpcomingSession = hasSession(selectedMonth, selectedYear, day);
                                    
                                    return (
                                        <div key={day} className="text-center">
                                            <div
                                                className={`w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors mx-auto ${
                                                    hasUpcomingSession
                                                        ? 'bg-teal-100 text-teal-700 border border-teal-200'
                                                        : 'text-gray-800 hover:bg-gray-100'
                                                }`}
                                            >
                                                {day}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                        
                        <Button 
                            variant="ghost" 
                            size="sm" 
                            className="p-2"
                            onClick={goToNextMonth}
                        >
                            <ChevronRight className="h-4 w-4 text-gray-500" />
                        </Button>
                    </div>

                </CardContent>
            </Card>

            {/* Upcoming Sessions List */}
            <div className="space-y-3">
                {sessions.length > 0 ? (
                    sessions.map((session) => {
                        const sessionDate = formatSessionDate(session.date);
                        return (
                            <Card key={session.id} className="bg-white rounded-xl shadow-sm border border-gray-100">
                                <CardContent className="p-4">
                                    <div className="flex items-center space-x-4">
                                        {/* Date Card */}
                                        <div className="bg-yellow-100 rounded-lg p-3 min-w-[60px] text-center">
                                            <div className="text-xs text-gray-600 mb-1">{sessionDate.month}</div>
                                            <div className="text-lg font-bold text-gray-800">{sessionDate.day}</div>
                                        </div>

                                        {/* Session Info */}
                                        <div className="flex-1">
                                            <h4 className="font-bold text-gray-800 text-lg">{session.student_name}</h4>
                                            <div className="flex items-center space-x-2 mt-1">
                                                <span className="bg-teal-100 text-teal-700 px-2 py-1 rounded-full text-sm font-medium border border-teal-200">
                                                    {session.time}
                                                </span>
                                            </div>
                                        </div>

                                        {/* View Details Link */}
                                        <Button 
                                            variant="link" 
                                            className="text-teal-600 hover:text-teal-700 p-0"
                                            onClick={() => handleViewDetails(session)}
                                        >
                                            View Details
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        );
                    })
                ) : (
                    <Card className="bg-white rounded-xl shadow-sm border border-gray-100">
                        <CardContent className="p-8 text-center">
                            <Calendar className="h-12 w-12 text-gray-300 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-800 mb-2">No Upcoming Sessions</h3>
                            <p className="text-gray-600">You don't have any scheduled sessions yet.</p>
                        </CardContent>
                    </Card>
                )}
            </div>
            
            {/* Session Details Modal */}
            <SessionDetailsModal
                isOpen={isModalOpen}
                onClose={handleCloseModal}
                session={selectedSession}
                onJoinSession={handleJoinSession}
                onStartChat={handleStartChat}
                onStartVideoCall={handleStartVideoCall}
                onAddTeacherNotes={handleAddTeacherNotes}
                onRateSession={handleRateSession}
            />
        </div>
    );
}
