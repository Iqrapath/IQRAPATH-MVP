/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Summary Modal
 * 
 * EXACT SPECS FROM IMAGE:
 * - Large title "Reschedule Summary" at top left
 * - Teacher and subject info with book image (Quran on wooden surface)
 * - Current vs New Date & Time with calendar icon and green rounded boxes
 * - Total Fee with money bag icon and green rounded box showing "$20 / ₦15,000"
 * - Reschedule reason section with light green text
 * - Red reschedule policy note
 * - "Go Back" and "Request Reschedule" buttons at bottom
 */

import React from 'react';
import { Button } from '@/components/ui/button';
import { MoneyIcon } from '../icons/money-icon';
import { DateTimeIcon } from '../icons/date-time-icon';

interface Teacher {
    id: number;
    name: string;
    avatar?: string;
}

interface Subject {
    id?: number;
    name: string;
}

interface Booking {
    id: number;
    booking_date?: string;
    start_time?: string;
    end_time?: string;
    duration_minutes?: number;
    teacher: Teacher | string;
    subject: Subject | string | { name: string };
}

interface RescheduleSummaryModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirmReschedule: () => void;
    booking: Booking;
    newDate: string;
    newTime: string;
    newDuration: number;
    totalFee: number;
    currency: string;
    rescheduleReason: string;
    isProcessing?: boolean;
}

export default function RescheduleSummaryModal({
    isOpen,
    onClose,
    onConfirmReschedule,
    booking,
    newDate,
    newTime,
    newDuration,
    totalFee,
    currency = '₦',
    rescheduleReason,
    isProcessing = false
}: RescheduleSummaryModalProps) {
    if (!isOpen) return null;

    const formatAmount = (amount: number): string => {
        return amount.toLocaleString();
    };

    const formatTime = (time: string): string => {
        const [hours, minutes] = time.split(':');
        const hour = parseInt(hours);
        const ampm = hour >= 12 ? 'PM' : 'AM';
        const displayHour = hour % 12 || 12;
        return `${displayHour}:${minutes} ${ampm}`;
    };

    const formatDate = (dateString: string): string => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    };

    const getTeacherInitials = (name: string): string => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    const currentDate = booking.booking_date ? formatDate(booking.booking_date) : 'Not set';
    const currentTime = booking.start_time ? formatTime(booking.start_time) : 'Not set';
    const newTimeFormatted = formatTime(newTime);

    // Handle flexible teacher and subject types
    const teacherName = typeof booking.teacher === 'string' ? booking.teacher : booking.teacher.name;
    const subjectName = typeof booking.subject === 'string' 
        ? booking.subject 
        : 'name' in booking.subject ? booking.subject.name : 'Unknown Subject';

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-4xl p-6 max-w-xl w-full shadow-2xl">
                {/* Header */}
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900">
                        Reschedule Summary
                    </h2>
                </div>

                {/* Upper Section - Image Left, Details Right */}
                <div className="flex items-start gap-4 mb-6">
                    {/* Subject Image */}
                    <div className="w-16 h-16 rounded-lg bg-gradient-to-br from-amber-100 to-amber-200 flex items-center justify-center flex-shrink-0">
                        <div className="text-2xl">📖</div>
                    </div>

                    {/* Right Side Details */}
                    <div className="flex-1 space-y-3">
                        {/* Teacher */}
                        <div>
                            <span className="text-gray-500 text-sm">Teacher: </span>
                            <span className="font-medium text-gray-900">{teacherName}</span>
                        </div>

                        {/* Subject */}
                        <div>
                            <span className="text-gray-500 text-sm">Subject: </span>
                            <span className="font-medium text-gray-900">{subjectName}</span>
                        </div>

                        {/* Current Date & Time */}
                        <div className="flex items-center gap-2">
                            <DateTimeIcon className="w-8 h-8 text-gray-500" />
                            <span className="text-gray-500 text-sm">Current:</span>
                            <div className="flex items-center gap-2 bg-gray-100">
                                <div className="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                    {currentDate}
                                </div>
                                <div className="bg-gray-100 text-gray-600 px-2 py-1 rounded text-xs">
                                    {currentTime}
                                </div>
                            </div>
                        </div>

                        {/* New Date & Time */}
                        <div className="flex items-center gap-2">
                            <DateTimeIcon className="w-8 h-8 text-green-500" />
                            <span className="text-gray-500 text-sm">New:</span>
                            <div className="flex items-center gap-2 bg-[#FFF9E9]">
                                <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs">
                                    {newDate}
                                </div>
                                <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs">
                                    {newTimeFormatted}
                                </div>
                            </div>
                        </div>

                        {/* Duration */}
                        <div className="flex items-center gap-2">
                            <span className="text-gray-500 text-sm">Duration:</span>
                            <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs font-medium">
                                {newDuration} minutes
                            </div>
                        </div>

                        {/* Total Fee */}
                        <div className="flex items-center gap-2">
                            <MoneyIcon className="w-8 h-8 text-amber-500" />
                            <span className="text-gray-500 text-sm">Total Fee:</span>
                            <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs font-medium">
                                <span className="font-bold">${Math.round(totalFee / 750)}</span> / {currency}{formatAmount(totalFee)}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Section */}
                <div className="space-y-4">
                    {/* Reschedule Reason - Left Aligned */}
                    {rescheduleReason && (
                        <div className="text-left">
                            <p className="text-sm">
                                <span className="text-green-600">Reschedule reason: </span>
                                <span className="text-gray-700">{rescheduleReason}</span>
                            </p>
                        </div>
                    )}

                    {/* Reschedule Policy - Middle Aligned */}
                    <div className="text-center">
                        <p className="text-sm">
                            <span className="text-red-600 font-medium">Note: </span>
                            <span className="text-red-600">Reschedule request will be sent to teacher for approval.</span>
                        </p>
                    </div>

                    {/* Action Buttons - Middle Aligned */}
                    <div className="flex gap-3 justify-center">
                        <Button
                            onClick={onClose}
                            variant="outline"
                            className="px-6 py-2 rounded-full border-[#338078] text-[#338078] hover:bg-green-50"
                            disabled={isProcessing}
                            size="lg"
                        >
                            Go Back
                        </Button>
                        <Button
                            onClick={onConfirmReschedule}
                            className="px-6 py-2 bg-[#338078] hover:bg-[#236158] text-white rounded-full"
                            disabled={isProcessing}
                            size="lg"
                        >
                            {isProcessing ? 'Processing...' : 'Request Reschedule'}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
