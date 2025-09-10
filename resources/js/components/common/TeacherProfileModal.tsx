/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=394-26445&t=O1w7ozri9pYud8IO-0
 * Export: Teacher profile modal (preview)
 */
import React, { useState, useEffect } from 'react';
import { Star } from 'lucide-react';
import AppModal from '@/components/common/AppModal';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import { UserLocationIcon } from '../icons/user-location-icon';
import { MessageCircleStudentIcon } from '../icons/message-circle-student-icon';
import TeacherTabBio from './teacher-tabs/TeacherTabBio';
import TeacherTabCalendar from './teacher-tabs/TeacherTabCalendar';
import TeacherTabPricing from './teacher-tabs/TeacherTabPricing';
import TeacherTabReviews from './teacher-tabs/TeacherTabReviews';
import { VerifiedIcon } from '../icons/verified-icon';
import { router } from '@inertiajs/react';


interface TeacherProfileModalProps {
    teacher: {
        id: number;
        name: string;
        avatar?: string;
        subjects: unknown; // Can be string[] | string | { name: string }[]
        rating: number;
        hourly_rate_ngn?: number | string; // optional, some feeds may omit
        location?: string;
        availability?: string;
        reviews_count?: number | string;
        bio?: string;
        experience_years?: string;
        verified?: boolean; // Teacher verification status
        availability_data?: {
            availability_type?: string;
            time_slots?: Array<{
                start_time: string;
                end_time: string;
                day_of_week: number;
                color?: string;
                label?: string;
                is_active?: boolean;
                time_zone?: string;
            }>;
        };
        [key: string]: any; // Allow additional properties for tab components
    };
    trigger: React.ReactNode;
}

function normalizeSubjects(subjects: unknown): string[] {
    if (Array.isArray(subjects)) {
        if (subjects.length === 0) return [];
        if (typeof subjects[0] === 'string') {
            return (subjects as string[]).filter(Boolean);
        }
        return (subjects as any[])
            .map((s) => (s?.template?.name ?? s?.name) as string)
            .filter(Boolean);
    }
    if (typeof subjects === 'string') {
        return subjects
            .split(',')
            .map((s) => s.trim())
            .filter(Boolean);
    }
    return [];
}

function formatNaira(value?: number | string): string {
    const amount = Number(value ?? 0);
    if (Number.isNaN(amount)) return '0';
    return amount.toLocaleString();
}

