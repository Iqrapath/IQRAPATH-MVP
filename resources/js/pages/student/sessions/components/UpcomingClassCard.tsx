/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=388-24636&t=O1w7ozri9pYud8IO-0
 * Export: Upcoming class card component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Shows "Start Class" as a button (actionable)
 * - Has video button for joining when it starts
 * - Has chat button
 * - Shows "Confirmed" or "Pending" status badges
 * - No progress bar (class hasn't started yet)
 */
import { Video, MessageCircle } from 'lucide-react';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { toast } from 'sonner';
import { parseISO, differenceInMinutes, format as formatDate } from 'date-fns';

interface SessionListItem {
    id: number;
    session_uuid: string;
    title: string;
    teacher: string;
    teacher_id: number;
    teacher_avatar: string;
    subject: string;
    date: string;
    time: string;
    duration: number;
    status: string;
    meeting_link?: string;
    completion_date?: string;
    progress?: number;
    rating?: number;
    imageUrl?: string;
    booking_date?: string;
    start_time?: string;
}

interface UpcomingClassCardProps {
    session: SessionListItem;
    getSubjectIcon: (subject: string) => string;
    getProgressColor: (progress: number) => string;
    renderStars: (rating: number) => React.ReactNode;
}

export default function UpcomingClassCard({
    session,
    getSubjectIcon,
    getProgressColor,
    renderStars
}: UpcomingClassCardProps) {
    const getStatusBadgeColor = (status: string) => {
        switch (status?.toLowerCase()) {
            case 'approved':
            case 'confirmed':
                return 'bg-teal-200 text-white';
            case 'pending':
                return 'bg-yellow-200 text-white';
            case 'scheduled':
                return 'bg-blue-200 text-white';
            default:
                return 'bg-gray-200 text-white';
        }
    };

    const handleMessageTeacher = async () => {
        try {
            const response = await axios.post('/api/conversations', {
                recipient_id: session.teacher_id
            });

            if (response.data.success && response.data.data) {
                router.visit(route('student.messages.show', response.data.data.id));
            }
        } catch (error) {
            console.error('Failed to start conversation:', error);
            if (axios.isAxiosError(error) && error.response) {
                const errorData = error.response.data;
                
                // Check if it's a role restriction (no active booking)
                if (errorData.code === 'ROLE_RESTRICTION') {
                    if (session.status?.toLowerCase() === 'pending') {
                        toast.info('Booking pending approval', {
                            description: 'You can message the teacher once they approve your booking request.'
                        });
                    } else {
                        toast.error('Unable to message teacher', {
                            description: errorData.message || 'You need an active booking to message this teacher.'
                        });
                    }
                    return;
                }
                
                // Other errors
                toast.error('Failed to start conversation', {
                    description: errorData.message || 'Please try again later.'
                });
            } else {
                toast.error('Failed to start conversation', {
                    description: 'Please check your internet connection and try again.'
                });
            }
        }
    };

    const handleStartClass = () => {
        // Only allow approved sessions to join
        if (session.status?.toLowerCase() !== 'approved') {
            toast.error('Class not yet approved', {
                description: 'Please wait for the teacher to approve your booking.'
            });
            return;
        }

        // Check if meeting link exists
        if (!session.meeting_link) {
            toast.error('Meeting link not available', {
                description: 'The meeting link will be available closer to the session time.'
            });
            return;
        }

        // Parse session date and time
        try {
            // Combine date and time to create a full datetime
            const sessionDateTime = new Date(`${session.booking_date || session.date} ${session.start_time || session.time.split(' - ')[0]}`);
            const now = new Date();
            const minutesUntilStart = differenceInMinutes(sessionDateTime, now);

            // Allow joining 15 minutes before the session starts
            if (minutesUntilStart > 15) {
                const sessionTime = formatDate(sessionDateTime, 'h:mm a');
                const sessionDate = formatDate(sessionDateTime, 'MMM d, yyyy');
                const hours = Math.floor(minutesUntilStart / 60);
                const minutes = minutesUntilStart % 60;
                
                let timeRemaining = '';
                if (hours > 0) {
                    timeRemaining = `${hours} hour${hours > 1 ? 's' : ''} and ${minutes} minute${minutes !== 1 ? 's' : ''}`;
                } else {
                    timeRemaining = `${minutes} minute${minutes !== 1 ? 's' : ''}`;
                }

                toast.info('Class not yet started', {
                    description: `Your class is scheduled for ${sessionTime} on ${sessionDate}. You can join ${timeRemaining} before the start time (15 minutes early).`,
                    duration: 6000
                });
                return;
            }

            // Allow joining - open meeting link
            window.open(session.meeting_link, '_blank');
        } catch (error) {
            console.error('Error parsing session time:', error);
            // Fallback: just open the link
            window.open(session.meeting_link, '_blank');
        }
    };

    return (
        <div className="flex items-center justify-between py-3 border-b border-gray-100 last:border-b-0">
            {/* Left Section - Subject Image */}
            <div className="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center overflow-hidden flex-shrink-0">
                {session.imageUrl || getSubjectIcon(session.subject) ? (
                    <img
                        src={session.imageUrl || getSubjectIcon(session.subject)}
                        alt={session.title}
                        className="w-14 h-14 object-cover rounded-xl"
                    />
                ) : (
                    <div className="w-14 h-14 bg-[#2C7870] rounded-xl flex items-center justify-center">
                        <span className="text-white font-bold text-sm">
                            {session.subject?.substring(0, 2).toUpperCase() || 'SU'}
                        </span>
                    </div>
                )}
            </div>

            {/* Center Section - Class Details */}
            <div className="flex-1 px-4">
                <div className="space-y-1">
                    {/* Class Title */}
                    <h3 className="text-base font-semibold text-gray-900 leading-tight">
                        {session.title}
                    </h3>

                    {/* Teacher Info */}
                    <div className="text-sm text-gray-600">
                        By {session.teacher}
                    </div>

                    {/* Schedule with Date and Time */}
                    <div className="flex items-center space-x-2 text-sm mt-2">
                        <div className="flex items-center space-x-2 bg-[#FFF9E9] p-1">
                            <span className="text-[#338078] font-medium">{session.date}</span>
                            <span className="text-gray-400">|</span>
                            <span className="text-[#338078] font-medium">{session.time}</span>
                        </div>
                        <span className={`inline-block rounded-full px-3 py-1 text-xs font-medium ${getStatusBadgeColor(session.status)}`}>
                            {session.status}
                        </span>
                    </div>
                </div>
            </div>

            {/* Right Section - Action Buttons */}
            <div className="flex items-center space-x-3">
                {/* Start Class Button - Only for approved sessions */}
                <button 
                    onClick={handleStartClass}
                    disabled={session.status?.toLowerCase() !== 'approved'}
                    className={`px-4 py-2 rounded-full transition-colors text-sm font-medium flex items-center space-x-2 ${
                        session.status?.toLowerCase() === 'approved'
                            ? 'bg-[#2C7870] text-white hover:bg-[#236158]'
                            : 'bg-gray-300 text-gray-500 cursor-not-allowed'
                    }`}
                    title={session.status?.toLowerCase() === 'approved' ? 'Join class' : 'Waiting for approval'}
                >
                    <Video className="w-4 h-4" />
                    <span>Start Class</span>
                </button>

                {/* Message Button */}
                <button 
                    onClick={handleMessageTeacher}
                    className="p-2 text-[#2C7870] hover:bg-teal-50 rounded-full transition-colors"
                    title="Message teacher"
                >
                    <MessageCircle className="w-5 h-5" />
                </button>
            </div>
        </div>
    );
}
