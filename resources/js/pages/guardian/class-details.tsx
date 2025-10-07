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
import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import GuardianLayout from '@/layouts/guardian/guardian-layout';
import { toast } from 'sonner';
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
import TeacherProfileModal from '@/components/common/TeacherProfileModal';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Props extends PageProps, ClassDetailsPageProps { }

function ClassDetailsCard({ booking }: { booking: Props['booking'] }) {
    const getStatusBadgeColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'confirmed':
            case 'approved':
                return 'bg-green-100 text-green-800';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    return (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-start gap-4">
                {/* Subject Icon */}
                <div className="w-16 h-16 bg-teal-600 rounded-xl flex items-center justify-center flex-shrink-0">
                    <Users className="w-8 h-8 text-white" />
                </div>

                <div className="flex-1">
                    <div className="flex items-center justify-between mb-2">
                        <h2 className="text-xl font-semibold text-gray-900">{booking.title}</h2>
                        <span className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusBadgeColor(booking.status)}`}>
                            {booking.status}
                        </span>
                    </div>

                    <div className="flex items-center gap-4 text-sm text-gray-600 mb-4">
                        <div className="flex items-center gap-1">
                            <User className="w-4 h-4" />
                            <span>Ustadh {booking.teacher}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Calendar className="w-4 h-4" />
                            <span>{booking.date}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Clock className="w-4 h-4" />
                            <span>{booking.time}</span>
                        </div>
                    </div>

                    {/* Mode Section */}
                    <div className="flex items-center gap-4">
                        <div className="flex items-center gap-2">
                            <Video className="w-5 h-5 text-teal-600" />
                            <span className="text-sm font-medium text-gray-700">Virtual Class</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <ZoomIcon className="w-5 h-5" />
                            <span className="text-sm text-gray-600">Zoom</span>
                        </div>
                        <div className="flex items-center gap-2">
                            <GoogleMeetIcon className="w-5 h-5" />
                            <span className="text-sm text-gray-600">Google Meet</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function TeacherDetailsCard({ teacher }: { teacher: Props['teacher'] }) {
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);

    return (
        <div className="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div className="flex items-start gap-4">
                <Avatar className="w-16 h-16">
                    <AvatarImage src={teacher.avatar} alt={teacher.name} />
                    <AvatarFallback className="bg-teal-100 text-teal-600 text-lg font-semibold">
                        {teacher.name.split(' ').map(n => n[0]).join('').toUpperCase()}
                    </AvatarFallback>
                </Avatar>

                <div className="flex-1">
                    <div className="flex items-center gap-2 mb-2">
                        <h3 className="text-lg font-semibold text-gray-900">{teacher.name}</h3>
                        {teacher.is_verified && (
                            <VerifiedIcon className="w-5 h-5 text-teal-600" />
                        )}
                    </div>

                    <div className="flex items-center gap-4 text-sm text-gray-600 mb-3">
                        <div className="flex items-center gap-1">
                            <MapPin className="w-4 h-4" />
                            <span>{teacher.location}</span>
                        </div>
                        <div className="flex items-center gap-1">
                            <Star className="w-4 h-4 text-yellow-500" />
                            <span>{teacher.rating}/5</span>
                        </div>
                    </div>

                    <p className="text-sm text-gray-600 mb-4">{teacher.specialization}</p>

                    <div className="flex items-center gap-3">
                        <button
                            onClick={() => setIsProfileModalOpen(true)}
                            className="flex items-center gap-2 px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-full text-sm font-medium transition-colors"
                        >
                            <MessageCircle className="w-4 h-4" />
                            View Profile
                        </button>
                    </div>
                </div>
            </div>

            <TeacherProfileModal
                isOpen={isProfileModalOpen}
                onClose={() => setIsProfileModalOpen(false)}
                teacher={teacher}
            />
        </div>
    );
}

export default function ClassDetails({ auth, booking, teacher }: Props) {
    const [isCancelDialogOpen, setIsCancelDialogOpen] = useState(false);

    const handleReschedule = () => {
        router.visit('/guardian/reschedule/class', {
            method: 'post',
            data: {
                booking_id: booking.id,
                teacher_id: teacher.id
            }
        });
    };

    const handleCancel = () => {
        setIsCancelDialogOpen(true);
    };

    const confirmCancelBooking = () => {
        router.post(`/guardian/my-bookings/${booking.id}/cancel`, {}, {
            onSuccess: () => {
                toast.success('Booking cancelled successfully');
                setIsCancelDialogOpen(false);
                router.visit('/guardian/my-bookings');
            },
            onError: (errors) => {
                if (errors.error) {
                    toast.error(errors.error);
                } else {
                    toast.error('Failed to cancel booking');
                }
            }
        });
    };

    return (
        <GuardianLayout pageTitle="Class Details">
            <Head title="Class Details" />

            <div className="max-w-4xl mx-auto p-6 space-y-6">
                {/* Header */}
                <div className="flex items-center gap-4 mb-6">
                    <button
                        onClick={() => router.visit('/guardian/my-bookings')}
                        className="flex items-center gap-2 text-gray-600 hover:text-gray-900 transition-colors"
                    >
                        <ArrowLeft className="w-5 h-5" />
                        <span>Back to My Bookings</span>
                    </button>
                </div>

                {/* Class Details Card */}
                <ClassDetailsCard booking={booking} />

                {/* Teacher Details Card */}
                <TeacherDetailsCard teacher={teacher} />

                {/* Action Buttons */}
                <div className="flex items-center gap-4">
                    <button
                        onClick={handleReschedule}
                        className="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-3 px-6 rounded-full font-medium transition-colors"
                    >
                        Reschedule Class
                    </button>
                    <button
                        onClick={handleCancel}
                        className="flex-1 border border-teal-600 text-teal-600 hover:bg-teal-50 py-3 px-6 rounded-full font-medium transition-colors"
                    >
                        Cancel Booking
                    </button>
                </div>
            </div>

            {/* Cancel Booking Confirmation Dialog */}
            <AlertDialog open={isCancelDialogOpen} onOpenChange={setIsCancelDialogOpen}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Cancel Booking</AlertDialogTitle>
                        <AlertDialogDescription>
                            Are you sure you want to cancel this booking? This action cannot be undone.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Keep Booking</AlertDialogCancel>
                        <AlertDialogAction
                            onClick={confirmCancelBooking}
                            className="bg-red-600 hover:bg-red-700"
                        >
                            Cancel Booking
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </GuardianLayout>
    );
}
