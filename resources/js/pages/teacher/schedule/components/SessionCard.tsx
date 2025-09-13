/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=200-7399&t=barbCY4Jn7yoOuNr-0
 * Export: Session Card component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Time display: Large start time, smaller end time below
 * - Vertical separator line
 * - Subject and teacher name in teal background
 * - Join Session button on the right
 * - Clean white background with teal accents
 */
import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
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

interface SessionCardProps {
    session: Session;
}

export default function SessionCard({ session }: SessionCardProps) {
    const [joining, setJoining] = useState(false);

    const handleJoinSession = async () => {
        try {
            setJoining(true);
            const response = await axios.post(`/teacher/sessions/${session.id}/join`, {}, {
                withCredentials: true
            });
            
            if (response.data.success) {
                // Redirect to meeting link
                window.open(response.data.meeting_link, '_blank');
            } else {
                alert(response.data.message || 'Failed to join session');
            }
        } catch (error: any) {
            console.error('Error joining session:', error);
            alert(error.response?.data?.message || 'Failed to join session');
        } finally {
            setJoining(false);
        }
    };

    return (
        <div className="flex items-center space-x-4 p-4 bg-white border border-gray-200 rounded-xl shadow-sm">
            {/* Time Section */}
            <div className="flex-shrink-0 text-center">
                <div className="text-2xl font-bold text-gray-900">
                    {session.startTime}
                </div>
                <div className="text-sm text-gray-500">
                    {session.endTime}
                </div>
            </div>

            {/* Vertical Separator */}
            <div className="w-px h-16 bg-gray-200"></div>

            {/* Session Details */}
            <div className="flex-1 bg-teal-50 rounded-lg p-4">
                <div className="space-y-1">
                    <div className="text-lg font-semibold text-gray-900">
                        {session.subject}
                    </div>
                    <div className="text-sm text-gray-600">
                        {session.teacher}
                    </div>
                    <div className="text-xs text-gray-500">
                        Student: {session.student}
                    </div>
                </div>
            </div>

            {/* Join Session Button */}
            <div className="flex-shrink-0">
                <Button
                    onClick={handleJoinSession}
                    disabled={joining}
                    className="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-lg font-medium transition-colors disabled:opacity-50"
                >
                    {joining ? 'Joining...' : 'Join Session'}
                </Button>
            </div>
        </div>
    );
}
