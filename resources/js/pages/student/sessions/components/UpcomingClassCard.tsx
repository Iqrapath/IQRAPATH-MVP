/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=388-24636&t=O1w7ozri9pYud8IO-0
 * Export: Upcoming class card component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Shows "Start Class" as a button (actionable)
 * - Has video button for joining when it starts
 * - Has chat button
 * - Shows "Confirmed" or "Pending" status badges
 * - No progress bar (class hasn't started yet)
 */
import React from 'react';
import { MessageCircle, Video } from 'lucide-react';

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

interface UpcomingClassCardProps {
    session: SessionListItem;
    getSubjectIcon: (subject: string) => string;
    getProgressColor: (progress: number) => string;
    renderStars: (rating: number) => React.ReactNode;
}

export default function UpcomingClassCard({
    session,
    getSubjectIcon,
    getProgressColor,
    renderStars
}: UpcomingClassCardProps) {
    const getStatusBadgeColor = (status: string) => {
        switch (status?.toLowerCase()) {
            case 'confirmed':
                return 'bg-teal-200 text-white';
            case 'pending':
                return 'bg-yellow-200 text-white';
            case 'scheduled':
                return 'bg-blue-200 text-white';
            default:
                return 'bg-gray-200 text-white';
        }
    };

    return (
        <div className="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
            {/* Left Section - Subject Image */}
            <div className="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                {session.imageUrl || getSubjectIcon(session.subject) ? (
                    <img
                        src={session.imageUrl || getSubjectIcon(session.subject)}
                        alt={session.title}
                        className="w-14 h-14 object-cover rounded-xl"
                    />
                ) : (
                    <div className="w-14 h-14 bg-[#2C7870] rounded-xl flex items-center justify-center">
                        <span className="text-white font-bold text-sm">
                            {session.subject?.substring(0, 2).toUpperCase() || 'SU'}
                        </span>
                    </div>
                )}
            </div>

            {/* Center Section - Class Details */}
            <div className="flex-1 px-4">
                <div className="space-y-1">
                    {/* Class Title */}
                    <h3 className="text-base font-semibold text-gray-900 leading-tight">
                        {session.title}
                    </h3>

                    {/* Teacher Info */}
                    <div className="text-sm text-gray-600">
                        By {session.teacher}
                    </div>

                    {/* Schedule with Date and Time */}
                    <div className="flex items-center space-x-2 text-sm mt-2">
                        <div className="flex items-center space-x-2 bg-[#FFF9E9] p-1">
                            <span className="text-[#338078] font-medium">{session.date}</span>
                            <span className="text-gray-400">|</span>
                            <span className="text-[#338078] font-medium">{session.time}</span>
                        </div>
                        <span className={`inline-block rounded-full px-3 py-1 text-xs font-medium ${getStatusBadgeColor(session.status)}`}>
                            {session.status}
                        </span>
                    </div>
                </div>
            </div>

            {/* Right Section - Status Badge and Action Button */}
            <div className="flex flex-col items-end space-y-2">
                {/* Status Badge */}

                {/* Start Class Button */}
                <button className="px-4 py-2 bg-[#2C7870] text-white rounded-full hover:bg-[#236158] transition-colors text-sm font-medium">
                    Start Class
                </button>
            </div>
        </div>
    );
}
