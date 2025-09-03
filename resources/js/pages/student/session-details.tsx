/**
 * 🎨 FIGMA REFERENCE
 * Session Details page for booking process
 * 
 * EXACT SPECS FROM FIGMA:
 * - Subject selection with checkboxes
 * - Note to teacher textarea
 * - Go Back and Continue buttons
 * - Clean layout with proper spacing
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import StudentLayout from '@/layouts/student/student-layout';
import { Head } from '@inertiajs/react';
import RecommendedTeachers from './components/RecommendedTeachers';
import { type RecommendedTeacher } from '@/types';

interface SessionDetailsPageProps {
    teacher_id: number;
    date: string;
    availability_ids: number[];
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
}

export default function SessionDetailsPage({ 
    teacher_id, 
    date, 
    availability_ids, 
    teacher 
}: SessionDetailsPageProps) {
    const [selectedSubjects, setSelectedSubjects] = useState<string[]>([]);
    const [noteToTeacher, setNoteToTeacher] = useState('');

    // Show loading state while redirecting if missing required data
    if (!teacher_id || !date || !availability_ids || availability_ids.length === 0) {
        return (
            <StudentLayout pageTitle="Session Details">
                <Head title="Session Details" />
                <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                    <div className="text-center">
                        <p className="text-gray-600 mb-4">Redirecting to browse teachers...</p>
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
        router.visit(`/student/book-class?teacherId=${teacher_id}`);
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

        router.visit('/student/booking/pricing-payment', {
            method: 'post',
            data: {
                teacher_id,
                date,
                availability_ids,
                subjects: selectedSubjects,
                note_to_teacher: noteToTeacher
            }
        });
    };

    return (
        <StudentLayout pageTitle="Session Details">
            <Head title="Session Details" />
            <div className="">
                <div className="">
                    {/* Header */}
                    <div className="mb-10">
                        <h1 className="text-2xl font-bold text-[#212121] mb-2">
                            Subject / Session Details
                        </h1>
                    </div>

                    <div >
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
                                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3">
                                    {availableSubjects.map((subject) => (
                                        <label 
                                            key={subject}
                                            className="flex items-center gap-3 cursor-pointer"
                                        >
                                            <input
                                                type="checkbox"
                                                checked={selectedSubjects.includes(subject)}
                                                onChange={() => handleSubjectToggle(subject)}
                                                className="w-5 h-5 rounded border-2 border-[#BDBDBD] text-[#2C7870] focus:ring-[#2C7870] focus:ring-1"
                                            />
                                            <span className="text-lg font-normal text-[#212121]">
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
                                disabled={availableSubjects.length === 0 || selectedSubjects.length === 0}
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
