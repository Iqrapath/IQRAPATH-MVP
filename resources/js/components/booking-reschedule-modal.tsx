import React, { useState, useEffect } from 'react';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Calendar, Clock, Loader2 } from 'lucide-react';
import { format, addDays } from 'date-fns';
import { router } from '@inertiajs/react';

interface TimeSlot {
    value: string;
    label: string;
    start_time: string;
    end_time: string;
}

interface DateOption {
    value: string;
    label: string;
    day_name?: string;
    has_conflicts?: boolean;
}

interface BookingRescheduleModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirm: (data: {
        newDate: string;
        newTime: string;
        notifyParties: boolean;
        reason: string;
    }) => void;
    booking: {
        id: number;
        booking_date: string;
        start_time: string;
        end_time: string;
        student: {
            name: string;
        };
        teacher: {
            name: string;
        };
        subject: {
            template: {
                name: string;
            };
        };
    };
}

export function BookingRescheduleModal({
    isOpen,
    onClose,
    onConfirm,
    booking
}: BookingRescheduleModalProps) {
    const [newDate, setNewDate] = useState('');
    const [newTime, setNewTime] = useState('');
    const [notifyParties, setNotifyParties] = useState(true);
    const [reason, setReason] = useState('');
    const [availableSlots, setAvailableSlots] = useState<TimeSlot[]>([]);
    const [loadingSlots, setLoadingSlots] = useState(false);
    const [dateOptions, setDateOptions] = useState<DateOption[]>([]);
    const [loadingDays, setLoadingDays] = useState(false);
    const [teacherName, setTeacherName] = useState('');

    // Fallback date generation (if API fails)
    const generateFallbackDateOptions = (): DateOption[] => {
        const options: DateOption[] = [];
        const today = new Date();
        
        for (let i = 1; i <= 30; i++) {
            const date = addDays(today, i);
            const value = format(date, 'yyyy-MM-dd');
            const label = format(date, 'MMMM d, yyyy');
            options.push({ value, label });
        }
        
        return options;
    };

    // Fetch available days from backend
    const fetchAvailableDays = async () => {
        setLoadingDays(true);
        try {
            const response = await fetch(route('admin.bookings.available-days', booking.id));
            const data = await response.json();
            
            if (data.available_days) {
                setDateOptions(data.available_days);
                setTeacherName(data.teacher_name || '');
            }
        } catch (error) {
            console.error('Error fetching available days:', error);
            // Fallback to generating basic date options
            setDateOptions(generateFallbackDateOptions());
        } finally {
            setLoadingDays(false);
        }
    };

    // Fetch available time slots when date changes
    const fetchAvailableSlots = async (date: string) => {
        if (!date) return;
        
        setLoadingSlots(true);
        setAvailableSlots([]);
        setNewTime(''); // Reset time selection
        
        try {
            const response = await fetch(route('admin.bookings.available-slots', booking.id) + `?date=${date}`);
            const data = await response.json();
            
            if (data.available_slots) {
                setAvailableSlots(data.available_slots);
            }
        } catch (error) {
            console.error('Error fetching available slots:', error);
        } finally {
            setLoadingSlots(false);
        }
    };

    // Fetch available days when modal opens
    useEffect(() => {
        if (isOpen) {
            fetchAvailableDays();
        }
    }, [isOpen]);

    // Fetch slots when date changes
    useEffect(() => {
        if (newDate) {
            fetchAvailableSlots(newDate);
        }
    }, [newDate]);

    const handleConfirm = () => {
        if (!newDate || !newTime) {
            return;
        }

        onConfirm({
            newDate,
            newTime,
            notifyParties,
            reason: reason.trim()
        });
    };

    const handleClose = () => {
        // Reset form
        setNewDate('');
        setNewTime('');
        setNotifyParties(true);
        setReason('');
        setAvailableSlots([]);
        setLoadingSlots(false);
        setDateOptions([]);
        setLoadingDays(false);
        setTeacherName('');
        onClose();
    };

    // dateOptions is now managed by state

    return (
        <Dialog open={isOpen} onOpenChange={handleClose}>
            <DialogContent className="max-w-md mx-auto">
                <DialogHeader className="text-center pb-4">
                    <DialogDescription className="sr-only">Reschedule Booking</DialogDescription>
                    <DialogTitle className="text-xl font-semibold text-gray-900">
                        Reschedule Booking
                    </DialogTitle>
                </DialogHeader>

                <div className="space-y-6">
                    {/* Teacher Info */}
                    {teacherName && (
                        <div className="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p className="text-sm text-blue-800">
                                <strong>Teacher:</strong> {teacherName}
                            </p>
                            <p className="text-xs text-blue-600 mt-1">
                                Only available days and times are shown below
                            </p>
                        </div>
                    )}

                    {/* New Date and Time */}
                    <div className="grid grid-cols-2 gap-4">
                        <div className="space-y-2">
                            <Label htmlFor="newDate" className="text-sm font-medium text-gray-700">
                                New Date
                            </Label>
                            <Select value={newDate} onValueChange={setNewDate} disabled={loadingDays}>
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder={loadingDays ? "Loading available dates..." : "Select New Date"} />
                                </SelectTrigger>
                                <SelectContent>
                                    {loadingDays ? (
                                        <SelectItem value="loading" disabled>
                                            Loading available dates...
                                        </SelectItem>
                                    ) : dateOptions.length > 0 ? (
                                        dateOptions.map((option) => (
                                            <SelectItem key={option.value} value={option.value}>
                                                {option.label}
                                                {option.has_conflicts && (
                                                    <span className="text-xs text-orange-600 ml-2">(Limited availability)</span>
                                                )}
                                            </SelectItem>
                                        ))
                                    ) : (
                                        <SelectItem value="no-dates" disabled>
                                            No available dates found
                                        </SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                            {loadingDays && (
                                <div className="flex items-center gap-2 text-sm text-gray-500">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Checking teacher availability...
                                </div>
                            )}
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="newTime" className="text-sm font-medium text-gray-700">
                                New Time
                            </Label>
                            <Select value={newTime} onValueChange={setNewTime} disabled={!newDate || loadingSlots}>
                                <SelectTrigger className="w-full">
                                    <SelectValue placeholder={loadingSlots ? "Loading available times..." : "Select New Time"} />
                                </SelectTrigger>
                                <SelectContent>
                                    {availableSlots.length > 0 ? (
                                        availableSlots.map((slot) => (
                                            <SelectItem key={slot.value} value={slot.value}>
                                                {slot.label}
                                            </SelectItem>
                                        ))
                                    ) : (
                                        <SelectItem value="no-slots" disabled>
                                            {loadingSlots ? "Loading..." : "No available times for this date"}
                                        </SelectItem>
                                    )}
                                </SelectContent>
                            </Select>
                            {loadingSlots && (
                                <div className="flex items-center gap-2 text-sm text-gray-500">
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Loading available time slots...
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Notify Both Parties */}
                    <div className="flex items-center justify-start">
                        <Label htmlFor="notifyParties" className="text-sm font-medium text-gray-700">
                            Notify Both Parties
                        </Label>
                        <Switch
                            id="notifyParties"
                            checked={notifyParties}
                            onCheckedChange={setNotifyParties}
                            className="ml-4"
                        />
                    </div>

                    {/* Reason for Reschedule */}
                    <div className="space-y-2">
                        <Label htmlFor="reason" className="text-sm font-medium text-gray-700">
                            Reason for reschedule
                        </Label>
                        <Textarea
                            id="reason"
                            placeholder="Write your reason..."
                            value={reason}
                            onChange={(e) => setReason(e.target.value)}
                            className="min-h-[100px] resize-none"
                        />
                    </div>

                    {/* Action Buttons */}
                    <div className="flex gap-3 pt-4">
                        <Button
                            onClick={handleConfirm}
                            disabled={!newDate || !newTime}
                            className="flex-1 bg-teal-600 hover:bg-teal-700 text-white font-medium py-3 rounded-lg"
                        >
                            Confirm Reschedule
                        </Button>
                        <Button
                            onClick={handleClose}
                            variant="outline"
                            className="text-red-600 border-red-600 hover:bg-red-50 font-medium py-3 px-6 rounded-lg"
                        >
                            Cancel Booking
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
