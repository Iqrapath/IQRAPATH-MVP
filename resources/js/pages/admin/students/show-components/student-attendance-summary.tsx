import React from 'react';

interface AttendanceStats {
    sessions_attended: number;
    missed_classes: number;
    average_engagement: number;
}

interface Props {
    attendanceStats: AttendanceStats | null;
}

export default function StudentAttendanceSummary({ attendanceStats }: Props) {
    const handleEdit = () => {
        // Handle edit action
        console.log('Edit attendance summary clicked');
    };

    return (
        <div className="bg-white rounded-xl shadow-sm p-6">
            {/* Title */}
            <h3 className="text-lg font-bold text-gray-800 mb-6">Attendance Summary</h3>
            
            <div className="space-y-4 text-base">
                {/* Sessions Attended */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Sessions Attended:</span>
                    <span className="text-gray-600">
                        {attendanceStats?.sessions_attended || 0}
                    </span>
                </div>

                {/* Missed Classes */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Missed Classes:</span>
                    <span className="text-gray-600">
                        {attendanceStats?.missed_classes || 0}
                    </span>
                </div>

                {/* Average Engagement */}
                <div className="flex">
                    <span className="font-medium text-gray-700 w-40">Average Engagement:</span>
                    <span className="text-gray-600">
                        {attendanceStats?.average_engagement || 0}%
                    </span>
                </div>
            </div>

            {/* Edit Link - Positioned at bottom right */}
            <div className="flex justify-end mt-6">
                <button 
                    onClick={handleEdit}
                    className="text-gray-500 hover:text-gray-700 text-sm cursor-pointer"
                >
                    Edit
                </button>
            </div>
        </div>
    );
}
