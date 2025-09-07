/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: OngoingClassCard
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=411-27985&t=m6ohX2RrycH79wFY-0
 * Export: Based on provided image - ongoing class cards with book icons and "In Progress" status
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Background: Clean white, no individual card borders (part of single card layout)
 * - Subject avatar: 64px × 64px, rounded corners, book icon on colored background
 * - Typography: Title 16px/semibold black, teacher 14px/regular gray with "Ustadh" prefix
 * - Date/Time: 14px/regular in format "18th March 2025 | 4:00 PM - 5:00 PM" with #FFF9E9 background
 * - Status badge: "In Progress" with teal background
 * - Action buttons: "Join Class" (teal bg), "Message Teacher" (teal outline)
 * - Spacing: Clean vertical spacing, buttons with proper gaps
 * - No progress bar visible in design
 * 
 * 📱 RESPONSIVE: Clean single column layout matching upcoming cards
 * 🎯 STATES: Status-specific styling with teal "In Progress" badge
 */
import React from 'react';
import { MessageCircleStudentIcon } from '@/components/icons/message-circle-student-icon';
import { router } from '@inertiajs/react';
import { BookingData } from '@/types';
import { BookingIcon } from '@/components/icons/booking-icon';

interface OngoingClassCardProps {
    booking: BookingData;
}

export default function OngoingClassCard({ booking }: OngoingClassCardProps) {
    const handleJoinSession = () => {
        if (booking.meetingUrl) {
            window.open(booking.meetingUrl, '_blank');
        }
    };

    const handleMessageTeacher = () => {
        router.visit(`/student/messages/teacher/${booking.teacher_id}`);
    };

    // Get subject-specific colors for the book icon
    const getSubjectColors = (title: string) => {
        const colors = [
            { bg: 'bg-amber-600', icon: 'text-amber-100' },
            { bg: 'bg-emerald-600', icon: 'text-emerald-100' },
            { bg: 'bg-blue-600', icon: 'text-blue-100' },
            { bg: 'bg-purple-600', icon: 'text-purple-100' },
            { bg: 'bg-rose-600', icon: 'text-rose-100' }
        ];
        
        // Use title hash to consistently assign colors
        const hash = title.split('').reduce((a, b) => {
            a = ((a << 5) - a) + b.charCodeAt(0);
            return a & a;
        }, 0);
        
        return colors[Math.abs(hash) % colors.length];
    };

    const subjectColors = getSubjectColors(booking.title);

    return (
        <div className="flex items-start gap-4">
            {/* Subject Book Icon */}
            <div className={`w-16 h-16 rounded-lg ${subjectColors.bg} flex items-center justify-center flex-shrink-0`}>
                <BookingIcon className={`w-8 h-8 ${subjectColors.icon}`} />
            </div>

            {/* Content */}
            <div className="flex-1 min-w-0">
                {/* Title and Teacher */}
                <h3 className="text-base font-semibold text-gray-900 mb-1">
                    {booking.title}
                </h3>
                <p className="text-sm text-gray-600 mb-3">
                    Ustadh {typeof booking.teacher === 'object' ? booking.teacher.name : booking.teacher}
                </p>

                {/* Date/Time and Status Badge on same line */}
                <div className="flex items-center gap-3 mb-6">
                    {/* Date and Time with Background */}
                    <div className="bg-[#FFF9E9] px-3 py-2 inline-block">
                        <p className="text-sm text-gray-600">
                            {booking.date} | {booking.time}
                        </p>
                    </div>
                    
                    {/* Status Badge */}
                    <span className="bg-[#E4FFFC] text-[#338078] px-3 py-1 text-xs font-medium rounded-full">
                        {booking.status}
                    </span>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center gap-3">
                    <button 
                        onClick={handleJoinSession}
                        className="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer"
                    >
                        Join Class
                    </button>

                    <button 
                        onClick={handleMessageTeacher}
                        className="border-b-2 border-teal-600 text-teal-600 hover:bg-teal-50 px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer flex items-center gap-2"
                    >
                        <MessageCircleStudentIcon className="w-4 h-4" />
                        Message Teacher
                    </button>
                </div>
            </div>
        </div>
    );
}
