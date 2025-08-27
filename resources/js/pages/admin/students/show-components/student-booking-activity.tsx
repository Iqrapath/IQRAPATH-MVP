import React from 'react';

interface Student {
    id: number;
    role: string;
    stats?: {
        completed_sessions: number;
        total_sessions: number;
        attendance_percentage: number;
        missed_sessions: number;
        average_engagement: number;
    };
    // Additional booking data that would come from database
    upcoming_sessions?: Array<{
        date: string;
        time: string;
        teacher_name: string;
    }>;
    rescheduled_sessions?: number;
}

interface Props {
    student: Student;
}

export default function StudentBookingActivity({ student }: Props) {
    // Helper functions to get data from database
    const getNextClassInfo = () => {
        if (student.upcoming_sessions && student.upcoming_sessions.length > 0) {
            const nextSession = student.upcoming_sessions[0];
            return `${nextSession.date}, ${nextSession.time} with ${nextSession.teacher_name}`;
        }
        return 'No upcoming sessions';
    };

    const getRescheduledCount = () => {
        return student.rescheduled_sessions || 0;
    };

    return (
        <div className="bg-white rounded-xl shadow-sm p-6">
            {/* Title */}
            <h3 className="text-lg font-bold text-gray-800 mb-4">Booking Activity</h3>

            {/* Table-like Layout */}
            <div className="space-y-3">
                {/* Headers */}
                <div className="flex border-b border-gray-200 pb-2">
                    <div className="flex-1">
                        <span className="font-medium text-gray-800">Category</span>
                    </div>
                    <div className="flex-1">
                        <span className="font-medium text-gray-800">Status</span>
                    </div>
                </div>

                {/* Past Sessions Row */}
                <div className="flex items-center py-2">
                    <div className="flex-1">
                        <span className="text-gray-700">Past Sessions</span>
                    </div>
                    <div className="flex-1">
                        <span className="text-gray-600">
                            {student.stats?.completed_sessions || 0} completed sessions
                        </span>
                    </div>
                </div>

                {/* Upcoming Sessions Row */}
                <div className="flex items-center py-2">
                    <div className="flex-1">
                        <span className="text-gray-700">Upcoming Sessions</span>
                    </div>
                    <div className="flex-1">
                        <span className="text-gray-600">
                            {student.upcoming_sessions && student.upcoming_sessions.length > 0 ?
                                `Next class: ${getNextClassInfo()}` :
                                'No upcoming sessions'
                            }
                        </span>
                    </div>
                </div>

                {/* Missed Sessions Row */}
                <div className="flex items-center py-2">
                    <div className="flex-1">
                        <span className="text-gray-700">Missed Sessions</span>
                    </div>
                    <div className="flex-1">
                        <span className="text-gray-600">
                            {student.stats?.missed_sessions || 0} missed, {getRescheduledCount()} rescheduled
                        </span>
                    </div>
                </div>

                {/* View Details Link - Positioned as shown in image */}
                <div className="flex justify-end pt-1">
                    <span className="text-teal-600 hover:text-teal-700 cursor-pointer font-medium text-sm">
                        View details →
                    </span>
                </div>
            </div>
        </div>
    );
}
