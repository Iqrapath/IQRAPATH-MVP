/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: OngoingClassCard
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=411-27985&t=m6ohX2RrycH79wFY-0
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
import React, { useState } from 'react';
import { MessageCircleStudentIcon } from '@/components/icons/message-circle-student-icon';
import { router, usePage } from '@inertiajs/react';
import { BookingData, PageProps } from '@/types';
import { BookingIcon } from '@/components/icons/booking-icon';
import JoinClassModal from '@/components/student/JoinClassModal';
import { toast } from 'sonner';

interface OngoingClassCardProps {
    booking: BookingData;
}

export default function OngoingClassCard({ booking }: OngoingClassCardProps) {
    const [isJoinModalOpen, setIsJoinModalOpen] = useState(false);
    const { auth } = usePage<PageProps>().props;

    const handleJoinSession = () => {
        setIsJoinModalOpen(true);
    };

    const handleJoinNow = () => {
        if (booking.meetingUrl) {
            window.open(booking.meetingUrl, '_blank');
            toast.success('You are now in the class');
        }
        setIsJoinModalOpen(false);
    };

    const handleMessageTeacher = () => {
        toast.success(`Messaging ${typeof booking.teacher === 'object' ? booking.teacher.name : booking.teacher} coming soon!`);
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

    // Calculate time remaining based on actual session times
    const calculateTimeRemaining = () => {
        if (!booking.start_time || !booking.end_time) {
            return "Session in progress";
        }

        const now = new Date();
        const startTime = new Date(`${booking.booking_date || booking.date} ${booking.start_time}`);
        const endTime = new Date(`${booking.booking_date || booking.date} ${booking.end_time}`);
        
        if (now < startTime) {
            const diffMs = startTime.getTime() - now.getTime();
            const diffMinutes = Math.ceil(diffMs / (1000 * 60));
            return `Starts in ${diffMinutes} minutes`;
        } else if (now > endTime) {
            return "Session ended";
        } else {
            const diffMs = endTime.getTime() - now.getTime();
            const diffMinutes = Math.ceil(diffMs / (1000 * 60));
            return `${diffMinutes} minutes remaining`;
        }
    };

    // Get student name from auth context
    const getStudentName = () => {
        return auth.user?.name || "Student";
    };

    // Determine meeting platform from URL
    const getMeetingPlatform = () => {
        if (!booking.meetingUrl) return 'zoom';
        
        if (booking.meetingUrl.includes('zoom.us')) {
            return 'zoom';
        } else if (booking.meetingUrl.includes('meet.google.com')) {
            return 'google-meet';
        } else {
            return 'zoom'; // Default to zoom
        }
    };

    return (
        <>
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

            {/* Join Class Modal */}
            <JoinClassModal
                isOpen={isJoinModalOpen}
                onClose={() => setIsJoinModalOpen(false)}
                onJoinNow={handleJoinNow}
                studentName={getStudentName()}
                meetingPlatform={getMeetingPlatform()}
                classInfo={{
                    date: booking.date,
                    time: booking.time,
                    teacher: typeof booking.teacher === 'object' ? booking.teacher.name : booking.teacher,
                    subject: booking.title,
                    timeRemaining: calculateTimeRemaining(),
                    meetingLink: booking.meetingUrl || '#'
                }}
            />
        </>
    );
}
