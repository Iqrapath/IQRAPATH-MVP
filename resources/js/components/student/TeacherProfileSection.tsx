import React from 'react';
import { Clock, MapPin, Star, CheckCircle } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { VerifiedIcon } from '../icons/verified-icon';
import { UserLocationIcon } from '../icons/user-location-icon';

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
}

interface TeacherProfileSectionProps {
    teacher: Teacher;
}

const renderStars = (rating: number): React.ReactNode => {
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

export default function TeacherProfileSection({ teacher }: TeacherProfileSectionProps) {
    const formatSubjects = (subjects: any[]) => {
        if (!subjects || subjects.length === 0) return '';
        return subjects.map(s => s.template?.name || s.name).join(', ');
    };

    return (
        <div>
            {/* Header */}
                <div className="flex items-start gap-6">
                    <div className="flex flex-col items-start">
                        <Avatar className="w-24 h-24 rounded-2xl p-2 border-2 border-gray-200">
                            <AvatarImage src={teacher.avatar} alt={teacher.name} className="rounded-2xl p-2" />
                            <AvatarFallback className="bg-[#2C7870] text-white font-semibold rounded-2xl text-lg p-2">
                                {teacher.name.split(' ').map((n: string) => n[0]).join('').substring(0, 2)}
                            </AvatarFallback>
                        </Avatar>
                        <div className="flex items-center gap-2 mt-2 text-sm text-gray-700 p-1">
                            <VerifiedIcon className="w-4 h-4 text-[#2C7870]" />
                            <span className="text-sm">{teacher.verified ? 'Verified' : 'Not Verified'}</span>
                        </div>
                    </div>
                    <div className="flex-1 min-w-0">
                        <h1 className="text-2xl font-bold text-gray-900 leading-tight mb-2">{teacher.name}</h1>
                        <div className="flex items-center gap-2 text-gray-600 mb-3">
                            <UserLocationIcon className="w-4 h-4" />
                            <span className="text-sm">{teacher.location}</span>
                        </div>
                        <div className="flex items-center gap-2 mb-4">
                            {renderStars(teacher.rating)}
                            <span className="text-sm text-gray-500">{Number(teacher.rating).toFixed(1)}/5 from {teacher.reviews_count} Students</span>
                        </div>
                        <div className="mb-3">
                            <div className="text-sm text-gray-500 mb-1">Subjects Taught</div>
                            <div className="text-lg font-medium text-gray-900">{formatSubjects(teacher.subjects || [])}</div>
                        </div>
                        <div>
                            <div className="text-sm text-gray-500 mb-1">Availability</div>
                            <div className="text-lg font-medium text-gray-900">{teacher.availability}</div>
                        </div>
                    </div>
                </div>
            </div>
    );
}
