/**
 * 🎨 FIGMA REFERENCE
 * Reschedule Session Details page for reschedule process
 * 
 * EXACT SPECS FROM FIGMA:
 * - Subject selection with checkboxes
 * - Note to teacher textarea
 * - Go Back and Continue buttons
 * - Clean layout with proper spacing
 * - Show student name for context
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { Head } from '@inertiajs/react';
import RecommendedTeachers from '../../student/components/RecommendedTeachers';
import { type RecommendedTeacher } from '@/types';

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
    student_name: string;
}

export default function RescheduleSessionDetailsPage({ 
    booking_id,
    teacher_id, 
    dates, 
    availability_ids, 
    time_slots,
    teacher,
    student_name
}: RescheduleSessionDetailsPageProps) {
    const [selectedSubjects, setSelectedSubjects] = useState<string[]>([]);
    const [noteToTeacher, setNoteToTeacher] = useState('');

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
            <GuardianLayout pageTitle="Reschedule Session Details">
                <Head title="Reschedule Session Details" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to my bookings...</p>
                        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-[#2C7870] mx-auto"></div>
                    </div>
                </div>
            </GuardianLayout>
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
        router.visit(`/guardian/reschedule/class?booking_id=${booking_id}`);
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

        router.visit('/guardian/reschedule/pricing-payment', {
            method: 'post',
            data: {
                booking_id,
                teacher_id,
                dates,
                availability_ids: availability_ids,
                subjects: selectedSubjects,
                note_to_teacher: noteToTeacher
            }
        });
    };

    return (
        <GuardianLayout pageTitle="Reschedule Session Details">
            <Head title="Reschedule Session Details" />
            <div className="">
                <div className="">
                    {/* Header */}
                    <div className="mb-10">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Reschedule Session Details
                        </h1>
                        <div className="text-sm text-[#4F4F4F] mb-2">
                            Rescheduling for: <span className="font-medium text-[#2C7870]">{student_name}</span>
                        </div>
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
                                <span className="font-medium">New Time:</span> {formatTimeSlots()}
                            </div>
                        </div>
                    </div>

                    <div >
                        {/* Subject Selection */}
                        <div className="mb-8">
                            <h2 className="text-xl font-semibold text-[#212121] mb-6">
                                Select Subject for {student_name} {teacher?.name && (
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
                                placeholder="Please note the reason for rescheduling and any specific requirements for the new session"
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
                                disabled={availableSubjects.length === 0 || selectedSubjects.length === 0}
                                className="px-8 py-3 bg-[#2C7870] hover:bg-[#236158] text-white disabled:bg-[#BDBDBD] disabled:cursor-not-allowed rounded-lg"
                            >
                                Continue Reschedule
                            </Button>
                        </div>
                    </div>
                </div>

                <RecommendedTeachers teachers={teacher?.recommended_teachers || []} />
            </div>
        </GuardianLayout>
    );
}
