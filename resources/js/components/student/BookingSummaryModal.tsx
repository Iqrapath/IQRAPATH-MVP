/**
 * 🎨 FIGMA REFERENCE
 * Booking Summary Modal
 * 
 * EXACT SPECS FROM FIGMA:
 * - Teacher profile with avatar and name
 * - Subject details
 * - Date & Time information
 * - Total fee display
 * - Notes section
 * - Cancellation policy
 * - Cancel and Confirm & Pay buttons
 */

import React from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Calendar, Clock, BookOpen, DollarSign, FileText, AlertCircle } from 'lucide-react';

interface Teacher {
    id: number;
    name: string;
    avatar?: string;
}

interface Subject {
    id: number;
    name: string;
}

interface BookingSummaryModalProps {
    isOpen: boolean;
    onClose: () => void;
    onConfirmPayment: () => void;
    teacher: Teacher;
    subject: Subject;
    date: string;
    time: string;
    totalFee: number;
    currency: string;
    notes?: string;
    isProcessing?: boolean;
}

export default function BookingSummaryModal({
    isOpen,
    onClose,
    onConfirmPayment,
    teacher,
    subject,
    date,
    time,
    totalFee,
    currency = '₦',
    notes,
    isProcessing = false
}: BookingSummaryModalProps) {
    if (!isOpen) return null;

    const formatAmount = (amount: number): string => {
        return amount.toLocaleString();
    };

    const getTeacherInitials = (name: string): string => {
        return name.split(' ').map(n => n[0]).join('').toUpperCase();
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4">
            <div className="bg-white rounded-3xl p-6 max-w-md w-full shadow-2xl">
                {/* Header */}
                <div className="mb-6">
                    <h2 className="text-xl font-semibold text-gray-900">
                        Booking Summary
                    </h2>
                </div>

                {/* Teacher Info */}
                <div className="flex items-center gap-3 mb-6">
                    <Avatar className="w-12 h-12">
                        <AvatarImage src={teacher.avatar} alt={teacher.name} />
                        <AvatarFallback className="bg-teal-100 text-teal-700 font-medium">
                            {getTeacherInitials(teacher.name)}
                        </AvatarFallback>
                    </Avatar>
                    <div>
                        <p className="text-sm text-gray-500">Teacher:</p>
                        <p className="font-medium text-gray-900">{teacher.name}</p>
                    </div>
                </div>

                {/* Subject */}
                <div className="flex items-center gap-3 mb-4">
                    <BookOpen className="w-5 h-5 text-gray-400" />
                    <div>
                        <p className="text-sm text-gray-500">Subject:</p>
                        <p className="font-medium text-gray-900">{subject.name}</p>
                    </div>
                </div>

                {/* Date & Time */}
                <div className="flex items-center gap-6 mb-4">
                    <div className="flex items-center gap-2">
                        <Calendar className="w-5 h-5 text-gray-400" />
                        <div>
                            <p className="text-sm text-gray-500">Date & Time</p>
                            <p className="font-medium text-gray-900">{date}</p>
                        </div>
                    </div>
                    <div className="flex items-center gap-2">
                        <Clock className="w-5 h-5 text-gray-400" />
                        <div>
                            <p className="font-medium text-gray-900">{time}</p>
                        </div>
                    </div>
                </div>

                {/* Total Fee */}
                <div className="flex items-center justify-between py-4 border-t border-gray-100 mb-4">
                    <div className="flex items-center gap-2">
                        <DollarSign className="w-5 h-5 text-amber-500" />
                        <span className="font-medium text-gray-900">Total Fee</span>
                    </div>
                    <div className="text-right">
                        <p className="text-lg font-bold text-gray-900">
                            {currency}{formatAmount(totalFee)}
                        </p>
                        {currency === '₦' && (
                            <p className="text-sm text-gray-500">
                                / {currency}{formatAmount(Math.round(totalFee * 0.8))}
                            </p>
                        )}
                    </div>
                </div>

                {/* Notes */}
                {notes && (
                    <div className="mb-4">
                        <div className="flex items-start gap-2">
                            <FileText className="w-5 h-5 text-gray-400 mt-0.5" />
                            <div className="flex-1">
                                <p className="text-sm text-gray-500 mb-1">Notes from you:</p>
                                <p className="text-gray-700 text-sm">{notes}</p>
                            </div>
                        </div>
                    </div>
                )}

                {/* Cancellation Policy */}
                <div className="bg-red-50 rounded-lg p-3 mb-6">
                    <div className="flex items-start gap-2">
                        <AlertCircle className="w-4 h-4 text-red-500 mt-0.5 flex-shrink-0" />
                        <div>
                            <p className="text-xs text-red-600 font-medium">Note:</p>
                            <p className="text-xs text-red-600">
                                Cancellation allowed 12 hours before session.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex gap-3">
                    <Button
                        onClick={onClose}
                        variant="outline"
                        className="flex-1 py-3 rounded-full border-gray-300 text-gray-700 hover:bg-gray-50"
                        disabled={isProcessing}
                    >
                        Cancel
                    </Button>
                    <Button
                        onClick={onConfirmPayment}
                        className="flex-1 bg-teal-600 hover:bg-teal-700 text-white py-3 rounded-full"
                        disabled={isProcessing}
                    >
                        {isProcessing ? 'Processing...' : 'Confirm & Pay'}
                    </Button>
                </div>
            </div>
        </div>
    );
}
