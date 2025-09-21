/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: UpcomingClassCard
 * Figma URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=409-26933&t=m6ohX2RrycH79wFY-0
 * Export: Based on provided image - upcoming class cards with book icons
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Background: #FFFFFF 
 * - No border visible in design
 * - Padding: 24px
 * - Subject avatar: 64px × 64px, rounded corners, book icon on colored background
 * - Typography: Title 16px/semibold black, teacher 14px/regular gray
 * - Date/Time: 14px/regular in format "20th March 2025 | 6:00 PM - 8:00 PM"
 * - Action buttons: "View Details" (teal bg), "Reschedule" (teal outline), "Cancel Booking" (text)
 * - Spacing: Clean vertical spacing, buttons with proper gaps
 * - No status badges visible in the design
 * 
 * 📱 RESPONSIVE: Clean single column layout
 * 🎯 STATES: Clean design with button hover states
 */
import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';
import { BookingData } from '@/types';
import { BookingIcon } from '@/components/icons/booking-icon';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface UpcomingClassCardProps {
    booking: BookingData;
}

export default function UpcomingClassCard({ booking }: UpcomingClassCardProps) {
    const [isCancelDialogOpen, setIsCancelDialogOpen] = useState(false);
    const handleViewDetails = () => {
        router.visit(`/student/my-bookings/${booking.id}`);
    };

    const handleReschedule = () => {
        router.visit('/student/reschedule/class', {
            method: 'post',
            data: {
                booking_id: booking.id,
                teacher_id: typeof booking.teacher === 'object' ? booking.teacher.id : booking.teacher_id
            }
        });
    };

    const handleCancel = () => {
        setIsCancelDialogOpen(true);
    };

    const confirmCancelBooking = () => {
        router.post(`/student/my-bookings/${booking.id}/cancel`, {}, {
            onSuccess: () => {
                toast.success('Booking cancelled successfully');
                setIsCancelDialogOpen(false);
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                } else {
                    toast.error('Failed to cancel booking');
                }
            }
        });
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

                    {/* Date and Time with Background */}
                    <div className="bg-[#FFF9E9] px-3 py-2 mb-6 inline-block">
                        <p className="text-sm text-gray-600">
                            {booking.date} | {booking.time}
                        </p>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-3">
                        <button 
                            onClick={handleViewDetails}
                            className="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            View Details
                        </button>

                        <button 
                            onClick={handleReschedule}
                            className="border border-teal-600 text-teal-600 hover:bg-teal-50 px-4 py-2 rounded-full text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            Reschedule
                        </button>

                        <button 
                            onClick={handleCancel}
                            className="text-teal-600 hover:text-teal-700 text-sm font-medium transition-colors duration-200 cursor-pointer"
                        >
                            Cancel Booking
                        </button>
                    </div>
                </div>

            {/* Cancel Booking Confirmation Dialog */}
            <AlertDialog open={isCancelDialogOpen} onOpenChange={setIsCancelDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancel Booking</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to cancel this booking? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Keep Booking</AlertDialogCancel>
                        <AlertDialogAction 
                            onClick={confirmCancelBooking}
                            className="bg-red-600 hover:bg-red-700"
                        >
                            Cancel Booking
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </div>
    );
}
