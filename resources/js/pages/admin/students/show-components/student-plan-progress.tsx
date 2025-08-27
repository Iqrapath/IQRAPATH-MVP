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
}

interface Props {
    student: Student;
}

export default function StudentPlanProgress({ student }: Props) {
    const getProgressPercentage = () => {
        if (student.stats?.completed_sessions !== undefined &&
            student.stats?.total_sessions !== undefined &&
            student.stats.total_sessions > 0) {
            return Math.round((student.stats.completed_sessions / student.stats.total_sessions) * 100);
        }
        return 0; // No progress if no data available
    };

    const hasProgressData = student.stats &&
        student.stats.completed_sessions !== undefined &&
        student.stats.total_sessions !== undefined;

    const progressPercentage = getProgressPercentage();
    const circumference = 2 * Math.PI * 40; // radius = 40
    const strokeDasharray = circumference;
    const strokeDashoffset = circumference - (progressPercentage / 100) * circumference;

    return (
        <div className="bg-white rounded-xl shadow-sm p-6">
            <div className="flex items-center">
                {/* Title - Far Left */}
                <div className="flex-1">
                    <h3 className="text-lg font-bold text-gray-800">Student Plan Progress</h3>
                </div>

                {/* Progress Circle - Center with significant spacing */}
                <div className="flex-2 flex justify-start">
                    <div className="relative w-32 h-32">
                        <svg className="w-32 h-32 transform -rotate-90" viewBox="0 0 100 100">
                            {/* Background circle - light beige/off-white track */}
                            <circle
                                cx="50"
                                cy="50"
                                r="40"
                                stroke="#F3E5C3"
                                strokeWidth="12"
                                fill="transparent"
                            />
                            {/* Progress circle - thick teal-green arc with rounded ends */}
                            <circle
                                cx="50"
                                cy="50"
                                r="40"
                                stroke="#0d9488"
                                strokeWidth="8"
                                fill="transparent"
                                strokeDasharray={strokeDasharray}
                                strokeDashoffset={strokeDashoffset}
                                strokeLinecap="round"
                                className="transition-all duration-500 ease-out drop-shadow-sm"
                                style={{
                                    filter: 'drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1))'
                                }}
                            />
                        </svg>
                        {/* Percentage text - centered in white inner circle */}
                        <div className="absolute inset-0 flex items-center justify-center">
                            <div className="text-center">
                                {hasProgressData ? (
                                    <span className="text-2xl font-bold text-gray-800 block leading-none">{progressPercentage}%</span>
                                ) : (
                                    <span className="text-sm font-medium text-gray-500 block leading-none">No Data</span>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                                 {/* Generate Certificates Action - Far Right */}
                 <div className="flex justify-end">
                     {progressPercentage === 100 ? (
                         <span className="text-teal-600 hover:text-teal-700 cursor-pointer font-medium text-base">
                             Generate Certificates
                         </span>
                     ) : (
                         <span className="text-gray-400 text-base" data-tip="Complete 100% to Generate Certificates">
                             Complete 100% to Generate Certificates
                         </span>
                     )}
                 </div>
            </div>
        </div>
    );
}
