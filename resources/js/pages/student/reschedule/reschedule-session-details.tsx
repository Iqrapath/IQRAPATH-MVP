/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Session Details page for reschedule process
 * 
 * EXACT SPECS FROM FIGMA:
 * - Subject selection with checkboxes
 * - Note to teacher textarea
 * - Go Back and Continue buttons
 * - Clean layout with proper spacing
 * - Show current booking details
 * - Show reschedule reason field
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import RecommendedTeachers from '../components/RecommendedTeachers';
import { type RecommendedTeacher } from '@/types';
import { BookingData } from '@/types';

interface TimeSlot {
    id: number;
    day_of_week: string;
    start_time: string;
    end_time: string;
    formatted_time: string;
    time_zone: string;
}

interface RescheduleSessionDetailsPageProps {
    booking_id: number;
    teacher_id: number;
    dates: string[];
    availability_ids: number[];
    time_slots: TimeSlot[];
    booking: BookingData;
    teacher?: {
        id: number;
        name: string;
        subjects?: Array<{
            id: number;
            name: string;
            template?: {
                name: string;
            };
        }>;
        recommended_teachers?: RecommendedTeacher[];
    };
    previous_page?: string;
}

export default function RescheduleSessionDetailsPage({ 
    booking_id,
    teacher_id, 
    dates, 
    availability_ids, 
    time_slots,
    booking,
    teacher,
    previous_page
}: RescheduleSessionDetailsPageProps) {
    const [selectedSubjects, setSelectedSubjects] = useState<string[]>([]);
    const [noteToTeacher, setNoteToTeacher] = useState('');
    const [rescheduleReason, setRescheduleReason] = useState('');

    // Pre-populate with current booking subjects
    React.useEffect(() => {
        if (booking && booking.subject) {
            const subjectName = typeof booking.subject === 'object' ? booking.subject.name : booking.title;
            if (subjectName) {
                setSelectedSubjects([subjectName]);
            }
        }
    }, [booking]);

    const formatTimeSlots = (): string => {
        if (time_slots.length === 0) {
            return "Time not selected";
        }
        
        if (time_slots.length === 1) {
            return time_slots[0].formatted_time;
        }
        
        // For multiple time slots, group by day and show times
        const groupedByDay: { [key: string]: string[] } = {};
        time_slots.forEach(slot => {
            if (!groupedByDay[slot.day_of_week]) {
                groupedByDay[slot.day_of_week] = [];
            }
            groupedByDay[slot.day_of_week].push(slot.formatted_time);
        });
        
        const dayOrder = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        const sortedDays = Object.keys(groupedByDay).sort((a, b) => {
            return dayOrder.indexOf(a) - dayOrder.indexOf(b);
        });
        
        return sortedDays.map(day => 
            `${day}: ${groupedByDay[day].join(', ')}`
        ).join(' | ');
    };

    // Show loading state while redirecting if missing required data
    if (!booking_id || !teacher_id || !dates || dates.length === 0 || !availability_ids || availability_ids.length === 0) {
        return (
            <StudentLayout pageTitle="Reschedule Session Details">
                <Head title="Reschedule Session Details" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to reschedule...</p>
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#2C7870] mx-auto"></div>
                    </div>
                </div>
            </StudentLayout>
        );
    }

    // Available subjects - only from teacher's subjects
    const availableSubjects = teacher?.subjects?.map(s => s.template?.name || s.name).filter(Boolean) || [];

    const handleSubjectToggle = (subject: string) => {
        setSelectedSubjects(prev => {
            if (prev.includes(subject)) {
                return prev.filter(s => s !== subject);
            } else {
                return [...prev, subject];
            }
        });
    };

    const handleGoBack = () => {
        router.visit('/student/reschedule/class', {
            method: 'post',
            data: {
                booking_id,
                teacher_id,
                dates,
                availability_ids
            }
        });
    };

    const handleContinue = () => {
        if (availableSubjects.length === 0) {
            alert('This teacher has no subjects configured. Please contact support.');
            return;
        }
        
        if (selectedSubjects.length === 0) {
            alert('Please select at least one subject');
            return;
        }

        if (!rescheduleReason.trim()) {
            alert('Please provide a reason for rescheduling');
            return;
        }

        router.visit('/student/reschedule/pricing-payment', {
            method: 'post',
            data: {
                booking_id,
                teacher_id,
                dates,
                availability_ids: availability_ids,
                subjects: selectedSubjects,
                note_to_teacher: noteToTeacher,
                reschedule_reason: rescheduleReason
            }
        });
    };

    return (
        <StudentLayout pageTitle="Reschedule Session Details">
            <Head title="Reschedule Session Details" />
            <div className="">
                <div className="">
                    {/* Header */}
                    <div className="mb-10">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Reschedule Subject / Session Details
                        </h1>
                        <div className="text-sm text-[#4F4F4F] mb-2">
                            Selected {dates.length} day{dates.length > 1 ? 's' : ''} • {availability_ids.length} time slot{availability_ids.length > 1 ? 's' : ''}
                        </div>
                        <div className="text-sm text-[#828282]">
                            {dates.map((date, index) => (
                                <div key={index} className="mb-1">
                                    <span className="font-medium">
                                        {new Date(date).toLocaleDateString('en-US', { 
                                            weekday: 'short', 
                                            month: 'short', 
                                            day: 'numeric' 
                                        })}
                                    </span>
                                </div>
                            ))}
                            <div className="text-sm text-[#4F4F4F] mt-2">
                                <span className="font-medium">Time:</span> {formatTimeSlots()}
                            </div>
                        </div>
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

                    <div >
                        {/* Reschedule Reason */}
                        <div className="mb-8">
                            <div className="mb-4">
                                <span className="text-sm text-[#828282]">Required</span>
                            </div>
                            
                            <h3 className="text-lg font-semibold text-[#212121] mb-4">
                                Why are you rescheduling?
                            </h3>
                            
                            <textarea
                                value={rescheduleReason}
                                onChange={(e) => setRescheduleReason(e.target.value)}
                                placeholder="Please provide a reason for rescheduling this class..."
                                rows={4}
                                className="w-full p-4 border border-[#E8E8E8] rounded-lg text-base text-[#212121] placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#2C7870] focus:border-transparent resize-none"
                            />
                        </div>

                        {/* Subject Selection */}
                        <div className="mb-8">
                            <h2 className="text-xl font-semibold text-[#212121] mb-6">
                                Select your Subject {teacher?.name && (
                                    <span className="text-sm font-normal text-[#828282]">
                                        - {teacher.name}'s subjects
                                    </span>
                                )}
                            </h2>
                            
                            {availableSubjects.length > 0 ? (
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 space-y-2">
                                    {availableSubjects.map((subject) => (
                                        <label 
                                            key={subject}
                                            className="flex items-center gap-1"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedSubjects.includes(subject)}
                                                onChange={() => handleSubjectToggle(subject)}
                                                className="w-5 h-5 rounded border-2 border-[#BDBDBD] text-[#2C7870] focus:ring-[#2C7870] focus:ring-1"
                                            />
                                            <span className="text-lg font-normal text-[#212121] cursor-pointer hover:text-[#2C7870]">
                                                {subject}
                                            </span>
                                        </label>
                                    ))}
                                </div>
                            ) : (
                                <div className="p-6 bg-[#FFF3CD] border border-[#FFEAA7] rounded-lg">
                                    <p className="text-[#856404] text-base">
                                        This teacher has no subjects configured. Please contact support or go back to select a different teacher.
                                    </p>
                                </div>
                            )}
                        </div>

                        {/* Note to Teacher */}
                        <div className="mb-8">
                            <div className="mb-4">
                                <span className="text-sm text-[#828282]">Optional Message</span>
                            </div>
                            
                            <h3 className="text-lg font-semibold text-[#212121] mb-4">
                                Note to Teacher
                            </h3>
                            
                            <textarea
                                value={noteToTeacher}
                                onChange={(e) => setNoteToTeacher(e.target.value)}
                                placeholder="I want to revise Surah Al-Baqarah"
                                rows={4}
                                className="w-full p-4 border border-[#E8E8E8] rounded-lg text-base text-[#212121] placeholder-[#828282] focus:outline-none focus:ring-2 focus:ring-[#2C7870] focus:border-transparent resize-none"
                            />
                        </div>

                        {/* Action Buttons */}
                        <div className="flex gap-4 justify-end">
                            <Button
                                variant="outline"
                                onClick={handleGoBack}
                                className="px-8 py-3 text-[#4F4F4F] border-[#E8E8E8] hover:bg-[#F8F9FA] rounded-lg"
                            >
                                Go Back
                            </Button>
                            <Button
                                onClick={handleContinue}
                                disabled={availableSubjects.length === 0 || selectedSubjects.length === 0 || !rescheduleReason.trim()}
                                className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white disabled:bg-[#BDBDBD] disabled:cursor-not-allowed rounded-lg"
                            >
                                Continue
                            </Button>
                        </div>
                    </div>
                </div>

                <RecommendedTeachers teachers={teacher?.recommended_teachers || []} />
            </div>
        </StudentLayout>
    );
}