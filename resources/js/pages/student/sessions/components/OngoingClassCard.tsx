/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=387-22195&t=O1w7ozri9pYud8IO-0
 * Export: Ongoing class card component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Shows "Give Feedback" as plain text (not in a button)
 * - Has video button for joining the ongoing session
 * - Has chat button
 * - Progress bar with percentage (e.g., 70% with orange bar)
 */
import React from 'react';
import { MessageCircle, Video } from 'lucide-react';
import MessageUserIcon from '@/components/icons/message-user-icon';

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

interface OngoingClassCardProps {
    session: SessionListItem;
    getSubjectIcon: (subject: string) => string;
    getProgressColor: (progress: number) => string;
    renderStars: (rating: number) => React.ReactNode;
}

export default function OngoingClassCard({ 
    session, 
    getSubjectIcon, 
    getProgressColor, 
    renderStars 
}: OngoingClassCardProps) {
    return (
        <div className="bg-white rounded-2xl p-6 shadow-sm border border-gray-100">
            <div className="flex items-center justify-between">
                {/* Left Section - Subject Icon and Info (Vertical Layout) */}
                <div className="flex flex-col items-start space-y-3 w-32">
                    {/* Subject Image */}
                    <div className="w-20 h-20 rounded-2xl bg-gray-100 flex items-center justify-center overflow-hidden">
                        {session.imageUrl || getSubjectIcon(session.subject) ? (
                            <img 
                                src={session.imageUrl || getSubjectIcon(session.subject)} 
                                alt={session.title}
                                className="w-16 h-16 object-cover rounded-2xl"
                            />
                        ) : (
                            <div className="w-16 h-16 bg-[#2C7870] rounded-2xl flex items-center justify-center">
                                <span className="text-white font-bold text-lg">
                                    {session.subject?.substring(0, 2).toUpperCase() || 'SU'}
                                </span>
                            </div>
                        )}
                    </div>
                    
                    {/* Session Title */}
                    <h3 className="text-lg font-semibold text-gray-900 text-center">
                        {session.title}
                    </h3>
                    
                    {/* Teacher Info */}
                    <div className="text-center">
                        <div className="flex items-center justify-center space-x-1 text-sm text-gray-600">
                            <span className="text-gray-500">👤</span>
                            <span className="text-gray-500">Teacher:</span>
                        </div>
                        <div className="text-sm font-medium text-gray-800 mt-1">
                            {session.teacher}
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
                        
                        {/* Progress Bar - Shows actual progress percentage */}
                        <div className="w-full bg-gray-200 rounded-full h-2 mb-2 relative">
                            <div 
                                className="h-2 rounded-full transition-all duration-300"
                                style={{ 
                                    width: `${session.progress || 0}%`,
                                    backgroundColor: getProgressColor(session.progress || 0)
                                }}
                            ></div>
                            <span className="absolute -top-6 right-0 text-xs bg-orange-100 text-orange-800 px-2 py-1 rounded-full">
                                {session.progress || 0}%
                            </span>
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

                {/* Right Section - Action Buttons for Ongoing Class */}
                <div className="flex items-center space-x-3">
                    {/* Give Feedback - Plain text */}
                    <span className="text-[#2C7870] text-sm font-medium">
                        Give Feedback
                    </span>
                    
                    {/* Video button for joining ongoing session */}
                    {session.meeting_link && (
                        <button 
                            onClick={() => window.open(session.meeting_link, '_blank')}
                            className="flex items-center space-x-2 px-4 py-2 bg-[#2C7870] text-white rounded-lg hover:bg-[#236158] transition-colors"
                        >
                            <Video className="w-4 h-4" />
                        </button>
                    )}

                    {/* Chat button */}
                    <button className="flex items-center space-x-2 px-4 py-2 bg-[#2C7870] text-white rounded-lg hover:bg-[#236158] transition-colors">
                        <MessageUserIcon className="w-4 h-4" />
                    </button>
                </div>
            </div>
        </div>
    );
}
