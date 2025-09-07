/**
 * 🎨 FIGMA DESIGN REFERENCE
 * 
 * Component: BookingTabNavigation
 * Figma URL: Based on the my-bookings tab navigation design pattern
 * Export: .cursor/design-references/student/my-bookings/tab-navigation.png
 * 
 * 📏 EXACT SPECIFICATIONS:
 * - Background: #FFFFFF with border #F1F5F9
 * - Border radius: 20px
 * - Inner padding: 8px
 * - Tab radius: 16px
 * - Active tab: #14B8A6 background, white text
 * - Inactive tabs: hover #F8FAFC, text #6B7280
 * - Count badges: Rounded-full, status-specific styling
 * - Typography: 14px/medium for tab labels
 * - Spacing: Tabs touching with border-radius
 * 
 * 📱 RESPONSIVE: Scrollable on mobile
 * 🎯 STATES: Default, active, hover, count badge variations
 */
import React from 'react';

export type TabType = 'upcoming' | 'ongoing' | 'completed';

interface Tab {
    id: TabType;
    label: string;
    count: number;
}

interface BookingTabNavigationProps {
    activeTab: TabType;
    onTabChange: (tab: TabType) => void;
    tabs: Tab[];
}

export default function BookingTabNavigation({ 
    activeTab, 
    onTabChange, 
    tabs 
}: BookingTabNavigationProps) {
    return (
        <div className="bg-white rounded-[20px] border border-gray-100 p-2 inline-flex">
            {tabs.map((tab) => (
                <button
                    key={tab.id}
                    onClick={() => onTabChange(tab.id)}
                    className={`
                        relative px-6 py-3 rounded-[16px] text-sm font-medium transition-all duration-200 whitespace-nowrap
                        ${activeTab === tab.id
                            ? 'bg-[#14B8A6] text-white shadow-sm'
                            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-50'
                        }
                    `}
                >
                    <span>{tab.label}</span>
                    {tab.count > 0 && (
                        <span
                            className={`
                                ml-2 inline-flex items-center justify-center min-w-[20px] h-5 px-1 text-xs rounded-full font-medium
                                ${activeTab === tab.id
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-100 text-gray-600'
                                }
                            `}
                        >
                            {tab.count}
                        </span>
                    )}
                </button>
            ))}
        </div>
    );
}
