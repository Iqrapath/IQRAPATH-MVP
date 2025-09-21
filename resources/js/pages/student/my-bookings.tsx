/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: MyBookings (Main Page)
 * Figma URLs: 
 * - Upcoming: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=409-26933&t=m6ohX2RrycH79wFY-0
 * - Ongoing: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=411-27985&t=m6ohX2RrycH79wFY-0
 * - Completed: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=412-28472&t=m6ohX2RrycH79wFY-0
 * Export: .cursor/design-references/student/my-bookings/
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Page title: 32px/bold, #111827
 * - Tab navigation: White background, rounded tabs, teal active state
 * - Card components: Status-specific layouts and interactions
 * - Spacing: 24px between sections, 20px card gaps
 * - Colors: Primary #14B8A6, backgrounds #FFFFFF, borders #F1F5F9
 * 
 * 📱 RESPONSIVE: Mobile-first design with responsive grid
 * 🎯 STATES: Tab switching, card interactions, empty states
 */
import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import { PageProps, BookingsPageProps, BookingData } from '@/types';

// Import the new component files
import BookingTabNavigation, { TabType } from './components/BookingTabNavigation';
import UpcomingClassCard from './components/UpcomingClassCard';
import OngoingClassCard from './components/OngoingClassCard';
import CompletedClassCard from './components/CompletedClassCard';
import BookingEmptyState from './components/BookingEmptyState';

interface Props extends PageProps, BookingsPageProps {}

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

    const renderBookingCard = (booking: BookingData) => {
        switch (activeTab) {
            case 'upcoming':
                return <UpcomingClassCard booking={booking} />;
            case 'ongoing':
                return <OngoingClassCard booking={booking} />;
            case 'completed':
                return <CompletedClassCard booking={booking} />;
            default:
                return <UpcomingClassCard booking={booking} />;
        }
    };

    return (
        <StudentLayout pageTitle="My Bookings">
            <Head title="My Bookings" />

            <div className="space-y-6">
                {/* Page Header */}
                <div className="mb-6 mt-4">
                    <h1 className="text-3xl font-bold text-gray-900 mb-3">
                        My Bookings
                    </h1>
                </div>

                {/* Tab Navigation */}
                <BookingTabNavigation 
                    activeTab={activeTab}
                    onTabChange={setActiveTab}
                    tabs={tabs}
                />

                {/* Content Area - Single Card Layout */}
                <div className="bg-white rounded-[20px] border border-gray-100 shadow-md">
                    {getCurrentBookings().length > 0 ? (
                        getCurrentBookings().map((booking, index) => (
                            <div key={booking.booking_uuid}>
                                <div className="p-6">
                                    {renderBookingCard(booking)}
                                </div>
                                {index < getCurrentBookings().length - 1 && (
                                    <hr className="border-gray-200 mx-6" />
                                )}
                            </div>
                        ))
                    ) : (
                        <div className="p-6">
                            <BookingEmptyState type={activeTab} />
                        </div>
                    )}
                </div>
            </div>
        </StudentLayout>
    );
}
