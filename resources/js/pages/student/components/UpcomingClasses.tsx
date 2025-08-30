/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=388-24636&t=O1w7ozri9pYud8IO-0
 * Export: Upcoming classes component with exact design specifications
 * 
 * EXACT SPECS FROM FIGMA:
 * - Card layout with subject icons and teacher information
 * - Status badges and action buttons positioning
 * - Typography and spacing as per design system
 * - Color scheme matching the design tokens
 */
import React from 'react';
import { Calendar, Video } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { type UpcomingSession } from '@/types';

interface UpcomingClassesProps {
    classes: UpcomingSession[];
}

export default function UpcomingClasses({ classes }: UpcomingClassesProps) {
    return (
        <div className="rounded-[28px] bg-white shadow-sm border border-gray-100 p-6 md:p-8 max-w-6xl mx-auto">
            {/* Header */}
            <div className="flex items-center justify-between mb-6">
                <h3 className="text-2xl font-semibold text-gray-900">Upcoming Class</h3>
                <Link href="/student/sessions" className="text-[#2c7870] hover:text-[#236158] font-medium">View All Class</Link>
            </div>

            {classes.length > 0 ? (
                <div className="space-y-6">
                    {classes.map((cls) => (
                        <div key={cls.id} className="group relative">
                            <div className="flex items-center gap-4 p-4 rounded-2xl bg-gray-50 hover:bg-gray-100 transition-colors">
                                {/* thumbnail */}
                                <img src={cls.imageUrl} alt="class" className="w-20 h-20 rounded-2xl object-cover flex-shrink-0" />

                                <div className="flex-1 min-w-0">
                                    <div className="text-lg font-semibold text-gray-900 mb-1 group-hover:text-[#2c7870] transition-colors">
                                        {cls.title}
                                    </div>
                                    <div className="text-sm text-gray-500 mb-2">By {cls.teacher}</div>
                                    
                                    <div className="flex items-center gap-3 text-sm">
                                        <span className="text-[#2c7870] font-medium">
                                            {cls.date} | {cls.time}
                                        </span>
                                        <span className={`rounded-full px-3 py-1 text-xs font-medium ${
                                            cls.status === 'Confirmed' ? 'bg-teal-50 text-teal-700' : 
                                            cls.status === 'Completed' ? 'bg-gray-50 text-gray-700' : 
                                            cls.status === 'Scheduled' ? 'bg-blue-50 text-blue-700' :
                                            'bg-amber-50 text-amber-700'
                                        }`}>
                                            {cls.status}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Action Button - positioned absolutely to avoid link conflicts */}
                            {cls.meetingUrl && (cls.status === 'Confirmed' || cls.status === 'Scheduled') && (
                                <button 
                                    className="absolute right-6 top-1/2 transform -translate-y-1/2 bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-4 flex items-center gap-2 text-sm font-medium transition-colors z-10"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        window.open(cls.meetingUrl, '_blank');
                                    }}
                                >
                                    <Video className="w-4 h-4" />
                                    Join
                                </button>
                            )}
                        </div>
                    ))}
                </div>
            ) : (
                <div className="text-center py-12">
                    <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                        <Calendar className="w-12 h-12 text-gray-400" />
                    </div>
                    <h4 className="text-lg font-medium text-gray-900 mb-2">No Classes Scheduled</h4>
                    <p className="text-gray-500 mb-4">You don't have any scheduled classes at the moment.</p>
                    <button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-6">
                        Find Teachers
                    </button>
                </div>
            )}
        </div>
    );
}
