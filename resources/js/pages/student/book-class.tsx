/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=397-27039&t=O1w7ozri9pYud8IO-0
 * 
 * EXACT SPECS FROM FIGMA:
 * - Colors: #2C7870 (primary), #4F4F4F (text), #6FCF97 (verified badge)
 * - Spacing: 24px between major sections, 16px internal padding
 * - Typography: 24px heading, 16px body text, 14px secondary text
 * - Calendar: Month/year navigation, day selection with highlighting
 * - Time slots: Morning/Afternoon/Evening categories with specific times
 * - Buttons: Cancel (outline) and Continue (filled) with exact styling
 */

import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import TeacherProfileSection from '@/components/student/TeacherProfileSection';
import BookingCalendar from '@/components/student/BookingCalendar';
import TimeSlotGrid from '@/components/student/TimeSlotGrid';
import RecommendedTeachers from './components/RecommendedTeachers';
import { type RecommendedTeacher } from '@/types';

interface TeacherAvailability {
    id: number;
    day_of_week: number;
    start_time: string;
    end_time: string;
    is_active: boolean;
    time_zone?: string;
    formatted_time: string;
    availability_type?: string;
}

interface Teacher {
    id: number;
    name: string;
    avatar?: string;
    rating: number;
    hourly_rate_usd?: number;
    hourly_rate_ngn?: number;
    subjects?: any[];
    location?: string;
    bio?: string;
    experience_years?: string;
    reviews_count?: number;
    availability?: string;
    verified?: boolean;
    availabilities?: TeacherAvailability[];
    recommended_teachers?: RecommendedTeacher[];
}

interface CurrentBooking {
    id: number;
    booking_uuid: string;
    current_date: string;
    current_start_time: string;
    current_end_time: string;
    subject: string;
    teacher_name: string;
    current_availability_ids: number[];
}

interface BookClassPageProps {
    teacher?: Teacher;
    teacherId?: string;
}

export default function BookClassPage({ teacher, teacherId }: BookClassPageProps) {
    const [selectedDates, setSelectedDates] = useState<Date[]>([]);
    const [selectedAvailabilityIds, setSelectedAvailabilityIds] = useState<number[]>([]);

    if (!teacher) {
        return (
            <StudentLayout pageTitle="Book Class">
                <Head title="Book Class" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600">Teacher not found</p>
                        <Button 
                            onClick={() => router.visit('/student/browse-teachers')}
                            className="mt-4 bg-[#2C7870] hover:bg-[#236158]"
                        >
                            Browse Teachers
                        </Button>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Handler functions
    const handleDateSelect = (date: Date) => {
        setSelectedDates(prev => {
            const dateString = date.toDateString();
            const isSelected = prev.some(d => d.toDateString() === dateString);
            
            if (isSelected) {
                // Remove date if already selected
                return prev.filter(d => d.toDateString() !== dateString);
            } else {
                // Add date if not selected
                return [...prev, date];
            }
        });
    };

    const handleTimeSlotSelect = (availabilityId: number) => {
        setSelectedAvailabilityIds(prev => {
            if (prev.includes(availabilityId)) {
                // Remove if already selected
                return prev.filter(id => id !== availabilityId);
            } else {
                // Add if not selected
                return [...prev, availabilityId];
            }
        });
    };

    const handleClearAll = () => {
        setSelectedAvailabilityIds([]);
        setSelectedDates([]);
    };

    const handleContinue = () => {
        if (selectedDates.length > 0 && selectedAvailabilityIds.length > 0) {
            // Navigate to session details page for new booking
            router.visit('/student/booking/session-details', {
                method: 'post',
                data: {
                    teacher_id: teacher.id,
                    dates: selectedDates.map(date => date.toISOString().split('T')[0]),
                    availability_ids: selectedAvailabilityIds
                }
            });
        } else {
            alert('Please select at least one date and time slot');
        }
    };

    return (
        <StudentLayout pageTitle="Book Class">
            <Head title="Book Class" />
            <div className="min-h-screen">
                <div className="max-w-4xl mx-auto px-6 py-8">
                    {/* Header Section */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Book a Class
                        </h1>
                        <p className="text-base text-gray-600">
                            You're booking a session with {teacher.name}
                        </p>
                    </div>

                    {/* Teacher Profile Section */}
                    <div className="mb-8">
                        <TeacherProfileSection teacher={teacher} />
                    </div>

                    {/* Main Booking Interface */}
                    <div className="space-y-8">
                        {/* Section 1: Select Date & Time */}
                            <h2 className="text-xl font-bold text-gray-900 mb-6">Select Date & Time</h2>
                        <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <BookingCalendar 
                                availabilities={teacher.availabilities || []}
                                selectedDates={selectedDates}
                                onDateSelect={handleDateSelect}
                            />
                        </div>

                        {/* Section 2: Select time */}
                            <h2 className="text-xl font-bold text-gray-900 mb-6">Select time</h2>
                        <div className="">
                            <TimeSlotGrid 
                                selectedDates={selectedDates}
                                availabilities={teacher.availabilities || []}
                                selectedAvailabilityIds={selectedAvailabilityIds}
                                onTimeSlotSelect={handleTimeSlotSelect}
                                onClearAll={handleClearAll}
                            />
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-end pt-4">
                            <Button
                                variant="outline"
                                onClick={() => router.visit('/student/browse-teachers')}
                                className="px-8 py-3 text-[#2c7870] border-[#2c7870] hover:bg-[#2c7870] hover:text-white rounded-lg font-medium transition-colors"
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleContinue}
                                disabled={selectedDates.length === 0 || selectedAvailabilityIds.length === 0}
                                className="px-8 py-3 bg-[#2c7870] hover:bg-[#236158] text-white disabled:bg-gray-300 disabled:cursor-not-allowed rounded-lg font-medium transition-colors"
                            >
                                Continue {selectedDates.length > 0 && selectedAvailabilityIds.length > 0 && `(${selectedDates.length} day${selectedDates.length > 1 ? 's' : ''}, ${selectedAvailabilityIds.length} slot${selectedAvailabilityIds.length > 1 ? 's' : ''})`}
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Recommended Teachers Section */}
                <div className="mt-12">
                    <RecommendedTeachers teachers={teacher.recommended_teachers || []} />
                </div>
            </div>
        </StudentLayout>
    );
}