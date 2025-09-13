/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=200-7399&t=barbCY4Jn7yoOuNr-0
 * Export: Upcoming Sessions component with calendar and session cards
 * 
 * EXACT SPECS FROM FIGMA:
 * - Calendar with month/year display and navigation
 * - Month list with current month highlighted
 * - Day selection with highlighted days
 * - Session cards with time, subject, teacher, and join button
 * - Clean white background with teal accents
 */
import React, { useState, useEffect } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ChevronLeft, ChevronRight } from 'lucide-react';
import SessionCard from './SessionCard';
import axios from 'axios';

interface Session {
    id: number;
    date: string;
    startTime: string;
    endTime: string;
    subject: string;
    teacher: string;
    student: string;
    status: 'scheduled' | 'in_progress' | 'completed' | 'cancelled';
}

export default function UpcomingSessions() {
    const [currentDate, setCurrentDate] = useState(new Date());
    const [selectedDate, setSelectedDate] = useState(new Date().getDate());
    const [sessions, setSessions] = useState<Session[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    // Fetch upcoming sessions from API
    const fetchUpcomingSessions = async () => {
        try {
            setLoading(true);
            setError(null);
            // Get CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            
            const response = await axios.get('/teacher/sessions/upcoming', {
                withCredentials: true,
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            if (response.data.success) {
                setSessions(response.data.sessions || []);
            } else {
                setError('Failed to fetch upcoming sessions');
            }
        } catch (err: any) {
            console.error('Error fetching upcoming sessions:', err);
            console.error('Error response:', err.response?.data);
            console.error('Error status:', err.response?.status);
            setError(`Failed to fetch upcoming sessions: ${err.response?.data?.message || err.message}`);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchUpcomingSessions();
    }, []);

    const months = [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'
    ];

    const daysOfWeek = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const currentMonth = currentDate.getMonth();
    const currentYear = currentDate.getFullYear();
    const currentMonthName = months[currentMonth];

    // Get days in current month
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const firstDayOfMonth = new Date(currentYear, currentMonth, 1).getDay();

    // Generate days array
    const days = [];
    for (let i = 1; i <= daysInMonth; i++) {
        days.push(i);
    }

    // Get sessions for selected date
    const selectedDateSessions = sessions.filter(session => {
        const sessionDate = new Date(session.date);
        return sessionDate.getDate() === selectedDate && 
               sessionDate.getMonth() === currentMonth && 
               sessionDate.getFullYear() === currentYear;
    });

    const navigateMonth = (direction: 'prev' | 'next') => {
        setCurrentDate(prev => {
            const newDate = new Date(prev);
            if (direction === 'prev') {
                newDate.setMonth(prev.getMonth() - 1);
            } else {
                newDate.setMonth(prev.getMonth() + 1);
            }
            return newDate;
        });
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading upcoming sessions...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="text-center py-12">
                <p className="text-red-600 mb-4">{error}</p>
                <Button onClick={fetchUpcomingSessions}>
                    Try Again
                </Button>
            </div>
        );
    }

    return (
        <div className="space-y-6">
            {/* Calendar Component */}
            <Card className="rounded-2xl border border-gray-200 shadow-sm">
                <CardContent className="p-6">
                    {/* Month and Year Header */}
                    <div className="text-center mb-6">
                        <h2 className="text-2xl font-semibold text-gray-900">
                            {currentMonthName} {currentYear}
                        </h2>
                    </div>

                    {/* Month Navigation */}
                    <div className="flex items-center justify-between mb-6">
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigateMonth('prev')}
                            className="p-2 hover:bg-gray-100"
                        >
                            <ChevronLeft className="w-5 h-5" />
                        </Button>

                        {/* Month List */}
                        <div className="flex space-x-4 overflow-x-auto">
                            {months.slice(0, 6).map((month, index) => (
                                <button
                                    key={month}
                                    onClick={() => setCurrentDate(new Date(currentYear, index, 1))}
                                    className={`px-3 py-1 text-sm font-medium transition-colors ${
                                        index === currentMonth
                                            ? 'text-teal-600 bg-teal-50 rounded-lg'
                                            : 'text-gray-600 hover:text-gray-900'
                                    }`}
                                >
                                    {month}
                                </button>
                            ))}
                            <span className="text-gray-400">...</span>
                        </div>

                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => navigateMonth('next')}
                            className="p-2 hover:bg-gray-100"
                        >
                            <ChevronRight className="w-5 h-5" />
                        </Button>
                    </div>

                    {/* Days of Week */}
                    <div className="grid grid-cols-7 gap-2 mb-4">
                        {daysOfWeek.map((day) => (
                            <div key={day} className="text-center text-sm font-medium text-gray-500 py-2">
                                {day}
                            </div>
                        ))}
                    </div>

                    {/* Calendar Days */}
                    <div className="grid grid-cols-7 gap-2">
                        {/* Empty cells for days before month starts */}
                        {Array.from({ length: firstDayOfMonth }, (_, i) => (
                            <div key={`empty-${i}`} className="h-10"></div>
                        ))}
                        
                        {/* Days of the month */}
                        {days.map((day) => {
                            const hasSession = sessions.some(session => {
                                const sessionDate = new Date(session.date);
                                return sessionDate.getDate() === day && 
                                       sessionDate.getMonth() === currentMonth && 
                                       sessionDate.getFullYear() === currentYear;
                            });
                            
                            return (
                                <button
                                    key={day}
                                    onClick={() => setSelectedDate(day)}
                                    className={`h-10 w-10 rounded-full text-sm font-medium transition-colors ${
                                        day === selectedDate
                                            ? 'bg-teal-100 text-teal-700 ring-2 ring-teal-200'
                                            : hasSession
                                            ? 'bg-teal-50 text-teal-600 hover:bg-teal-100'
                                            : 'text-gray-700 hover:bg-gray-100'
                                    }`}
                                >
                                    {day}
                                </button>
                            );
                        })}
                    </div>
                </CardContent>
            </Card>

            {/* Selected Date Sessions */}
            <div className="space-y-4">
                <div className="flex items-center space-x-2">
                    <h3 className="text-lg font-semibold text-gray-900">
                        {selectedDate} {currentMonthName}
                    </h3>
                    <div className="flex-1 h-px bg-gray-200"></div>
                </div>

                {selectedDateSessions.length > 0 ? (
                    <div className="space-y-3">
                        {selectedDateSessions.map((session) => (
                            <SessionCard key={session.id} session={session} />
                        ))}
                    </div>
                ) : (
                    <div className="text-center py-8 text-gray-500">
                        <p>No sessions scheduled for this date</p>
                    </div>
                )}
            </div>
        </div>
    );
}
