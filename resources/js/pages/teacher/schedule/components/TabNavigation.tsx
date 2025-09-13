import React from 'react';
import { Button } from '@/components/ui/button';

interface TabNavigationProps {
    activeTab: 'availability' | 'schedule';
    onTabChange: (tab: 'availability' | 'schedule') => void;
}

export default function TabNavigation({ activeTab, onTabChange }: TabNavigationProps) {
    return (
        <div className="flex space-x-1 bg-white p-1 rounded-lg border border-gray-200 w-fit">
            <Button
                variant={activeTab === 'availability' ? 'default' : 'ghost'}
                onClick={() => onTabChange('availability')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${
                    activeTab === 'availability'
                        ? 'bg-teal-600 text-white shadow-sm'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                }`}
            >
                Availability Settings
            </Button>
            <Button
                variant={activeTab === 'schedule' ? 'default' : 'ghost'}
                onClick={() => onTabChange('schedule')}
                className={`px-6 py-2 rounded-md transition-all duration-200 ${
                    activeTab === 'schedule'
                        ? 'bg-teal-600 text-white shadow-sm'
                        : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100'
                }`}
            >
                Your Schedule
            </Button>
        </div>
    );
}