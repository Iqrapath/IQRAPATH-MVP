import React, { useState } from 'react';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Calendar, XCircle } from 'lucide-react';

interface BookingCancelDialogProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: (reason: string, notifyParties: boolean) => void;
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

export function BookingCancelDialog({ 
    isOpen, 
    onClose, 
    onConfirm, 
    onOpenChange,
    booking 
}: BookingCancelDialogProps) {
    const [reason, setReason] = useState('Teacher has an emergency. Please rebook with a new time.');
    const [notifyParties, setNotifyParties] = useState(true);

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

    const handleConfirm = () => {
        onConfirm(reason, notifyParties);
    };

    const handleClose = () => {
        // Reset form state when closing
        setReason('Teacher has an emergency. Please rebook with a new time.');
        setNotifyParties(true);
        onClose();
    };

    return (
        <AlertDialog open={isOpen} onOpenChange={onOpenChange || onClose}>
            <AlertDialogContent className="max-w-md">
                <AlertDialogHeader className="text-center">
                    <div className="flex justify-center mb-4">
                        <div className="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center border-2 border-red-200">
                            <XCircle className="w-8 h-8 text-red-600" />
                        </div>
                    </div>
                    <AlertDialogTitle className="text-xl font-semibold text-gray-800">
                        Are you sure want to cancel this session ?
                    </AlertDialogTitle>
                </AlertDialogHeader>

                <AlertDialogDescription className="text-sm text-muted-foreground mb-4">
                    Please provide a reason for cancelling this session.
                </AlertDialogDescription>

                <div className="space-y-4">
                    {/* Reason for Cancellation */}
                    <div className="space-y-2">
                        <Label htmlFor="reason" className="text-sm font-medium text-gray-800">
                            Reason for Cancellation
                        </Label>
                        <Input
                            id="reason"
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            placeholder="Teacher has an emergency. Please rebook with a new time."
                            className="w-full"
                        />
                    </div>

                    {/* Notify Student/Teacher */}
                    <div className="flex items-center justify-between">
                        <Label htmlFor="notify" className="text-sm font-medium text-gray-800">
                            Notify Student/Teacher:
                        </Label>
                        <Switch
                            id="notify"
                            checked={notifyParties}
                            onCheckedChange={setNotifyParties}
                        />
                    </div>
                </div>

                <AlertDialogFooter className="flex gap-3 mt-6 items-center justify-center">
                    <AlertDialogAction
                        onClick={handleConfirm}
                        className="bg-teal-600 hover:bg-teal-700 text-white rounded-full px-6 py-2"
                    >
                        Yes, I'm sure
                    </AlertDialogAction>
                    <AlertDialogCancel
                        onClick={handleClose}
                        className="bg-white border border-teal-600 text-teal-600 hover:bg-teal-50 rounded-full px-6 py-2"
                    >
                        No, cancel
                    </AlertDialogCancel>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
