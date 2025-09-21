/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=387-22195&t=O1w7ozri9pYud8IO-0
 * Export: Base session card component with common layout
 * 
 * EXACT SPECS FROM FIGMA:
 * - Three-section layout: Left (image), Center (details), Right (actions)
 * - Rounded corners, shadows, and proper spacing
 * - Progress bars with color coding
 * - Star ratings and review text
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

interface SessionCardProps {
    session: SessionListItem;
    getSubjectIcon: (subject: string) => string;
    getProgressColor: (progress: number) => string;
    renderStars: (rating: number) => React.ReactNode;
}

export default function SessionCard({ 
    session, 
    getSubjectIcon, 
    getProgressColor, 
    renderStars 
}: SessionCardProps) {
    return (
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
                {/* Left Section - Subject Icon and Info */}
                <div className="flex items-start space-x-4">
                    <div className="w-16 h-16 rounded-2xl bg-gray-100 flex items-center justify-center overflow-hidden">
                        <img 
                            src={session.imageUrl || getSubjectIcon(session.subject)} 
                            alt={session.title}
                            className="w-12 h-12 object-cover"
                        />
                    </div>
                    <div className="flex-1">
                        <h3 className="text-lg font-semibold text-gray-900 mb-1">
                            {session.title}
                        </h3>
                        <div className="flex items-center space-x-2 text-sm text-gray-600 mb-2">
                            <span className="w-2 h-2 bg-[#2C7870] rounded-full"></span>
                            <span>{session.teacher}</span>
                        </div>
                    </div>
                </div>

                {/* Center Section - Session Details */}
                <div className="flex-1 px-6">
                    <div className="space-y-2">
                        <div className="flex items-center space-x-2 text-sm">
                            <span className="text-red-500">📅</span>
                            <span className="font-medium">{session.date}</span>
                        </div>
                        <div className="flex items-center space-x-2 text-sm">
                            <span className="text-red-500">🕐</span>
                            <span className="font-medium">{session.time}</span>
                        </div>
                        <div className="text-sm text-gray-600">
                            Progress
                        </div>
                        
                        {/* Progress Bar */}
                        <div className="w-full bg-gray-200 rounded-full h-2 mb-2">
                            <div 
                                className="h-2 rounded-full transition-all duration-300"
                                style={{ 
                                    width: `${session.progress || 0}%`,
                                    backgroundColor: getProgressColor(session.progress || 0)
                                }}
                            ></div>
                        </div>

                        {/* Star Rating */}
                        <div className="flex items-center space-x-1">
                            {renderStars(session.rating || 0)}
                            <span className="text-sm text-gray-600 ml-2">
                                {session.rating || 0}/5
                            </span>
                        </div>

                        <div className="text-xs text-gray-500">
                            Your Review - Great lesson, very knowledgeable teacher!
                        </div>
                    </div>
                </div>

                {/* Right Section - Action Buttons (to be implemented by child components) */}
                <div className="flex items-center space-x-3">
                    {/* This will be overridden by child components */}
                </div>
            </div>
        </div>
    );
}
