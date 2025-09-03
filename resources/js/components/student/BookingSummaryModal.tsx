/**
 * 🎨 FIGMA REFERENCE
 * Booking Summary Modal
 * 
 * EXACT SPECS FROM IMAGE:
 * - Large title "Booking Summary" at top left
 * - Teacher and subject info with book image (Quran on wooden surface)
 * - Date & Time with calendar icon and green rounded boxes
 * - Total Fee with money bag icon and green rounded box showing "$20 / ₦15,000"
 * - Notes section with light green text
 * - Red cancellation policy note
 * - "Go Back" and "Confirm & Pay" buttons at bottom
 */

import React from 'react';
import { Button } from '@/components/ui/button';
import { Calendar, DollarSign } from 'lucide-react';

interface Teacher {
    id: number;
    name: string;
    avatar?: string;
}

interface Subject {
    id: number;
    name: string;
}

interface BookingSummaryModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirmPayment: () => void;
    teacher: Teacher;
    subject: Subject;
    date: string;
    time: string;
    totalFee: number;
    currency: string;
    notes?: string;
    isProcessing?: boolean;
}

export default function BookingSummaryModal({
    isOpen,
    onClose,
    onConfirmPayment,
    teacher,
    subject,
    date,
    time,
    totalFee,
    currency = '₦',
    notes,
    isProcessing = false
}: BookingSummaryModalProps) {
    if (!isOpen) return null;

    const formatAmount = (amount: number): string => {
        return amount.toLocaleString();
    };

    const getTeacherInitials = (name: string): string => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-4xl p-6 max-w-xl w-full shadow-2xl">
                {/* Header */}
                <div className="mb-6">
                    <h2 className="text-2xl font-bold text-gray-900">
                        Booking Summary
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
                            <span className="font-medium text-gray-900">{teacher.name}</span>
                        </div>

                        {/* Subject */}
                        <div>
                            <span className="text-gray-500 text-sm">Subject </span>
                            <span className="font-medium text-gray-900">{subject.name}</span>
                        </div>

                        {/* Date & Time */}
                        <div className="flex items-center gap-2">
                            <Calendar className="w-4 h-4 text-gray-500" />
                            <span className="text-gray-500 text-sm">Date & Time:</span>
                            <div className="flex items-center gap-2 bg-[#FFF9E9]">
                                <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs">
                                    {date}
                                </div>
                                <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs">
                                    {time}
                                </div>
                            </div>
                        </div>

                        {/* Total Fee */}
                        <div className="flex items-center gap-2">
                            <DollarSign className="w-4 h-4 text-amber-500" />
                            <span className="text-gray-500 text-sm">Total Fee:</span>
                            <div className="bg-[#FFF9E9] text-[#338078] px-2 py-1 rounded text-xs font-medium">
                                <span className="font-bold">${Math.round(totalFee / 750)}</span> / {currency}{formatAmount(totalFee)}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Bottom Section */}
                <div className="space-y-4">
                    {/* Notes - Left Aligned */}
                    {notes && (
                        <div className="text-left">
                            <p className="text-sm">
                                <span className="text-green-600">Notes from you: </span>
                                <span className="text-gray-700">{notes}</span>
                            </p>
                        </div>
                    )}

                    {/* Cancellation Policy - Middle Aligned */}
                    <div className="text-center">
                        <p className="text-sm">
                            <span className="text-red-600 font-medium">Note: </span>
                            <span className="text-red-600">Cancellation allowed 12 hours before session.</span>
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
                            onClick={onConfirmPayment}
                            className="px-6 py-2 bg-[#338078] hover:bg-[#236158] text-white rounded-full"
                            disabled={isProcessing}
                            size="lg"
                        >
                            {isProcessing ? 'Processing...' : 'Confirm & Pay'}
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
