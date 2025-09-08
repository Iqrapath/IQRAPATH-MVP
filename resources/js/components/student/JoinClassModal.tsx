/**
 * 🎨 FIGMA REFERENCE
 * Join Class Modal
 * 
 * EXACT SPECS FROM IMAGE:
 * - Google Meet logo at top
 * - Personalized greeting "Dear [Name], You are about to join the class"
 * - Class information card with rounded corners and shadow
 * - Green "Class Info" header
 * - Date/time, teacher, subject, time remaining, meeting link
 * - Two action buttons: "Join Now" (green) and "Go Back" (white with green border)
 */

import React from 'react';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';
import { ZoomIcon } from '../icons/zoom-icon';
import { GoogleMeetIcon } from '../icons/google-meet-icon';

interface JoinClassModalProps {
    isOpen: boolean;
    onClose: () => void;
    onJoinNow: () => void;
    studentName: string;
    meetingPlatform: 'zoom' | 'google-meet';
    classInfo: {
        date: string;
        time: string;
        teacher: string;
        subject: string;
        timeRemaining: string;
        meetingLink: string;
    };
}

export default function JoinClassModal({
    isOpen,
    onClose,
    onJoinNow,
    studentName,
    meetingPlatform,
    classInfo
}: JoinClassModalProps) {
    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-2xl p-8 max-w-xl w-full shadow-2xl text-center">
                {/* Close Button */}
                <button
                    onClick={onClose}
                    className="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors"
                >
                    <X className="w-5 h-5" />
                </button>

                {/* Meeting Platform Logo */}
                <div className="flex justify-center mb-6">
                    <div className="flex items-center gap-2">
                        {meetingPlatform === 'google-meet' ? (
                            <>
                                {/* Google Meet Logo */}
                                <div className="w-8 h-8 flex items-center justify-center">
                                    <GoogleMeetIcon className="w-5 h-5" />
                                </div>
                            </>
                        ) : (
                            <>
                                {/* Zoom Logo */}
                                <div className="">
                                    <ZoomIcon className="w-20 h-20" />
                                </div>
                            </>
                        )}
                    </div>
                </div>

                {/* Greeting */}
                <div className="text-center mb-8">
                    <h2 className="text-xl font-semibold text-gray-800">
                        Dear {studentName}, You are about to join the class
                    </h2>
                </div>

                {/* Class Information Card */}
                <div className="bg-gray-50 rounded-xl p-6 shadow-inner mb-8 text-left">
                    {/* Class Info Header */}
                    <div className="text-center mb-4">
                        <h3 className="text-lg font-semibold text-[#338078]">Class Info</h3>
                    </div>

                    {/* Date and Time */}
                    <div className="text-center mb-4">
                        <p className="text-sm text-gray-700">
                            {classInfo.date} | {classInfo.time}
                        </p>
                    </div>

                    {/* Class Information Section */}
                    <div className="text-center items-center space-y-3">
                        <h4 className="text-base font-semibold text-gray-800 mb-4">Class Information Section</h4>
                        <div className="text-start space-y-3">
                            {/* Teacher */}
                            <div className="gap-3">
                                <span className="text-sm text-gray-700">👤: </span>
                                <span className="text-sm text-gray-700">
                                    Teacher: {classInfo.teacher}
                                </span>
                            </div>

                            {/* Subject */}
                            <div className="gap-3">
                                <span className="text-sm text-gray-700">📖: </span>
                                <span className="text-sm text-gray-700">
                                    Subject: {classInfo.subject}
                                </span>
                            </div>

                            {/* Time Remaining */}
                            <div className="mb-3 gap-3">
                                <span className="text-sm text-gray-700">Time Remaining: </span>
                                <div className="text-sm font-medium text-gray-800">
                                    {classInfo.timeRemaining}
                                </div>
                            </div>

                            {/* Meeting Link */}
                            <div className="gap-3">
                                <div className="text-sm text-gray-700">
                                <span className="text-sm text-gray-700">📍: </span>
                                    Meeting Link:
                                    <button
                                        onClick={onJoinNow}
                                        className="text-green-600 hover:text-green-700 font-medium underline ml-1"
                                    >
                                        Click to Join {meetingPlatform === 'google-meet' ? 'Google Meet' : 'Zoom'}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-3 justify-center">
                    <Button
                        onClick={onJoinNow}
                        className="bg-[#338078] hover:bg-[#236158] text-white py-3 px-6 rounded-full font-medium"
                    >
                        Join Now
                    </Button>
                    <Button
                        onClick={onClose}
                        variant="outline"
                        className="border-[#338078] text-[#338078] hover:bg-green-50 py-3 px-6 rounded-full font-medium"
                    >
                        Go Back
                    </Button>
                </div>
            </div>
        </div>
    );
}
