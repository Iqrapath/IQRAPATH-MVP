import React from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Mail,
    Phone,
    BookOpen,
    Calendar,
    Star,
    Edit
} from 'lucide-react';
import { PhoneIcon } from '@/components/icons/phone-icon';
import { BookIcon } from '@/components/icons/book-icon';
import { SessionIcon } from '@/components/icons/session-icon';

interface Teacher {
    email: string;
    phone: string;
}

interface TeacherProfile {
    subjects: any[];
    rating?: number;
    reviews_count?: number;
}

interface Props {
    teacher: Teacher;
    profile: TeacherProfile | null;
    totalSessions: number;
}

export default function TeacherContactDetails({ teacher, profile, totalSessions }: Props) {
    const subjectsList = profile?.subjects?.map(subject => subject.name).join(', ') || 'No subjects assigned';

    return (
        <Card className="mb-8 shadow-sm">
            <CardContent className="p-6">
                <div className="flex items-center justify-between">
                    <div className="space-y-6">
                        {/* First Row: Email and Phone */}
                        <div className="flex items-center gap-6">
                            <div className="flex items-center gap-3">
                                <Mail className="h-5 w-5 text-teal-600" />
                                <span className="text-gray-700">{teacher.email}</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <PhoneIcon className="h-5 w-5 text-teal-600" />
                                <span className="text-gray-700">{teacher.phone || 'Phone not provided'}</span>
                            </div>
                        </div>

                        {/* Second Row: Subjects, Sessions, and Edit Button */}
                        <div className="flex items-center gap-6">
                            <div className="flex items-center gap-3">
                                <BookIcon className="h-5 w-5 text-teal-600" />
                                <span className="text-gray-700">Subjects: {subjectsList}</span>
                            </div>
                            <div className="flex items-center gap-6">
                                <div className="flex items-center gap-2">
                                    <SessionIcon className="h-5 w-5 text-teal-600" />
                                    <span className="text-gray-700">{totalSessions} Sessions</span>
                                </div>
                            </div>
                        </div>

                                                 {/* Third Row: Rating and Reviews */}
                         <div className="flex items-center">
                             <div className="flex items-center gap-3">
                                 <Star className="h-5 w-5 text-teal-600" />
                                 <span className="text-gray-700">
                                     {profile?.rating ? `${profile.rating.toFixed(1)} (${profile.reviews_count || 0} Reviews)` : 'No reviews yet'}
                                 </span>
                             </div>
                         </div>
                    </div>
                    <div className="text-right">
                        <Button variant="link" className="text-sm p-0 h-auto cursor-pointer" disabled>
                            Edit
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
