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
 * 
 * RESCHEDULE MODE:
 * - Show current booking details prominently
 * - Pre-populate with existing booking data
 * - Prevent selecting same date/time
 * - Show "Request Reschedule" instead of "Book Now"
 */

import React, { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import TeacherProfileSection from '@/components/student/TeacherProfileSection';
import BookingCalendar from '@/components/student/BookingCalendar';
import TimeSlotGrid from '@/components/student/TimeSlotGrid';
import RecommendedTeachers from '../components/RecommendedTeachers';
import { type RecommendedTeacher } from '@/types';
import { BookingData } from '@/types';

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

interface RescheduleClassPageProps {
    booking: BookingData;
    teacher: Teacher;
    availabilities: TeacherAvailability[];
}

export default function RescheduleClassPage({ booking, teacher, availabilities }: RescheduleClassPageProps) {
    const [selectedDates, setSelectedDates] = useState<Date[]>([]);
    const [selectedAvailabilityIds, setSelectedAvailabilityIds] = useState<number[]>([]);

    // Pre-populate with current booking data
    useEffect(() => {
        if (booking && booking.booking_date) {
            const currentDate = new Date(booking.booking_date);
            setSelectedDates([currentDate]);
            
            // Find current availability IDs
            const currentAvailabilityIds = availabilities
                .filter(avail => 
                    avail.start_time === booking.start_time && 
                    avail.end_time === booking.end_time
                )
                .map(avail => avail.id);
            setSelectedAvailabilityIds(currentAvailabilityIds);
        }
    }, [booking, availabilities]);

    // Handler functions - matching book-class.tsx exactly
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
            // Check if trying to reschedule to same date/time
            const isSameDate = selectedDates[0].toISOString().split('T')[0] === booking.booking_date;
            const isSameTime = selectedAvailabilityIds.some(id => {
                const availability = availabilities.find(avail => avail.id === id);
                return availability && 
                       availability.start_time === booking.start_time && 
                       availability.end_time === booking.end_time;
            });

            if (isSameDate && isSameTime) {
                alert('Please select a different date or time for rescheduling.');
                return;
            }

            // Navigate to reschedule session details page
            router.visit('/student/reschedule/session-details', {
                method: 'post',
                data: {
                    booking_id: booking.id,
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
        <StudentLayout pageTitle="Reschedule Class">
            <Head title="Reschedule Class" />
            <div className="min-h-screen">
                <div className="max-w-4xl mx-auto px-6 py-8">
                    {/* Header Section */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-bold text-gray-900 mb-2">
                            Reschedule Your Class
                        </h1>
                        <p className="text-base text-gray-600">
                            You're rescheduling a session with {teacher.name}
                        </p>
                    </div>

                    {/* Current Booking Details */}
                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
                        <h3 className="text-lg font-semibold text-blue-900 mb-4">Current Booking</h3>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Date:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', {
                                        weekday: 'long',
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric'
                                    }) : 'Unknown Date'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Time:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.start_time && booking.end_time ? 
                                        `${new Date(`2000-01-01T${booking.start_time}`).toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        })} - ${new Date(`2000-01-01T${booking.end_time}`).toLocaleTimeString('en-US', {
                                            hour: 'numeric',
                                            minute: '2-digit',
                                            hour12: true
                                        })}` : 'Unknown Time'}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Subject:</span>
                                <span className="font-medium text-gray-900">
                                    {booking.title}
                                </span>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-gray-600">Teacher:</span>
                                <span className="font-medium text-gray-900">
                                    Ustadh {typeof booking.teacher === 'object' ? booking.teacher.name : booking.teacher}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Teacher Profile Section */}
                    <div className="mb-8">
                        <TeacherProfileSection teacher={teacher} />
                    </div>

                    {/* Main Booking Interface */}
                    <div className="space-y-8">
                        {/* Section 1: Select Date & Time */}
                        <h2 className="text-xl font-bold text-gray-900 mb-6">Select New Date & Time</h2>
                        <div className="bg-white rounded-lg border border-gray-200 p-6 shadow-sm">
                            <BookingCalendar 
                                availabilities={availabilities}
                                selectedDates={selectedDates}
                                onDateSelect={handleDateSelect}
                            />
                        </div>

                        {/* Section 2: Select time */}
                        <h2 className="text-xl font-bold text-gray-900 mb-6">Select New Time</h2>
                        <div className="">
                            <TimeSlotGrid 
                                selectedDates={selectedDates}
                                availabilities={availabilities}
                                selectedAvailabilityIds={selectedAvailabilityIds}
                                onTimeSlotSelect={handleTimeSlotSelect}
                                onClearAll={handleClearAll}
                            />
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-end pt-4">
                            <Button
                                variant="outline"
                                onClick={() => router.visit('/student/my-bookings')}
                                className="px-8 py-3 text-[#2c7870] border-[#2c7870] hover:bg-[#2c7870] hover:text-white rounded-lg font-medium transition-colors"
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleContinue}
                                disabled={selectedDates.length === 0 || selectedAvailabilityIds.length === 0}
                                className="px-8 py-3 bg-[#2c7870] hover:bg-[#236158] text-white disabled:bg-gray-300 disabled:cursor-not-allowed rounded-lg font-medium transition-colors"
                            >
                                Request Reschedule {selectedDates.length > 0 && selectedAvailabilityIds.length > 0 && `(${selectedDates.length} day${selectedDates.length > 1 ? 's' : ''}, ${selectedAvailabilityIds.length} slot${selectedAvailabilityIds.length > 1 ? 's' : ''})`}
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
