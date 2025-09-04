/**
 * 🎨 FIGMA REFERENCE
 * URL: Based on class details design from image description
 * Export: Class Details page with exact layout and styling
 * 
 * EXACT SPECS FROM FIGMA:
 * - Two main sections: Class Details and Teacher Details
 * - Class card with subject image, title, status badge, teacher info, duration, date/time
 * - Mode section with Zoom and Google Meet icons
 * - Teacher card with avatar, name, specialization, location, rating, availability
 * - Action buttons: Reschedule (teal solid) and Cancel Booking (teal outline)
 * - Colors: Primary teal (#2c7870), status badges, proper spacing
 */
import React from 'react';
import { Head, Link } from '@inertiajs/react';
import StudentLayout from '@/layouts/student/student-layout';
import {
    Calendar,
    Clock,
    User,
    MapPin,
    Star,
    Video,
    MessageCircle,
    ArrowLeft,
    Users,
    VerifiedIcon
} from 'lucide-react';
import { PageProps, ClassDetailsPageProps } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { UserLocationIcon } from '@/components/icons/user-location-icon';
import { ZoomIcon } from '@/components/icons/zoom-icon';
import { GoogleMeetIcon } from '@/components/icons/google-meet-icon';

interface Props extends PageProps, ClassDetailsPageProps { }

