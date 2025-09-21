/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=388-24636&t=O1w7ozri9pYud8IO-0
 * Export: Upcoming classes component with exact design specifications
 * 
 * EXACT SPECS FROM FIGMA:
 * - Card layout with subject icons and teacher information
 * - Status badges and action buttons positioning
 * - Typography and spacing as per design system
 * - Color scheme matching the design tokens
 */
import React from 'react';
import { Calendar } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type UpcomingSession } from '@/types';
import UpcomingClassCard from '../sessions/components/UpcomingClassCard';

interface UpcomingClassesProps {
    classes: UpcomingSession[];
}

export default function UpcomingClasses({ classes }: UpcomingClassesProps) {
    // Helper functions for UpcomingClassCard
    const getSubjectIcon = (subject: string): string => {
        // Return empty string to force initials display instead of images
        return '';
    };

    const getProgressColor = (progress: number = 0) => {
        if (progress >= 80) return '#10B981'; // Green
        if (progress >= 60) return '#F59E0B'; // Yellow
        return '#EF4444'; // Red
    };

    const renderStars = (rating: number = 0) => {
        return Array.from({ length: 5 }, (_, i) => (
            <span key={i} className={i < rating ? 'text-yellow-400' : 'text-gray-300'}>
                ★
            </span>
        ));
    };
    return (
        <div className="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            {/* Header */}
            <div className="flex items-center justify-between mb-3">
                <h2 className="text-lg font-bold text-gray-900">Upcoming Class</h2>
                <Link href="/student/sessions?filter=upcoming" className="text-[#2C7870] hover:underline text-sm">
                    View All Class
                </Link>
            </div>

            {classes.length > 0 ? (
                <>
                    {classes.map((cls) => {
                        // Use service data directly - it already has the correct format
                        const sessionData = {
                            id: cls.id,
                            session_uuid: cls.session_uuid || cls.id.toString(),
                            title: cls.title,
                            teacher: cls.teacher,
                            teacher_avatar: cls.teacher_avatar || '',
                            subject: cls.subject || cls.title,
                            date: cls.date,
                            time: cls.time,
                            duration: cls.duration || 60,
                            status: cls.status,
                            meeting_link: cls.meeting_link || cls.meetingUrl,
                            completion_date: cls.completion_date,
                            progress: cls.progress || 0,
                            rating: cls.rating || 0,
                            imageUrl: cls.imageUrl || undefined
                        };

                        return (
                            <UpcomingClassCard
                                key={cls.id}
                                session={sessionData}
                                getSubjectIcon={getSubjectIcon}
                                getProgressColor={getProgressColor}
                                renderStars={renderStars}
                            />
                        );
                    })}
                </>
            ) : (
                <div className="text-center py-12">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <Calendar className="w-12 h-12 text-gray-400" />
                    </div>
                    <h4 className="text-lg font-medium text-gray-900 mb-2">No Classes Scheduled</h4>
                    <p className="text-gray-500 mb-4">You don't have any scheduled classes at the moment.</p>
                    <Link href="/student/browse-teachers">
                        <button className="bg-[#2C7870] hover:bg-[#236158] text-white rounded-full py-2 px-6">
                            Find Teachers
                        </button>
                    </Link>
                </div>
            )}
        </div>
    );
}
