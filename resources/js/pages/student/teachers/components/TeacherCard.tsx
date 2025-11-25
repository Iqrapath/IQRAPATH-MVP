/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=405-22320&t=O1w7ozri9pYud8IO-0
 * Export: Teacher Profile Card Component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Card: White background, rounded corners, subtle shadow
 * - Profile picture: 96px square, rounded-xl corners, left side
 * - Content: Vertical stack right of image
 * - Name: 24px bold, #1F2937
 * - Subject: 14px gray-500 label + 14px gray-700 value
 * - Location: Pin icon + 14px gray-600 text
 * - Rating: Stars + rating text
 * - Availability: 14px gray-500 label + 14px gray-700 value  
 * - Pricing: #2C7870 background, white text, rounded pill
 * - Actions: "View Profile" link + circular chat button
 */
import { MapPin, Star } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { MessageCircleStudentIcon } from '@/components/icons/message-circle-student-icon';
import TeacherProfileModal from '../../../../components/common/TeacherProfileModal';
import axios from 'axios';
import { toast } from 'sonner';

interface TeacherCardProps {
    teacher: {
        id: number;
        name: string;
        avatar?: string;
        subjects?: string[];
        location: string;
        rating?: number;
        reviews_count?: number;
        availability?: string;
        hourly_rate_usd?: number;
        hourly_rate_ngn?: number;
        verified: boolean;
        experience_years: string;
        teaching_mode: string;
        languages: string[];
        is_on_holiday?: boolean;
    };
}