function ClassDetailsCard({ booking }: { booking: Props['booking'] }) {
    const getStatusBadgeColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'confirmed':
                return 'bg-teal-100 text-white';
            case 'pending':
                return 'bg-amber-50 text-amber-700';
            case 'approved':
                return 'bg-blue-50 text-blue-700';
            case 'completed':
                return 'bg-gray-50 text-gray-700';
            case 'cancelled':
                return 'bg-red-50 text-red-700';
            default:
                return 'bg-gray-50 text-gray-700';
        }
    };

    return (
        <div className="bg-white rounded-[20px] border border-gray-100 p-6">
            {/* booking details */}
            <div className="flex items-start gap-6">
                {/* Subject Initials */}
                <div className="w-24 h-24 rounded-[16px] bg-gradient-to-br from-[#2c7870] to-[#236158] flex items-center justify-center flex-shrink-0">
                    <span className="text-white font-bold text-2xl">
                        {booking.title.split(' ').map(word => word[0]).join('').substring(0, 2).toUpperCase()}
                    </span>
                </div>

                {/* Content */}
                <div className="flex-1">
                    {/* Title and Status Badge */}
                    <div className="flex items-start justify-between mb-4">
                        <h2 className="text-2xl font-bold text-gray-900">
                            {booking.title}
                        </h2>
                        <span className={`inline-block rounded-full px-3 py-1 text-sm font-medium ${getStatusBadgeColor(booking.status)}`}>
                            {booking.status}
                        </span>
                    </div>

                    {/* Teacher Information */}
                    <div className="mb-3">
                        <span className="text-sm text-gray-500">Teacher: </span>
                        <span className="text-sm text-gray-900">{booking.teacher}</span>
                    </div>

                    {/* Class Duration */}
                    <div className="mb-3">
                        <span className="text-sm text-gray-500">Class Duration: </span>
                        <span className="text-sm text-gray-900">{Math.floor(booking.duration / 60)} Hour{Math.floor(booking.duration / 60) !== 1 ? 's' : ''}</span>
                    </div>

                    {/* Date and Time Block - Light yellow-green background */}
                    <div className="mb-4">
                        <div className="bg-yellow-50 text-green-700 px-3 py-2 rounded-lg text-sm font-medium inline-block">
                            <span>{booking.date}</span>
                            <span className="mx-2 text-gray-400">|</span>
                            <span>{booking.time}</span>
                        </div>
                    </div>

                    {/* Mode Section */}
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-gray-500">Mode:</span>
                        <div className="flex items-center gap-3 bg-[#FFF9E9] rounded-full p-2">
                            <div className="flex items-center gap-1">
                                <Video className="w-8 h-8 text-green-600" />
                                <span className="text-sm text-green-700">Zoom</span>
                            </div>
                            <span className="text-gray-400">|</span>
                            <div className="flex items-center gap-1">
                                <GoogleMeetIcon className="w-4 h-4 text-blue-600" />
                                <span className="text-sm text-green-700">Google Meet</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function TeacherDetailsCard({ teacher }: { teacher: Props['teacher'] }) {
    const renderStars = (rating: number) => {
        return Array.from({ length: 5 }, (_, i) => (
            <Star
                key={i}
                className={`w-4 h-4 ${i < Math.floor(rating) ? 'text-yellow-400 fill-current' : 'text-gray-300'}`}
            />
        ));
    };

    return (
        <div className="bg-white rounded-[20px] border border-gray-100 p-6">
            <div className="flex items-start gap-6">
                {/* Teacher Avatar */}
                <Avatar className="w-24 h-24 rounded-2xl p-2 border-2 border-gray-200">
                    <AvatarImage src={teacher.avatar} alt={teacher.name} className="rounded-2xl p-2" />
                    <AvatarFallback className="bg-[#2C7870] text-white font-semibold rounded-2xl text-lg p-2">
                        {teacher.name.split(' ').map((n: string) => n[0]).join('').substring(0, 2)}
                    </AvatarFallback>
                </Avatar>

                {/* Content */}
                <div className="flex-1">
                    <div className="flex items-start gap-6">
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
                                <div className="text-lg font-medium text-gray-900">{teacher.subjects.join(', ')}</div>
                            </div>
                            <div>
                                <div className="text-sm text-gray-500 mb-1">Availability</div>
                                <div className="text-lg font-medium text-gray-900">{teacher.availability}</div>
                            </div>
                        </div>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center gap-4">
                        <Link
                            href={`/student/teachers/${teacher.id}`}
                            className="text-[#2c7870] hover:text-[#236158] font-medium"
                        >
                            View Profile
                        </Link>
                        <button className="w-10 h-10 rounded-lg border-b-3 border-[#2c7870] flex items-center justify-center transition-colors cursor-pointer">
                            <MessageCircle className="w-5 h-5 text-[#2c7870]" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

export default function ClassDetails({ auth, booking, teacher }: Props) {
    const handleReschedule = () => {
        // TODO: Implement reschedule functionality
        console.log('Reschedule booking:', booking.id);
    };

    const handleCancelBooking = () => {
        // TODO: Implement cancel booking functionality
        console.log('Cancel booking:', booking.id);
    };

    return (
        <StudentLayout pageTitle="Class Details">
            <Head title="Class Details" />

            <div className="space-y-6">
                {/* Back Button */}
                <Link
                    href="/student/my-bookings"
                    className="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                >
                    <ArrowLeft className="w-4 h-4" />
                    <span>Back to My Bookings</span>
                </Link>

                {/* Class Details Section */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 mb-6">Class Details</h1>
                    <ClassDetailsCard booking={booking} />
                </div>

                {/* Teacher Details Section */}
                <div>
                    <h1 className="text-3xl font-bold text-gray-900 mb-6">Teacher Details</h1>
                    <TeacherDetailsCard teacher={teacher} />
                </div>

                {/* Action Buttons */}
                <div className="flex items-center gap-4 pt-6">
                    {booking.can_reschedule && (
                        <button
                            onClick={handleReschedule}
                            className="bg-[#2c7870] hover:bg-[#236158] text-white rounded-full px-8 py-3 font-medium transition-colors"
                        >
                            Reschedule
                        </button>
                    )}

                    {booking.can_cancel && (
                        <button
                            onClick={handleCancelBooking}
                            className="border border-[#2c7870] text-[#2c7870] hover:bg-[#2c7870] hover:text-white rounded-full px-8 py-3 font-medium transition-colors"
                        >
                            Cancel Booking
                        </button>
                    )}
                </div>
            </div>
        </StudentLayout>
    );
}
