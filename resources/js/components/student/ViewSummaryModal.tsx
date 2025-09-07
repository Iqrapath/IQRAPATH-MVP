/**
 * 🎨 DESIGN REFERENCE
 * 
 * Component: ViewSummaryModal
 * Image: Provided class summary modal design
 * 
 * 📏 EXACT SPECIFICATIONS FROM IMAGE:
 * - Modal background: White with rounded corners
 * - Header: Class title with "Confirmed" status badge
 * - Class info: Book icon, title, teacher name, duration
 * - Date/Time: Yellow background highlight
 * - Mode: Zoom and Google Meet icons
 * - Teacher Notes: Teal color label
 * - Student Notes: Interactive link text
 * - Rate & Review: 5 yellow stars with feedback textarea
 * - Action buttons: "Rebook Class" (teal) and "Download Summary PDF" (outlined)
 */

import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import { BookingData } from '@/types';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Video, BookOpen, Download, Edit3, Save, XCircle } from 'lucide-react';
import { toast } from 'sonner';
import { ZoomIcon } from '../icons/zoom-icon';
import { GoogleMeetIcon } from '../icons/google-meet-icon';

interface ViewSummaryModalProps {
    booking: BookingData;
    isOpen: boolean;
    onClose: () => void;
}

export default function ViewSummaryModal({ booking, isOpen, onClose }: ViewSummaryModalProps) {
    // State for editing rating and review
    const [isEditingReview, setIsEditingReview] = useState(false);
    const [rating, setRating] = useState(booking.teachingSession?.student_rating || booking.user_rating || 0);
    const [review, setReview] = useState(
        booking.teachingSession?.booking_notes?.student_review || 
        booking.teachingSession?.student_review || 
        booking.teachingSession?.student_notes || 
        booking.user_feedback || 
        ''
    );

    // State for editing personal notes
    const [isEditingNotes, setIsEditingNotes] = useState(false);
    const [personalNotes, setPersonalNotes] = useState(
        booking.teachingSession?.booking_notes?.student_note || 
        booking.teachingSession?.student_notes || 
        booking.student_notes || 
        ''
    );

    const handleRebook = () => {
        const teacherId = booking.teacher_id || (typeof booking.teacher === 'object' ? booking.teacher?.id : null);
        router.visit(`/student/browse-teachers/${teacherId}?rebook=${booking.id}`);
        onClose();
    };

    const handleDownloadPDF = () => {
        // Handle PDF download functionality
        console.log('Download PDF for booking:', booking.id);
    };

    const handleSaveReview = () => {
        // Save the rating and review to the database
        router.post(`/student/bookings/${booking.id}/review`, {
            rating: rating,
            review: review,
        }, {
            onSuccess: () => {
                toast.success('Review saved successfully!');
                setIsEditingReview(false);
            },
            onError: (errors) => {
                toast.error('Failed to save review. Please try again.');
                console.error('Review save errors:', errors);
            }
        });
    };

    const handleCancelEdit = () => {
        // Reset to original values
        setRating(booking.teachingSession?.student_rating || booking.user_rating || 0);
        setReview(
            booking.teachingSession?.booking_notes?.student_review || 
            booking.teachingSession?.student_review || 
            booking.teachingSession?.student_notes || 
            booking.user_feedback || 
            ''
        );
        setIsEditingReview(false);
    };

    const handleSavePersonalNotes = () => {
        // Save personal notes to student_note
        router.post(`/student/bookings/${booking.id}/personal-notes`, {
            personal_notes: personalNotes,
        }, {
            onSuccess: () => {
                toast.success('Personal notes saved successfully!');
                setIsEditingNotes(false);
            },
            onError: (errors) => {
                toast.error('Failed to save personal notes. Please try again.', errors);
                console.error('Personal notes save errors:', errors);
            }
        });
    };

    const handleCancelEditNotes = () => {
        // Reset to original values
        setPersonalNotes(
            booking.teachingSession?.booking_notes?.student_note || 
            booking.teachingSession?.student_notes || 
            booking.student_notes || 
            ''
        );
        setIsEditingNotes(false);
    };

    const handleDownloadPdf = () => {
        try {
            // Create a link to download the PDF
            const link = document.createElement('a');
            link.href = route('student.bookings.summary-pdf', booking.id);
            link.download = `Class_Summary_${booking.booking_uuid}_${new Date().toISOString().split('T')[0]}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            toast.success('PDF download started!');
        } catch (error) {
            console.error('PDF download error:', error);
            toast.error('Failed to download PDF. Please try again.');
        }
    };

    // Get subject-specific colors for the book icon
    const getSubjectColors = (title: string) => {
        const colors = [
            { bg: 'bg-amber-600', icon: 'text-amber-100' },
            { bg: 'bg-emerald-600', icon: 'text-emerald-100' },
            { bg: 'bg-blue-600', icon: 'text-blue-100' },
            { bg: 'bg-purple-600', icon: 'text-purple-100' },
            { bg: 'bg-rose-600', icon: 'text-rose-100' }
        ];

        const hash = title.split('').reduce((a, b) => {
            a = ((a << 5) - a) + b.charCodeAt(0);
            return a & a;
        }, 0);

        return colors[Math.abs(hash) % colors.length];
    };

    const subjectColors = getSubjectColors(booking.title);

    if (!isOpen) return null;

    return (
        <div 
            className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4"
            onClick={(e) => {
                if (e.target === e.currentTarget) {
                    onClose();
                }
            }}
        >
            <div className="bg-white rounded-3xl p-6 max-w-2xl w-full shadow-2xl">
                {/* Header */}
                <div className="mb-4">
                    <div className="flex items-start justify-between">
                        <h2 className="text-xl font-bold text-gray-900">
                            {booking.title || (typeof booking.subject === 'object' ? booking.subject?.name : booking.subject) || 'Class Summary'}
                        </h2>
                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                            booking.status === 'Completed' 
                                ? 'bg-[#E8F5E8] text-[#2D7738]' 
                                : booking.status === 'ongoing'
                                ? 'bg-[#E4FFFC] text-[#338078]'
                                : 'bg-[#E4FFFC] text-[#338078]'
                        }`}>
                            {booking.status ? booking.status.charAt(0).toUpperCase() + booking.status.slice(1) : 'Confirmed'}
                        </span>
                    </div>
                </div>

                {/* Upper Section - Image Left, Details Right */}
                <div className="flex items-start gap-3 mb-4">
                    {/* Subject Image */}
                    <div className={`w-12 h-12 rounded-lg ${subjectColors.bg} flex items-center justify-center flex-shrink-0`}>
                        <BookOpen className={`w-6 h-6 ${subjectColors.icon}`} />
                    </div>

                    {/* Right Side Details */}
                    <div className="flex-1 space-y-2">
                        {/* Teacher */}
                        <div>
                            <span className="text-gray-500 text-sm">Teacher: </span>
                            <span className="font-medium text-gray-900">
                                {typeof booking.teacher === 'object' && booking.teacher?.name 
                                    ? `Ustadh ${booking.teacher.name}` 
                                    : typeof booking.teacher === 'string' && booking.teacher !== 'Unknown Teacher'
                                    ? `Ustadh ${booking.teacher}`
                                    : 'Teacher Name'}
                            </span>
                        </div>

                        {/* Class Duration */}
                        <div>
                            <span className="text-gray-500 text-sm">Class Duration: </span>
                            <span className="font-medium text-gray-900">
                                {booking.duration_minutes ? `${booking.duration_minutes} minutes` : '1 Hour'}
                            </span>
                        </div>

                        {/* Date & Time */}
                        <div className="flex items-center gap-2 bg-[#FFF9E9] rounded-lg p-2 text-sm">
                            <div className="flex items-center gap-2">
                                <span className="text-gray-900 font-semibold text-sm">
                                    {booking.booking_date ? new Date(booking.booking_date).toLocaleDateString('en-US', {
                                        day: 'numeric',
                                        month: 'long',
                                        year: 'numeric'
                                    }) : 'Date'} | {booking.start_time ? new Date(`2000-01-01T${booking.start_time}`).toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    }) : 'Time'} - {booking.end_time ? new Date(`2000-01-01T${booking.end_time}`).toLocaleTimeString('en-US', {
                                        hour: 'numeric',
                                        minute: '2-digit',
                                        hour12: true
                                    }) : 'End Time'}
                                </span>
                            </div>
                        </div>

                        {/* Mode */}
                        <div>
                            <div className="flex items-center gap-4">
                                <span className="text-gray-500 text-sm mb-2 block">Mode:</span>
                                <div className="flex items-center gap-2 bg-[#FFF9E9] rounded-lg p-2 text-sm">
                                    {booking.teachingSession?.meeting_platform === 'zoom' ? (
                                        <div className="flex items-center gap-2">
                                            <Video className="w-5 h-5 text-blue-600" />
                                            <span className="text-gray-700">Zoom</span>
                                        </div>
                                    ) : booking.teachingSession?.meeting_platform === 'google_meet' ? (
                                        <div className="flex items-center gap-2">
                                            <GoogleMeetIcon className="w-5 h-5 text-green-600" />
                                            <span className="text-gray-700">Google Meet</span>
                                        </div>
                                    ) : (
                                        <div className="flex items-center gap-2">
                                            <Video className="w-5 h-5 text-blue-600" />
                                            <span className="text-gray-700">Online Session</span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Recording URL */}
                {booking.teachingSession?.recording_url && (
                    <div className="mb-4">
                        <p className="text-teal-600 font-medium mb-1 text-sm">Session Recording:</p>
                        <a 
                            href={booking.teachingSession.recording_url} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="text-blue-600 hover:text-blue-800 underline text-sm"
                        >
                            Watch Session Recording
                        </a>
                    </div>
                )}

                {/* Teacher Notes */}
                <div className="mb-4">
                    <p className="text-teal-600 font-medium mb-1 text-sm">Notes from Teacher:</p>
                    <p className="text-gray-700 text-sm">
                        {booking.teachingSession?.teacher_notes || 
                         booking.teachingSession?.booking_notes?.teacher_note || 
                         booking.teacher_notes || 
                         `No notes from ustadh ${typeof booking.teacher === 'object' && booking.teacher?.name ? booking.teacher.name : (typeof booking.teacher === 'string' && booking.teacher !== 'Unknown Teacher' ? booking.teacher : 'teacher')} available at the moment.`}
                    </p>
                </div>

                {/* Student Notes Section */}
                <div className="mb-4">
                    <div className="flex items-center justify-between mb-1">
                        <h3 className="font-bold text-gray-900 text-sm">Notes from you:</h3>
                        {/* {!isEditingNotes && (
                            <Button
                                onClick={() => setIsEditingNotes(true)}
                                variant="ghost"
                                size="sm"
                                className="h-6 px-2 text-xs text-teal-600 hover:text-teal-700 hover:bg-teal-50"
                            >
                                <Edit3 className="w-3 h-3 mr-1" />
                                Edit
                            </Button>
                        )} */}
                    </div>

                    {isEditingNotes ? (
                        <div className="space-y-3">
                            <Textarea
                                value={personalNotes}
                                onChange={(e) => setPersonalNotes(e.target.value)}
                                placeholder="Add your personal notes about this class..."
                                className="min-h-[60px] text-sm"
                            />
                            <div className="flex gap-2 justify-end">
                                <Button
                                    onClick={handleCancelEditNotes}
                                    variant="outline"
                                    size="sm"
                                    className="h-7 px-3 text-xs"
                                >
                                    <XCircle className="w-3 h-3 mr-1" />
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleSavePersonalNotes}
                                    size="sm"
                                    className="h-7 px-3 bg-teal-600 hover:bg-teal-700 text-xs"
                                >
                                    <Save className="w-3 h-3 mr-1" />
                                    Save
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div>
                            <p className="text-gray-700 text-sm">
                                {personalNotes || 'No personal notes added yet.'}
                            </p>
                            <p className="text-teal-600 text-sm mt-1">
                                if you want to <span className="underline cursor-pointer hover:text-teal-700" onClick={() => setIsEditingNotes(true)}>add personal notes</span>
                            </p>
                        </div>
                    )}
                </div>

                {/* Rate & Review Section */}
                <div className="mb-4">
                    <div className="flex items-center justify-between mb-3">
                        <h3 className="font-bold text-gray-900 text-sm">Rate & Review:</h3>
                        {!isEditingReview && (
                            <Button
                                onClick={() => setIsEditingReview(true)}
                                variant="ghost"
                                size="sm"
                                className="h-6 px-2 text-xs text-teal-600 hover:text-teal-700 hover:bg-teal-50"
                            >
                                <Edit3 className="w-3 h-3 mr-1" />
                                Edit
                            </Button>
                        )}
                    </div>

                    {/* Star Rating */}
                    <div className="flex items-center gap-1 mb-3">
                        {[1, 2, 3, 4, 5].map((star) => (
                            <span 
                                key={star} 
                                onClick={() => isEditingReview && setRating(star)}
                                className={`text-lg ${isEditingReview ? 'cursor-pointer hover:text-yellow-500' : 'cursor-default'} ${
                                    star <= rating
                                        ? 'text-yellow-400' 
                                        : 'text-gray-300'
                                }`}
                            >
                                ★
                            </span>
                        ))}
                        <span className="text-xs text-gray-500 ml-2">
                            {rating > 0 ? `(${rating}/5 stars)` : '(Leave a Star Rating)'}
                        </span>
                    </div>

                    {/* Feedback Textarea */}
                    {isEditingReview ? (
                        <div className="space-y-3">
                            <Textarea
                                value={review}
                                onChange={(e) => setReview(e.target.value)}
                                placeholder="Write your feedback about the class..."
                                className="min-h-[60px] text-sm"
                            />
                            <div className="flex gap-2 justify-end">
                                <Button
                                    onClick={handleCancelEdit}
                                    variant="outline"
                                    size="sm"
                                    className="h-7 px-3 text-xs"
                                >
                                    <XCircle className="w-3 h-3 mr-1" />
                                    Cancel
                                </Button>
                                <Button
                                    onClick={handleSaveReview}
                                    size="sm"
                                    className="h-7 px-3 bg-teal-600 hover:bg-teal-700 text-xs"
                                >
                                    <Save className="w-3 h-3 mr-1" />
                                    Save
                                </Button>
                            </div>
                        </div>
                    ) : (
                        <div className="bg-gray-50 rounded-lg p-3 border border-gray-200 min-h-[60px]">
                            <p className="text-gray-400 text-sm">
                                {review || 'Write your feedback...'}
                            </p>
                        </div>
                    )}
                </div>

                {/* Action Buttons - Center Aligned */}
                <div className="flex gap-2 justify-center">
                    <Button
                        onClick={handleRebook}
                        className="px-4 py-2 bg-[#338078] hover:bg-[#236158] text-white rounded-full text-sm"
                    >
                        Rebook Class
                    </Button>
                    <Button
                        onClick={handleDownloadPdf}
                        variant="outline"
                        className="px-4 py-2 rounded-full border-[#338078] text-[#338078] hover:bg-green-50 flex items-center gap-2 text-sm"
                    >
                        <Download className="w-3 h-3" />
                        Download Summary PDF
                    </Button>
                </div>
            </div>
        </div>
    );
}
