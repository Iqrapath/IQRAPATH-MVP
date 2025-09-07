/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: CompletedClassCard
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=412-28472&t=m6ohX2RrycH79wFY-0
 * Export: Based on provided image - completed class cards with book icons and "Completed" status
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Background: Clean white, no individual card borders (part of single card layout)
 * - Subject avatar: 64px × 64px, rounded corners, book icon on colored background
 * - Typography: Title 16px/semibold black, teacher 14px/regular gray with "Ustadh" prefix
 * - Date/Time: 14px/regular in format "15th March 2025 | 5:00 PM - 6:00 PM" with #FFF9E9 background
 * - Status badge: "Completed" with specific completed status colors beside date/time
 * - Action buttons: "View Summary" (teal bg), "Rebook" (teal outline), "Rate Teacher" (text link)
 * - Spacing: Clean vertical spacing, buttons with proper gaps
 * - No rating stars section visible in design
 * 
 * 📱 RESPONSIVE: Clean single column layout matching other card types
 * 🎯 STATES: Completed-specific styling with appropriate status badge
 */
import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { BookingData } from '@/types';
import { BookingIcon } from '@/components/icons/booking-icon';
import ViewSummaryModal from '@/components/student/ViewSummaryModal';
import RateTeacherDialog from '@/components/student/RateTeacherDialog';

interface CompletedClassCardProps {
    booking: BookingData;
}

export default function CompletedClassCard({ booking }: CompletedClassCardProps) {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [isRateDialogOpen, setIsRateDialogOpen] = useState(false);

    const handleViewSummary = () => {
        setIsModalOpen(true);
    };

    const handleRebook = () => {
        router.visit(`/student/browse-teachers/${booking.teacher_id}?rebook=${booking.id}`);
    };

    const handleRateTeacher = () => {
        setIsRateDialogOpen(true);
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
                        Ustadh {typeof booking.teacher === 'object' && booking.teacher?.name ? booking.teacher.name : typeof booking.teacher === 'string' ? booking.teacher : 'Teacher Name'}
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
                            onClick={handleViewSummary}
                            className="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            View Summary
                        </button>

                        <button 
                            onClick={handleRebook}
                            className="border border-teal-600 text-teal-600 hover:bg-teal-50 px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            Rebook
                        </button>

                        <button 
                            onClick={handleRateTeacher}
                            className="text-teal-600 hover:text-teal-700 text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            Rate Teacher
                        </button>
                    </div>
                </div>
            </div>

            {/* View Summary Modal */}
            <ViewSummaryModal 
                booking={booking}
                isOpen={isModalOpen}
                onClose={() => setIsModalOpen(false)}
            />

            {/* Rate Teacher Dialog */}
            <RateTeacherDialog
                booking={booking}
                isOpen={isRateDialogOpen}
                onClose={() => setIsRateDialogOpen(false)}
            />
        </>
    );
}
