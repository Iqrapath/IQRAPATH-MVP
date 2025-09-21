/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=200-7399&t=barbCY4Jn7yoOuNr-0
 * Export: Past Sessions component with table layout
 * 
 * EXACT SPECS FROM FIGMA:
 * - Table layout with headers: Date, Time, Student, Subject
 * - Light gray header background with rounded corners
 * - Session rows with proper spacing and typography
 * - Time display with large teal start time and smaller gray end time
 */
import React, { useState, useEffect } from 'react';
import axios from 'axios';

interface Session {
    id: number;
    date: string;
    startTime: string;
    endTime: string;
    subject: string;
    student: string;
    status: 'completed' | 'cancelled' | 'no_show';
    rating?: number;
    feedback?: string;
}

export default function PastSessions() {
    const [sessions, setSessions] = useState<Session[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    // Fetch past sessions from API
    useEffect(() => {
        const fetchPastSessions = async () => {
            try {
                setLoading(true);
                const response = await axios.get('/teacher/sessions/past', {
                    withCredentials: true
                });
                
                if (response.data.success) {
                    setSessions(response.data.sessions);
                } else {
                    setError('Failed to fetch past sessions');
                }
            } catch (err: any) {
                console.error('Error fetching past sessions:', err);
                setError('Failed to fetch past sessions');
            } finally {
                setLoading(false);
            }
        };

        fetchPastSessions();
    }, []);

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        const day = date.getDate();
        const month = date.toLocaleString('default', { month: 'long' });
        return { day, month };
    };

    if (loading) {
        return (
            <div className="flex items-center justify-center py-12">
                <div className="text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto mb-4"></div>
                    <p className="text-gray-600">Loading past sessions...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="text-center py-12">
                <p className="text-red-600 mb-4">{error}</p>
                <button 
                    onClick={() => window.location.reload()}
                    className="px-4 py-2 bg-teal-600 text-white rounded-lg hover:bg-teal-700"
                >
                    Try Again
                </button>
            </div>
        );
    }

    return (
        <div className="space-y-4">
            {/* Table Header */}
            <div className="bg-gray-100 rounded-lg px-6 py-4">
                <div className="grid grid-cols-4 gap-4 text-sm font-medium text-gray-600">
                    <div>Date</div>
                    <div>Time</div>
                    <div>Student</div>
                    <div>Subject</div>
                </div>
            </div>

            {/* Session Rows */}
            <div className="space-y-3">
                {sessions.map((session) => {
                    const { day, month } = formatDate(session.date);
                    return (
                        <div key={session.id} className="bg-white border border-gray-200 rounded-lg px-6 py-4">
                            <div className="grid grid-cols-4 gap-4 items-center">
                                {/* Date Column */}
                                <div className="flex items-center space-x-2">
                                    <span className="text-lg font-semibold text-gray-900">{day}</span>
                                    <div className="w-px h-6 bg-gray-300"></div>
                                    <span className="text-sm text-gray-600">{month}</span>
                                </div>

                                {/* Time Column */}
                                <div className="space-y-1">
                                    <div className="text-xl font-bold text-teal-600">
                                        {session.startTime}
                                    </div>
                                    <div className="text-sm text-gray-500">
                                        {session.endTime}
                                    </div>
                                </div>

                                {/* Student Column */}
                                <div className="text-sm font-medium text-gray-900">
                                    {session.student}
                                </div>

                                {/* Subject Column */}
                                <div className="text-sm font-medium text-gray-900">
                                    {session.subject}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Empty State */}
            {sessions.length === 0 && (
                <div className="text-center py-12 text-gray-500">
                    <p>No past sessions found</p>
                </div>
            )}
        </div>
    );
}