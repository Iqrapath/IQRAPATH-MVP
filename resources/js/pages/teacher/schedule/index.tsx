import React, { useState } from 'react';
import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import TabNavigation from './components/TabNavigation';
import AvailabilitySettings from './components/AvailabilitySettings';
import YourSchedule from './components/YourSchedule';

export default function TeacherSchedule() {
    const [activeTab, setActiveTab] = useState<'availability' | 'schedule'>('availability');

    return (
        <TeacherLayout pageTitle="Schedule">
            <Head title="Schedule" />
            
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Schedule</h1>
                </div>

                {/* Tab Navigation */}
                <TabNavigation 
                    activeTab={activeTab} 
                    onTabChange={setActiveTab} 
                />

                {/* Tab Content */}
                {activeTab === 'availability' && <AvailabilitySettings />}
                {activeTab === 'schedule' && <YourSchedule />}
            </div>
        </TeacherLayout>
    );
}
