import React from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { CheckCircle, RotateCcw, UserCheck, X, Clock } from 'lucide-react';
import { format } from 'date-fns';
import { DialogDescription } from '@radix-ui/react-dialog';

interface BookingDetailsModalProps {
    isOpen: boolean;
    onClose: () => void;
    booking: {
        id: number;
        booking_uuid: string;
        student: {
            name: string;
        };
        teacher: {
            name: string;
            is_available?: boolean;
        };
        subject: {
            template: {
                name: string;
            };
        };
        booking_date: string;
        start_time: string;
        duration_minutes: number;
        status: 'pending' | 'approved' | 'rejected' | 'upcoming' | 'completed' | 'missed' | 'cancelled';
        notes?: string;
    };
    onReschedule?: () => void;
    onReassign?: () => void;
    onCancel?: () => void;
}

export function BookingDetailsModal({
    isOpen,
    onClose,
    booking,
    onReschedule,
    onReassign,
    onCancel
}: BookingDetailsModalProps) {
    const formatTime = (time: string) => {
        return format(new Date(`2000-01-01T${time}`), 'h:mm a');
    };

    const formatDate = (date: string) => {
        return format(new Date(date), 'MMMM d, yyyy');
    };

    // Debug: Log booking data to see what's being passed
    // console.log('Booking data in modal:', booking);
    // console.log('Teacher availability:', booking.teacher.is_available);

    const getStatusConfig = (status: string) => {
        switch (status) {
            case 'confirmed':
            case 'approved':
                return {
                    label: 'Confirmed',
                    color: 'text-green-600',
                    icon: CheckCircle
                };
            case 'pending':
                return {
                    label: 'Pending',
                    color: 'text-yellow-600',
                    icon: Clock
                };
            case 'cancelled':
                return {
                    label: 'Cancelled',
                    color: 'text-red-600',
                    icon: X
                };
            case 'rejected':
                return {
                    label: 'Rejected',
                    color: 'text-red-600',
                    icon: X
                };
            case 'upcoming':
                return {
                    label: 'Upcoming',
                    color: 'text-blue-600',
                    icon: CheckCircle
                };
            case 'completed':
                return {
                    label: 'Completed',
                    color: 'text-green-600',
                    icon: CheckCircle
                };
            case 'missed':
                return {
                    label: 'Missed',
                    color: 'text-orange-600',
                    icon: X
                };
            default:
                return {
                    label: status,
                    color: 'text-gray-600',
                    icon: CheckCircle
                };
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="max-w-md mx-auto p-0">
                <DialogDescription className="sr-only">Booking Details View</DialogDescription>
                <DialogHeader className="px-6 py-4 border-b border-gray-200">
                    <DialogTitle className="text-xl font-semibold text-gray-900 text-center">
                        Booking Details View
                    </DialogTitle>
                </DialogHeader>

                <div className="px-6 py-4 space-y-3">
                    {/* Booking ID */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Booking ID:</span>
                        <span className="text-sm text-gray-900 font-mono ml-12">BK-{booking.booking_uuid.slice(-6).toUpperCase()}</span>
                    </div>

                    {/* Student */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Student:</span>
                        <span className="text-sm text-gray-900 ml-12">{booking.student.name}</span>
                    </div>

                    {/* Teacher */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Teacher:</span>
                        <div className="flex items-center gap-2">
                            <span className="text-sm text-gray-900 ml-12">{booking.teacher.name}</span>
                            {(() => {
                                // Check if teacher is available (not in holiday mode)
                                const isAvailable = booking.teacher.is_available !== false;
                                
                                // If availability data is not loaded, show unknown status
                                if (booking.teacher.is_available === undefined) {
                                    return (
                                        <span className="text-xs text-gray-500 bg-gray-50 px-2 py-1 rounded-full">status unknown</span>
                                    );
                                }
                                
                                return isAvailable ? (
                                    <span className="text-xs text-blue-600 bg-blue-50 px-2 py-1 rounded-full">available</span>
                                ) : (
                                    <span className="text-xs text-green-600 bg-green-50 px-2 py-1 rounded-full">unavailable</span>
                                );
                            })()}
                        </div>
                    </div>

                    {/* Subject */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Subject:</span>
                        <span className="text-sm text-gray-900 ml-12">{booking.subject.template.name}</span>
                    </div>

                    {/* Booking Date & Time */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600 ">Booking Date & Time:</span>
                        <span className="text-sm text-gray-900 ml-8">
                            {formatDate(booking.booking_date)} - {formatTime(booking.start_time)} ml-12
                        </span>
                    </div>

                    {/* Duration */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Duration:</span>
                        <span className="text-sm text-gray-900 ml-12">{booking.duration_minutes} minutes</span>
                    </div>

                    {/* Status */}
                    <div className="flex justify-start items-center">
                        <span className="text-sm font-medium text-gray-600">Status:</span>
                        <div className="flex items-center gap-2">
                            {(() => {
                                const statusConfig = getStatusConfig(booking.status);
                                const StatusIcon = statusConfig.icon;
                                return (
                                    <>
                                        <StatusIcon className={`h-4 w-4 ${statusConfig.color}`} />
                                        <span className="text-sm text-gray-900 ml-12">{statusConfig.label}</span>
                                    </>
                                );
                            })()}
                        </div>
                    </div>

                    {/* Notes Section */}
                    {booking.notes && (
                        <div className="pt-4">
                            <span className="text-sm font-medium text-gray-600 block mb-2">Notes</span>
                            <div className="bg-gray-50 border border-gray-200 rounded-lg p-3 min-h-[60px]">
                                <span className="text-sm text-gray-700">
                                    {booking.notes}
                                </span>
                            </div>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="pt-6 space-y-3 justify-start space-x-4">
                        {/* Reschedule Button */}
                        <Button 
                            onClick={onReschedule}
                            className=" bg-[#338078] hover:bg-[#236158] text-white font-medium py-3 rounded-full"
                        >
                            Reschedule
                        </Button>

                        {/* Reassign Teacher Button */}
                        <Button 
                            onClick={onReassign}
                            variant="outline"
                            className=" border-[#338078] text-[#338078] hover:bg-green-50 font-medium py-3 rounded-full"
                        >
                            Reassign Teacher
                        </Button>

                        {/* Cancel Booking Button */}
                        <button 
                            onClick={onCancel}
                            className=" text-red-600 hover:text-red-700 font-medium py-3 text-sm"
                        >
                            Cancel Booking
                        </button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