export default function TeacherProfileModal({ teacher, trigger }: TeacherProfileModalProps) {
    // State for detailed teacher data
    const [detailedTeacher, setDetailedTeacher] = useState<any>(teacher);
    const [isLoading, setIsLoading] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    // Fetch detailed teacher data when modal opens
    useEffect(() => {
        if (modalOpen && teacher.id) {
            setIsLoading(true);
            fetch(`/student/teachers/${teacher.id}/profile-data`)
                .then(response => response.json())
                .then(data => {
                    // console.log('Fetched detailed teacher data:', data);
                    setDetailedTeacher(data.teacher);
                })
                .catch(error => {
                    // console.error('Error fetching teacher data:', error);
                    // Fallback to original teacher data
                    setDetailedTeacher(teacher);
                })
                .finally(() => {
                    setIsLoading(false);
                });
        }
    }, [modalOpen, teacher.id]);

    // CSS to hide scrollbar
    const hideScrollbarStyle = `
        .hide-scrollbar::-webkit-scrollbar {
            display: none;
        }
    `;

    const renderStars = (rating: number) => {
        const full = Math.floor(rating);
        const partial = rating % 1;
        const empty = 5 - Math.ceil(rating);
        return (
            <div className="flex items-center gap-1">
                {Array.from({ length: full }, (_, i) => (
                    <Star key={`f-${i}`} className="w-4 h-4 text-amber-400 fill-amber-400" />
                ))}
                {partial > 0 && (
                    <div className="relative">
                        <Star className="w-4 h-4 text-gray-300" />
                        <div className="absolute inset-0 overflow-hidden" style={{ width: `${partial * 100}%` }}>
                            <Star className="w-4 h-4 text-amber-400 fill-amber-400" />
                        </div>
                    </div>
                )}
                {Array.from({ length: empty }, (_, i) => (
                    <Star key={`e-${i}`} className="w-4 h-4 text-gray-300" />
                ))}
            </div>
        );
    };

    const currentTeacher = isLoading ? teacher : detailedTeacher;
    const subjectsLine = normalizeSubjects(currentTeacher.subjects).slice(0, 5).join(', ');
    const availability = currentTeacher.availability;
    const location = currentTeacher.location;
    const reviews = currentTeacher.reviews_count ?? 120;

    return (
        <>
            <style dangerouslySetInnerHTML={{ __html: hideScrollbarStyle }} />
            <AppModal
                title="Teacher Profile"
                description="View teacher details, availability, and pricing"
                size="xl"
                trigger={trigger}
                open={modalOpen}
                onOpenChange={setModalOpen}
            >
                <div
                    className="bg-white rounded-3xl p-8 space-y-6 max-h-[80vh] overflow-y-auto scroll-smooth hide-scrollbar"
                    style={{
                        scrollbarWidth: 'none', /* Firefox */
                        msOverflowStyle: 'none', /* IE and Edge */
                    }}
                >
                    {/* Header */}
                    <div className="flex items-start gap-6">
                        <div className="flex flex-col items-start">
                            <Avatar className="w-24 h-24 rounded-2xl p-2 border-2 border-gray-200">
                                <AvatarImage src={currentTeacher.avatar} alt={currentTeacher.name} className="rounded-2xl p-2" />
                                <AvatarFallback className="bg-[#2C7870] text-white font-semibold rounded-2xl text-lg p-2">
                                    {currentTeacher.name.split(' ').map((n: string) => n[0]).join('').substring(0, 2)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="flex items-center gap-2 mt-2 text-sm text-gray-700 p-1">
                                <VerifiedIcon className="w-4 h-4 text-[#2C7870]" />
                                <span className="text-sm">{currentTeacher.verified ? 'Verified' : 'Not Verified'}</span>
                            </div>
                        </div>
                        <div className="flex-1 min-w-0">
                            <h1 className="text-2xl font-bold text-gray-900 leading-tight mb-2">{currentTeacher.name}</h1>
                            <div className="flex items-center gap-2 text-gray-600 mb-3">
                                <UserLocationIcon className="w-4 h-4" />
                                <span className="text-sm">{location}</span>
                            </div>
                            <div className="flex items-center gap-2 mb-4">
                                {renderStars(currentTeacher.rating)}
                                <span className="text-sm text-gray-500">{Number(currentTeacher.rating).toFixed(1)}/5 from {reviews} Students</span>
                            </div>
                            <div className="mb-3">
                                <div className="text-sm text-gray-500 mb-1">Subjects Taught</div>
                                <div className="text-lg font-medium text-gray-900">{subjectsLine}</div>
                            </div>
                            <div>
                                <div className="text-sm text-gray-500 mb-1">Availability</div>
                                <div className="text-lg font-medium text-gray-900">{availability}</div>
                            </div>
                        </div>
                    </div>

                    {/* Tabs */}
                    <Tabs defaultValue="bio" className="w-full">
                        <TabsList className="grid w-full grid-cols-4 bg-transparent gap-8 h-auto p-0 border-0">
                            <TabsTrigger
                                value="bio"
                                className="relative pb-2 px-0 bg-transparent border-0 shadow-none text-gray-400 data-[state=active]:text-[#2C7870] data-[state=active]:bg-transparent font-medium"
                            >
                                Bio & Experience
                                <span className="absolute bottom-0 left-0 h-[3px] w-full bg-[#2C7870] rounded-full scale-x-0 data-[state=active]:scale-x-100 transition-transform duration-200"></span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="calendar"
                                className="relative pb-2 px-0 bg-transparent border-0 shadow-none text-gray-400 data-[state=active]:text-[#2C7870] data-[state=active]:bg-transparent font-medium"
                            >
                                Availability Calendar
                                <span className="absolute bottom-0 left-0 h-[3px] w-full bg-[#2C7870] rounded-full scale-x-0 data-[state=active]:scale-x-100 transition-transform duration-200"></span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="pricing"
                                className="relative pb-2 px-0 bg-transparent border-0 shadow-none text-gray-400 data-[state=active]:text-[#2C7870] data-[state=active]:bg-transparent font-medium"
                            >
                                Pricing
                                <span className="absolute bottom-0 left-0 h-[3px] w-full bg-[#2C7870] rounded-full scale-x-0 data-[state=active]:scale-x-100 transition-transform duration-200"></span>
                            </TabsTrigger>
                            <TabsTrigger
                                value="reviews"
                                className="relative pb-2 px-0 bg-transparent border-0 shadow-none text-gray-400 data-[state=active]:text-[#2C7870] data-[state=active]:bg-transparent font-medium"
                            >
                                Ratings & Reviews
                                <span className="absolute bottom-0 left-0 h-[3px] w-full bg-[#2C7870] rounded-full scale-x-0 data-[state=active]:scale-x-100 transition-transform duration-200"></span>
                            </TabsTrigger>
                        </TabsList>

                        <TabsContent value="bio" className="mt-6">
                            <TeacherTabBio teacher={currentTeacher} />
                        </TabsContent>

                        <TabsContent value="calendar" className="mt-6">
                            <TeacherTabCalendar
                                teacherId={currentTeacher.id}
                                availabilityData={currentTeacher.availability_data}
                            />
                        </TabsContent>

                        <TabsContent value="pricing" className="mt-6">
                            <TeacherTabPricing
                                usd={currentTeacher.hourly_rate_usd}
                                ngn={currentTeacher.hourly_rate_ngn}
                                teacher={currentTeacher}
                            />
                        </TabsContent>

                        <TabsContent value="reviews" className="mt-6">
                            <TeacherTabReviews
                                teacherId={currentTeacher.id}
                                teacher={currentTeacher}
                            />
                        </TabsContent>
                    </Tabs>

                    <div className="flex items-center gap-3 pt-4 mb-4">
                        <Button
                            onClick={() => router.visit(`/student/book-class?teacherId=${currentTeacher.id}`)}
                            className="bg-[#2C7870] hover:bg-[#236158] text-white px-6 py-2 rounded-full font-medium">
                            Book Now
                        </Button>
                        <Button
                            variant="ghost"
                            className="text-[#2C7870] hover:text-[#236158] hover:bg-transparent px-6 py-2 rounded-full font-medium border-b-3 border-[#2C7870] flex items-center gap-2"
                        >
                            <MessageCircleStudentIcon className="w-4 h-4" />
                            Send Message
                        </Button>
                    </div>
                </div>
            </AppModal>
        </>
    );
}


