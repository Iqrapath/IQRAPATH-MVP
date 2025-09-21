import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, BookOpen, User, Loader2 } from 'lucide-react';
import { format } from 'date-fns';
import { router } from '@inertiajs/react';

interface Teacher {
    id: number;
    name: string;
    email: string;
    is_available?: boolean;
}

interface BookingReassignModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: (data: {
        newTeacherId: number;
        notifyParties: boolean;
        adminNote: string;
    }) => void;
    onCancelBooking: () => void;
    booking: {
        id: number;
        booking_uuid: string;
        student: {
            name: string;
        };
        teacher: {
            id: number;
            name: string;
        };
        subject: {
            template: {
                name: string;
            };
        };
        booking_date: string;
        start_time: string;
    };
}

export function BookingReassignModal({
    isOpen,
    onClose,
    onConfirm,
    onCancelBooking,
    booking
}: BookingReassignModalProps) {
    const [selectedTeacherId, setSelectedTeacherId] = useState<string>('');
    const [notifyParties, setNotifyParties] = useState(true);
    const [adminNote, setAdminNote] = useState('');
    const [availableTeachers, setAvailableTeachers] = useState<Teacher[]>([]);
    const [loadingTeachers, setLoadingTeachers] = useState(false);

    // Fetch available teachers when modal opens
    const fetchAvailableTeachers = async () => {
        setLoadingTeachers(true);
        try {
            const response = await fetch(route('admin.bookings.available-teachers', booking.id));
            const data = await response.json();
            
            if (data.teachers) {
                setAvailableTeachers(data.teachers);
            }
        } catch (error) {
            console.error('Error fetching available teachers:', error);
        } finally {
            setLoadingTeachers(false);
        }
    };

    useEffect(() => {
        if (isOpen) {
            fetchAvailableTeachers();
        }
    }, [isOpen]);

    const handleConfirm = () => {
        if (!selectedTeacherId) {
            return;
        }

        onConfirm({
            newTeacherId: parseInt(selectedTeacherId),
            notifyParties,
            adminNote: adminNote.trim()
        });
    };

    const handleClose = () => {
        // Reset form
        setSelectedTeacherId('');
        setNotifyParties(true);
        setAdminNote('');
        setAvailableTeachers([]);
        setLoadingTeachers(false);
        onClose();
    };

    const formatTime = (time: string) => {
        return format(new Date(`2000-01-01T${time}`), 'h:mm a');
    };

    const formatDate = (date: string) => {
        return format(new Date(date), 'MMMM d, yyyy');
    };

    const isConfirmDisabled = !selectedTeacherId || loadingTeachers;

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-w-xl mx-auto p-0 rounded-xl">
                <DialogHeader className="px-6 py-5 border-b border-gray-200">
                    <DialogDescription className="sr-only">Reassign This Session to a Different Teacher</DialogDescription>
                    <DialogTitle className="text-xl font-semibold text-gray-900 text-center">
                        Reassign This Session to a Different Teacher
                    </DialogTitle>
                </DialogHeader>

                <div className="px-6 py-5 space-y-5">
                    {/* Session Details Card */}
                    <div className="bg-gray-50 rounded-lg p-4 flex items-start gap-4">
                        {/* Book Icon */}
                        <div className="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <BookOpen className="h-6 w-6 text-amber-600" />
                        </div>
                        
                        {/* Session Info */}
                        <div className="flex-1 space-y-2">
                            <div className="flex items-center gap-2">
                                <User className="h-4 w-4 text-gray-500" />
                                <span className="text-sm text-gray-600">Student:</span>
                                <span className="text-sm font-medium text-gray-900">{booking.student.name}</span>
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <User className="h-4 w-4 text-gray-500" />
                                <span className="text-sm text-gray-600">Current Teacher:</span>
                                <span className="text-sm font-medium text-gray-900">{booking.teacher.name}</span>
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <BookOpen className="h-4 w-4 text-gray-500" />
                                <span className="text-sm text-gray-600">Subject:</span>
                                <span className="text-sm font-medium text-gray-900 bg-yellow-100 px-2 py-1 rounded">
                                    {booking.subject.template.name}
                                </span>
                            </div>
                            
                            <div className="flex items-center gap-2">
                                <Calendar className="h-4 w-4 text-gray-500" />
                                <span className="text-sm text-gray-600">Session Date:</span>
                                <span className="text-sm font-medium text-gray-900 bg-yellow-100 px-2 py-1 rounded">
                                    {formatDate(booking.booking_date)}
                                </span>
                                <span className="text-sm text-gray-500">|</span>
                                <span className="text-sm font-medium text-gray-900 bg-yellow-100 px-2 py-1 rounded">
                                    {formatTime(booking.start_time)}
                                </span>
                            </div>
                        </div>
                    </div>

                    {/* Choose Teacher Section */}
                    <div className="space-y-2">
                        <Label className="text-sm font-medium text-gray-700">
                            Choose a teacher
                        </Label>
                        <Select value={selectedTeacherId} onValueChange={setSelectedTeacherId} disabled={loadingTeachers}>
                            <SelectTrigger className="w-full">
                                <SelectValue placeholder={loadingTeachers ? "Loading teachers..." : "Select a teacher"} />
                            </SelectTrigger>
                            <SelectContent>
                                {availableTeachers.length > 0 ? (
                                    availableTeachers.map((teacher) => (
                                        <SelectItem key={teacher.id} value={teacher.id.toString()}>
                                            <div className="flex items-center gap-2">
                                                <span>{teacher.name}</span>
                                                {teacher.is_available !== undefined && (
                                                    <span className={`text-xs px-2 py-1 rounded-full ${
                                                        teacher.is_available 
                                                            ? 'bg-green-100 text-green-600' 
                                                            : 'bg-red-100 text-red-600'
                                                    }`}>
                                                        {teacher.is_available ? 'Available' : 'Unavailable'}
                                                    </span>
                                                )}
                                            </div>
                                        </SelectItem>
                                    ))
                                ) : (
                                    <SelectItem value="no-teachers" disabled>
                                        {loadingTeachers ? "Loading..." : "No available teachers"}
                                    </SelectItem>
                                )}
                            </SelectContent>
                        </Select>
                        {loadingTeachers && (
                            <div className="flex items-center gap-2 text-sm text-gray-500">
                                <Loader2 className="h-4 w-4 animate-spin" />
                                Loading available teachers...
                            </div>
                        )}
                        <div className="text-right">
                            <button className="text-sm text-green-600 hover:text-green-700 font-medium">
                                Assign New Teacher
                            </button>
                        </div>
                    </div>

                    {/* Admin Note Section */}
                    <div className="space-y-2">
                        <Label htmlFor="adminNote" className="text-sm font-medium text-gray-700">
                            Admin Note (Optional)
                        </Label>
                        <Textarea
                            id="adminNote"
                            value={adminNote}
                            onChange={(e) => setAdminNote(e.target.value)}
                            placeholder="Write any note to the teacher here..."
                            className="min-h-[80px] rounded-lg bg-gray-50 border-gray-200"
                        />
                    </div>

                    {/* Notify Both Parties */}
                    <div className="flex items-center justify-between pt-2">
                        <Label htmlFor="notifyParties" className="text-sm font-medium text-gray-700">
                            Notify Both Parties
                        </Label>
                        <Switch
                            id="notifyParties"
                            checked={notifyParties}
                            onCheckedChange={setNotifyParties}
                            className="data-[state=checked]:bg-teal-600"
                        />
                    </div>

                    {/* Action Buttons */}
                    <div className="pt-4 space-y-3">
                        <Button
                            onClick={handleConfirm}
                            disabled={isConfirmDisabled}
                            className="bg-[#338078] hover:bg-[#236158] text-white font-medium rounded-full"
                        >
                            Reassign Teacher
                        </Button>
                        <button
                            onClick={onCancelBooking}
                            className="ml-4 text-red-600 hover:text-red-700 font-medium text-sm text-center"
                        >
                            Cancel Booking
                        </button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
