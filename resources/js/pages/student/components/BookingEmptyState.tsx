/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: BookingEmptyState
 * Figma URL: Based on empty state design patterns in the my-bookings page
 * Export: .cursor/design-references/student/my-bookings/empty-state.png
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Background: Transparent
 * - Icon circle: 96px × 96px, #F3F4F6 background
 * - Icon: 48px calendar icon, #9CA3AF color
 * - Typography: Title 18px/medium, description 16px/regular
 * - Button: Primary teal styling, 16px padding, rounded-full
 * - Text alignment: Center
 * - Spacing: 24px between elements
 * 
 * 📱 RESPONSIVE: Consistent across all screen sizes
 * 🎯 STATES: Default, button hover state
 */
import React from 'react';
import { Link } from '@inertiajs/react';
import { Calendar, BookOpen, CheckCircle } from 'lucide-react';
import { TabType } from './BookingTabNavigation';

interface BookingEmptyStateProps {
    type: TabType;
}

export default function BookingEmptyState({ type }: BookingEmptyStateProps) {
    const getEmptyStateContent = () => {
        switch (type) {
            case 'upcoming':
                return {
                    icon: Calendar,
                    title: 'No Upcoming Classes',
                    description: "You don't have any upcoming classes scheduled. Book a session with one of our qualified teachers to start your learning journey.",
                    actionText: 'Book a Class',
                    actionLink: '/student/browse-teachers'
                };
            case 'ongoing':
                return {
                    icon: BookOpen,
                    title: 'No Ongoing Classes',
                    description: "You don't have any classes in progress right now. Your scheduled classes will appear here when they start.",
                    actionText: 'Browse Teachers',
                    actionLink: '/student/browse-teachers'
                };
            case 'completed':
                return {
                    icon: CheckCircle,
                    title: 'No Completed Classes',
                    description: "You haven't completed any classes yet. Start your Islamic education journey by booking your first lesson.",
                    actionText: 'Start Learning',
                    actionLink: '/student/browse-teachers'
                };
        }
    };

    const content = getEmptyStateContent();
    const IconComponent = content.icon;

    return (
        <div className="text-center py-16">
            {/* Icon Circle */}
            <div className="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <IconComponent className="w-12 h-12 text-gray-400" />
            </div>
            
            {/* Content */}
            <div className="max-w-md mx-auto">
                <h4 className="text-lg font-medium text-gray-900 mb-3">
                    {content.title}
                </h4>
                <p className="text-gray-600 mb-8 leading-relaxed">
                    {content.description}
                </p>
                
                {/* Action Button */}
                <Link href={content.actionLink}>
                    <button className="bg-[#14B8A6] hover:bg-[#0D9488] text-white px-6 py-3 rounded-full font-medium transition-colors duration-200 shadow-sm">
                        {content.actionText}
                    </button>
                </Link>
            </div>
        </div>
    );
}