export default function TeacherCard({ teacher }: TeacherCardProps) {
    // Convert teacher data to match TeacherProfileModal interface
    const modalTeacher = {
        ...teacher,
        subjects: teacher.subjects || [],
        rating: teacher.rating || 0,
        reviews_count: teacher.reviews_count || 0,
        location: teacher.location || '',
        availability: teacher.availability || '',
        hourly_rate_ngn: teacher.hourly_rate_ngn || 0,
    };
    const renderStars = (rating: number) => {
        const safeRating = rating || 0;
        const fullStars = Math.floor(safeRating);
        const partialStar = safeRating % 1;
        const emptyStars = 5 - Math.ceil(safeRating);

        return (
            <div className="flex items-center gap-1">
                {/* Full stars */}
                {Array.from({ length: fullStars }, (_, i) => (
                    <Star key={i} className="w-4 h-4 text-yellow-400 fill-current" />
                ))}
                {/* Partial star */}
                {partialStar > 0 && (
                    <div className="relative">
                        <Star className="w-4 h-4 text-gray-300" />
                        <div
                            className="absolute top-0 left-0 overflow-hidden"
                            style={{ width: `${partialStar * 100}%` }}
                        >
                            <Star className="w-4 h-4 text-yellow-400 fill-current" />
                        </div>
                    </div>
                )}
                {/* Empty stars */}
                {Array.from({ length: emptyStars }, (_, i) => (
                    <Star key={`empty-${i}`} className="w-4 h-4 text-gray-300" />
                ))}
            </div>
        );
    };

    const formatSubjects = (subjects: string[]) => {
        if (!subjects || subjects.length === 0) return 'No subjects listed';
        return subjects.slice(0, 2).join(', ');
    };

    const formatAvailability = (availability?: string) => {
        if (!availability) return 'Not specified';
        return availability;
    };

    const handleMessageTeacher = async () => {
        try {
            // Create or get existing conversation with this teacher
            const response = await axios.post('/api/conversations', {
                recipient_id: teacher.id
            });

            if (response.data.success && response.data.data) {
                // Navigate to the conversation
                router.visit(route('student.messages.show', response.data.data.id));
            }
        } catch (error) {
            console.error('Failed to start conversation:', error);
            if (axios.isAxiosError(error) && error.response) {
                const errorData = error.response.data;
                
                // Check if it's a role restriction (no active booking)
                if (errorData.code === 'ROLE_RESTRICTION' && errorData.details?.reason === 'no_active_booking') {
                    toast.error('Book a session first', {
                        description: 'You need to book a session with this teacher before you can message them.',
                        action: {
                            label: 'View Profile',
                            onClick: () => router.visit(route('student.teachers.show', teacher.id))
                        }
                    });
                    return;
                }
                
                // Other errors
                toast.error('Failed to start conversation', {
                    description: errorData.message || 'Please try again later.'
                });
            } else {
                toast.error('Failed to start conversation', {
                    description: 'Please check your internet connection and try again.'
                });
            }
        }
    };

    return (
        <Card className="w-full border border-gray-300 shadow shadow-teal-50 rounded-2xl">
            <CardContent className="p-4 md:p-2">
                <div className="flex flex-col md:flex-row items-start md:items-start gap-4 md:gap-4 ">
                    {/* Profile Picture */}
                    <div className="relative flex-shrink-0 self-start">
                        <Avatar className="w-20 h-20 md:w-24 md:h-24 rounded-xl">
                            <AvatarImage src={teacher.avatar} alt={teacher.name} className="rounded-xl object-cover" />
                            <AvatarFallback className="bg-[#2C7870] text-white text-xl md:text-2xl font-semibold rounded-xl">
                                {teacher.name.split(' ').map(n => n[0]).join('').substring(0, 2)}
                            </AvatarFallback>
                        </Avatar>
                    </div>

                    {/* Content - Vertical Stack */}
                    <div className="flex-1  min-w-0">
                        {/* Name */}
                        <h3 className="text-lg sm:text-xl md:text-2xl font-bold text-gray-900 leading-tight mb-2 break-words">
                            {teacher.name}
                        </h3>

                        <div className="space-y-3">
                            {/* Subject */}
                            <div className="text-sm">
                                <span className="text-gray-500">Subject: </span>
                                <span className="text-gray-700 font-medium break-words">{formatSubjects(teacher.subjects || [])}</span>
                            </div>

                            {/* Location */}
                            <div className="flex items-center gap-2">
                                <MapPin className="w-4 h-4 text-gray-500 flex-shrink-0" />
                                <span className="text-sm text-gray-600 break-words">{teacher.location}</span>
                            </div>

                            {/* Rating */}
                            <div className="flex items-center gap-3">
                                {renderStars(teacher.rating || 0)}
                                <span className="text-sm font-medium text-gray-600">
                                    {teacher.rating || 0}/5
                                </span>
                            </div>

                            {/* Availability */}
                            <div className="text-sm">
                                <span className="text-gray-500">Availability: </span>
                                <span className="text-gray-700 font-medium break-words">{formatAvailability(teacher.availability || '')}</span>
                            </div>

                            {/* Holiday Status */}
                            {teacher.is_on_holiday && (
                                <div className="text-sm">
                                    <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                        🏖️ Currently on Holiday
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* Bottom row: Price pill and actions */}
                        <div className="mt-4 pr-4 flex flex-col sm:flex-row flex-wrap sm:flex-nowrap items-start sm:items-center gap-4 w-full">
                            {/* Pricing */}
                            <div className="inline-flex flex-col bg-teal-50 rounded-full px-4 py-3 text-left">
                                <div className="text-sm font-bold text-[#3B3B3B]">
                                    ${teacher.hourly_rate_usd || 30} / ₦{teacher.hourly_rate_ngn?.toLocaleString() || '0'}
                                </div>
                                <div className="text-xs text-gray-600 mt-1">Per session</div>
                            </div>

                            {/* Actions */}
                            <div
                                className="flex items-center gap-4 sm:gap-6 flex-wrap"
                            >
                                <TeacherProfileModal
                                    teacher={modalTeacher}
                                    trigger={
                                        <Button
                                            variant="link"
                                            className="text-teal-600 hover:text-teal-700 p-0 h-auto"
                                        >
                                            View Profile
                                        </Button>
                                    }
                                />
                                {/* <Button 
                                    variant="ghost" 
                                    size="sm"
                                    onClick={handleMessageTeacher}
                                    className="w-10 h-10 md:w-12 md:h-12 p-0  border-b-3 border-teal-600 text-teal-600 hover:bg-transparent hover:text-teal-600 flex-shrink-0 rounded-lg"
                                    title="Message teacher"
                                >
                                    <MessageCircleStudentIcon className="w-8 h-8 md:w-10 md:h-10" />
                                </Button> */}
                            </div>
                        </div>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
