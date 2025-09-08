/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Success Modal
 * 
 * EXACT SPECS FROM FIGMA:
 * - Success emoji and title
 * - Success message with teacher and reschedule details
 * - Information about pending approval
 * - Single action button "Got It, JazakaAllahu Khair!"
 */

import React from 'react';
import { Button } from '@/components/ui/button';

interface Teacher {
    id: number;
    name: string;
}

interface RescheduleSuccessModalProps {
    isOpen: boolean;
    onClose: () => void;
    teacher: Teacher;
    currentDate: string;
    currentTime: string;
    newDate: string;
    newTime: string;
}

export default function RescheduleSuccessModal({
    isOpen,
    onClose,
    teacher,
    currentDate,
    currentTime,
    newDate,
    newTime
}: RescheduleSuccessModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-16 max-w-xl w-full shadow-2xl text-center">
                {/* Success Header */}
                <div className="mb-10">
                    <div className="text-4xl mb-8">✅</div>
                    <h2 className="text-2xl font-bold text-gray-900 mb-2">
                        Reschedule Request Submitted!
                    </h2>
                </div>

                {/* Success Message */}
                <div className="mb-10">
                    <p className="text-gray-600 text-base leading-relaxed">
                        Your reschedule request has been sent to{' '}
                        <span className="font-semibold text-teal-600">{teacher.name}</span>.
                    </p>
                    <div className="mt-4 p-4 bg-gray-50 rounded-xl">
                        <p className="text-sm text-gray-500 mb-2">Current Session:</p>
                        <p className="text-gray-700 font-medium">{currentDate}, {currentTime}</p>
                        <p className="text-sm text-gray-500 mt-3 mb-2">Requested New Time:</p>
                        <p className="text-gray-700 font-medium">{newDate}, {newTime}</p>
                    </div>
                    <p className="text-gray-600 text-base leading-relaxed mt-4">
                        You will be notified once the teacher responds to your request.
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
