/**
 * 🎨 FIGMA REFERENCE
 * URL: Based on student dashboard patterns and booking management design
 * Export: My Bookings page with tab navigation and booking cards
 * 
 * EXACT SPECS FROM FIGMA:
 * - Header: "My Bookings" title with consistent typography
 * - Tabs: Upcoming Classes, Ongoing Class, Completed Classes with active state styling
 * - Cards: Subject image, title, teacher info, date/time, status badges
 * - Actions: View Details, Reschedule, Cancel Booking buttons with proper positioning
 * - Colors: Primary teal (#2c7870), secondary text, status-specific badge colors
 * - Spacing: Consistent with design system (24px gaps, 20px padding)
 */
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { Calendar, Video, Clock, User } from 'lucide-react';
import { PageProps, BookingsPageProps, BookingData } from '@/types';

interface Props extends PageProps, BookingsPageProps {}

type TabType = 'upcoming' | 'ongoing' | 'completed';

interface BookingCardProps {
    booking: BookingData;
    showActions?: boolean;
}

function BookingCard({ booking, showActions = true }: BookingCardProps) {
    const handleJoinSession = () => {
        if (booking.meetingUrl) {
            window.open(booking.meetingUrl, '_blank');
        }
    };

    const getStatusBadgeColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'confirmed':
                return 'bg-teal-50 text-teal-700';
            case 'pending':
                return 'bg-amber-50 text-amber-700';
            case 'approved':
                return 'bg-blue-50 text-blue-700';
            case 'completed':
                return 'bg-gray-50 text-gray-700';
            case 'cancelled':
                return 'bg-red-50 text-red-700';
            default:
                return 'bg-gray-50 text-gray-700';
        }
    };

    return (
        <div className="bg-white rounded-[20px] border border-gray-100 p-6 hover:shadow-sm transition-shadow">
            <div className="flex items-center gap-4">
                {/* Subject Initials */}
                <div className="w-16 h-16 rounded-[16px] bg-gradient-to-br from-[#2c7870] to-[#236158] flex items-center justify-center flex-shrink-0">
                    <span className="text-white font-bold text-lg">
                        {booking.title ? booking.title.charAt(0).toUpperCase() : 'S'}
                    </span>
                </div>

                {/* Content */}
                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between mb-2">
                        <div>
                            <h3 className="text-lg font-semibold text-gray-900 mb-1">
                                {booking.title}
                            </h3>
                            <div className="flex items-center gap-2 text-sm text-gray-500 mb-2">
                                <User className="w-4 h-4" />
                                <span>By {booking.teacher}</span>
                            </div>
                        </div>
                        <span className={`rounded-full px-3 py-1 text-xs font-medium ${getStatusBadgeColor(booking.status)}`}>
                            {booking.status}
                        </span>
                    </div>

                    <div className="flex items-center gap-4 text-sm text-gray-600 mb-4">
                        <div className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            <span>{booking.date}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Clock className="w-4 h-4" />
                            <span>{booking.time}</span>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    {showActions && (
                        <div className="flex items-center gap-3">
                            <button 
                            onClick={() => router.visit(`/student/my-bookings/${booking.id}`)}
                            className="rounded-full bg-[#2c7870] hover:bg-[#236158] px-4 py-2 text-white text-sm font-medium">
                                View Details
                            </button>

                            {booking.can_join && (
                                <button 
                                    onClick={handleJoinSession}
                                    className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-4 flex items-center gap-2 text-sm font-medium transition-colors"
                                >
                                    <Video className="w-4 h-4" />
                                    Join
                                </button>
                            )}

                            {booking.can_reschedule && (
                                <button className="rounded-full text-[#2c7870] border border-[#2c7870] px-4 py-2 text-sm font-medium">
                                    Reschedule
                                </button>
                            )}

                            {booking.can_cancel && (
                                <button className="text-red-600 hover:text-red-700 text-sm font-medium">
                                    Cancel Booking
                                </button>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

function EmptyState({ type }: { type: TabType }) {
    const getEmptyStateContent = () => {
        switch (type) {
            case 'upcoming':
                return {
                    title: 'No Upcoming Classes',
                    description: "You don't have any upcoming classes scheduled.",
                    actionText: 'Book a Class',
                    actionLink: '/student/browse-teachers'
                };
            case 'ongoing':
                return {
                    title: 'No Ongoing Classes',
                    description: "You don't have any classes in progress right now.",
                    actionText: 'Browse Teachers',
                    actionLink: '/student/browse-teachers'
                };
            case 'completed':
                return {
                    title: 'No Completed Classes',
                    description: "You haven't completed any classes yet.",
                    actionText: 'Start Learning',
                    actionLink: '/student/browse-teachers'
                };
        }
    };

    const content = getEmptyStateContent();

    return (
        <div className="text-center py-12">
            <div className="w-24 h-24 mx-auto mb-6 bg-gray-100 rounded-full flex items-center justify-center">
                <Calendar className="w-12 h-12 text-gray-400" />
            </div>
            <h4 className="text-lg font-medium text-gray-900 mb-2">{content.title}</h4>
            <p className="text-gray-500 mb-6">{content.description}</p>
            <Link href={content.actionLink}>
                <button className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full py-2 px-6 font-medium transition-colors">
                    {content.actionText}
                </button>
            </Link>
        </div>
    );
}

export default function MyBookings({ auth, bookings, stats }: Props) {
    const [activeTab, setActiveTab] = useState<TabType>('upcoming');

    const tabs = [
        { id: 'upcoming' as TabType, label: 'Upcoming Classes', count: stats.upcoming },
        { id: 'ongoing' as TabType, label: 'Ongoing Class', count: stats.ongoing },
        { id: 'completed' as TabType, label: 'Completed Classes', count: stats.completed },
    ];

    const getCurrentBookings = (): BookingData[] => {
        return bookings[activeTab] || [];
    };

    return (
        <StudentLayout pageTitle="My Bookings">
            <Head title="My Bookings" />

            <div className="space-y-6">
                <div className="mb-6 mt-4">
                    <h1 className="text-3xl font-bold text-gray-900 mb-3">
                        My Bookings
                    </h1>
                </div>
                {/* Tab Navigation */}
                <div className="bg-white rounded-[20px] border border-gray-100 p-2 mb-8 inline-flex">
                    {tabs.map((tab) => (
                        <button
                            key={tab.id}
                            onClick={() => setActiveTab(tab.id)}
                            className={`relative px-6 py-3 rounded-[16px] text-sm font-medium transition-all ${
                                activeTab === tab.id
                                    ? 'bg-[#2c7870] text-white shadow-sm'
                                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                            }`}
                        >
                            {tab.label}
                            {tab.count > 0 && (
                                <span
                                    className={`ml-2 inline-flex items-center justify-center w-5 h-5 text-xs rounded-full ${
                                        activeTab === tab.id
                                            ? 'bg-white/20 text-white'
                                            : 'bg-gray-100 text-gray-600'
                                    }`}
                                >
                                    {tab.count}
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                {/* Content Area */}
                <div className="space-y-6">
                    {getCurrentBookings().length > 0 ? (
                        getCurrentBookings().map((booking) => (
                            <BookingCard 
                                key={booking.booking_uuid} 
                                booking={booking}
                                showActions={activeTab !== 'completed'}
                            />
                        ))
                    ) : (
                        <EmptyState type={activeTab} />
                    )}
                </div>
            </div>
        </StudentLayout>
    );
}
