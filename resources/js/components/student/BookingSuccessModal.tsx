/**
 * 🎨 FIGMA REFERENCE
 * Booking Success Modal
 * 
 * EXACT SPECS FROM FIGMA:
 * - Congratulations emoji and title
 * - Success message with teacher and session details
 * - Meeting link information
 * - Single action button "Got It, JazakaAllahu Khair!"
 */

import React from 'react';
import { Button } from '@/components/ui/button';

interface Teacher {
    id: number;
    name: string;
}

interface BookingSuccessModalProps {
    isOpen: boolean;
    onClose: () => void;
    teacher: Teacher;
    sessionDate: string;
    sessionTime: string;
}

export default function BookingSuccessModal({
    isOpen,
    onClose,
    teacher,
    sessionDate,
    sessionTime
}: BookingSuccessModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-16 max-w-xl w-full shadow-2xl text-center">
                {/* Congratulations Header */}
                <div className="mb-10">
                    <div className="text-4xl mb-8">🎉</div>
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">
                        Congratulations, Booking Successful!
                    </h2>
                </div>

                {/* Success Message */}
                <div className="mb-10">
                    <p className="text-gray-600 text-base leading-relaxed">
                        Your session with <span className="font-semibold text-teal-600">{teacher.name}</span> is scheduled for{' '}
                        <span className="font-semibold text-gray-900">{sessionDate}, {sessionTime}</span>.
                    </p>
                    <p className="text-gray-600 text-base leading-relaxed mt-3">
                        Meeting link will be sent before the session.
                    </p>
                </div>

                {/* Action Button */}
                <Button
                    onClick={onClose}
                    className="w-full bg-teal-600 hover:bg-teal-700 text-white py-4 rounded-full text-base font-medium"
                >
                    Got It, JazakaAllahu Khair!
                </Button>
            </div>
        </div>
    );
}
