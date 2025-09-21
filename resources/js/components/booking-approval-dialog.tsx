import React from 'react';
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
import { Calendar, CheckCircle } from 'lucide-react';

interface BookingApprovalDialogProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: () => void;
    onOpenChange?: (open: boolean) => void;
    booking: {
        id: number;
        student: {
            name: string;
        };
        teacher: {
            name: string;
        };
        booking_date: string;
        start_time: string;
        subject: {
            template?: {
                name: string;
            };
        };
    };
}

export function BookingApprovalDialog({ 
    isOpen, 
    onClose, 
    onConfirm, 
    onOpenChange,
    booking 
}: BookingApprovalDialogProps) {
    const formatDate = (date: string) => {
        return new Date(date).toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
    };

    const formatTime = (time: string) => {
        return new Date(`2000-01-01T${time}`).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    };

    const handleClose = () => {
        onClose();
    };

    return (
        <AlertDialog open={isOpen} onOpenChange={onOpenChange || onClose}>
            <AlertDialogContent className="max-w-lg">
                <AlertDialogHeader className="text-center items-center">
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center border-2 border-green-200">
                            <CheckCircle className="w-8 h-8 text-green-600" />
                        </div>
                    </div>
                    <AlertDialogTitle className="text-xl font-semibold text-gray-800">
                        Are you sure want to approve this session?
                    </AlertDialogTitle>
                </AlertDialogHeader>

                <AlertDialogDescription className="text-center">
                    <div className="bg-gray-50 rounded-lg p-4 mt-4">
                        <div className="flex items-center space-x-4">
                            {/* Placeholder for session image */}
                            <div className="w-20 h-20 bg-gradient-to-br from-amber-100 to-amber-200 rounded-lg flex items-center justify-center">
                                <div className="text-2xl">📖</div>
                            </div>

                            <div className="flex-1 space-y-2">
                                <div className="flex items-center">
                                    <span className="text-sm text-gray-500">Student:</span>
                                    <span className="text-sm font-medium text-gray-800 ml-2">
                                        {booking.student.name}
                                    </span>
                                </div>

                                <div className="flex items-center">
                                    <span className="text-sm text-gray-500">Teacher:</span>
                                    <span className="text-sm font-medium text-gray-800 ml-2">
                                        {booking.teacher.name}
                                    </span>
                                </div>

                                <div className="flex items-center">
                                    <Calendar className="w-4 h-4 text-gray-500 mr-1" />
                                    <span className="text-sm text-gray-500">Date & Time:</span>
                                    <div className="text-sm text-teal-600 font-medium ml-2 bg-[#FFF9E9] px-2 py-1 rounded-lg">
                                        {formatDate(booking.booking_date)}
                                        <span className="text-sm text-teal-500 ml-2 bg-[#FFF9E9] px-2 py-1 rounded-lg">
                                            {formatTime(booking.start_time)}
                                        </span>
                                    </div>
                                </div>

                                <div className="flex items-center">
                                    <span className="text-sm text-gray-500">Subject:</span>
                                    <span className="text-sm text-teal-600 font-medium ml-2 bg-[#FFF9E9] px-2 py-1 rounded-lg">
                                        {booking.subject.template?.name || 'Unknown Subject'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </AlertDialogDescription>

                <AlertDialogFooter className="gap-3 mt-6 items-center justify-center">
                    <AlertDialogAction
                        onClick={onConfirm}
                        className="bg-teal-600 hover:bg-teal-700 text-white rounded-full px-6 py-2 cursor-pointer"
                    >
                        Yes, I'm sure
                    </AlertDialogAction>
                    <AlertDialogCancel
                        onClick={handleClose}
                        className="bg-white border border-teal-600 text-teal-600 hover:bg-teal-50 rounded-full px-6 py-2 cursor-pointer"
                    >
                        No, cancel
                    </AlertDialogCancel>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
