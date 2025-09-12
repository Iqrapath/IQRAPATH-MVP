import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Textarea } from '@/components/ui/textarea';
import { Star, Video, MessageCircle, Calendar, Clock, User, BookOpen, } from 'lucide-react';
import { GoogleMeetIcon } from '@/components/icons/google-meet-icon';
import { ZoomIcon } from '@/components/icons/zoom-icon';
import MessageCircleStudentIcon from './icons/message-circle-student-icon';

interface SessionDetails {
    id: number;
    session_uuid?: string;
    student_name: string;
    student_avatar?: string;
    subject: string;
    teacher_name?: string;
    teacher_avatar?: string;
    date: string;
    start_time?: string;
    end_time?: string;
    duration?: string;
    status: string;
    meeting_platform?: 'zoom' | 'google_meet';
    meeting_link?: string;
    zoom_join_url?: string;
    google_meet_link?: string;
    student_notes?: string;
    teacher_notes?: string;
    student_rating?: number;
    teacher_rating?: number;
    student_review?: string;
    teacher_review?: string;
}

interface SessionDetailsModalProps {
    isOpen: boolean;
    onClose: () => void;
    session: SessionDetails | null;
    onJoinSession?: (sessionId: number) => void;
    onStartChat?: (sessionId: number) => void;
    onStartVideoCall?: (sessionId: number) => void;
    onAddTeacherNotes?: (sessionId: number, notes: string) => void;
    onRateSession?: (sessionId: number, rating: number, review: string) => void;
}

export function SessionDetailsModal({
    isOpen,
    onClose,
    session,
    onJoinSession,
    onStartChat,
    onStartVideoCall,
    onAddTeacherNotes,
    onRateSession
}: SessionDetailsModalProps) {
    const [teacherNotes, setTeacherNotes] = useState('');
    const [rating, setRating] = useState(0);
    const [review, setReview] = useState('');

    if (!session) return null;

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'long',
            day: 'numeric',
            year: 'numeric'
        });
    };

    const formatTime = (timeString: string) => {
        const time = new Date(`2000-01-01T${timeString}`);
        return time.toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'confirmed':
                return 'bg-green-100 text-green-800';
            case 'scheduled':
                return 'bg-blue-100 text-blue-800';
            case 'in_progress':
                return 'bg-yellow-100 text-yellow-800';
            case 'completed':
                return 'bg-gray-100 text-gray-800';
            case 'cancelled':
                return 'bg-red-100 text-red-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const getMeetingUrl = () => {
        if (session.meeting_platform === 'zoom' && session.zoom_join_url) {
            return session.zoom_join_url;
        } else if (session.meeting_platform === 'google_meet' && session.google_meet_link) {
            return session.google_meet_link;
        } else if (session.meeting_link) {
            return session.meeting_link;
        }
        return null;
    };

    const handleJoinSession = () => {
        const meetingUrl = getMeetingUrl();
        
        if (meetingUrl) {
            // Open the meeting link in a new tab
            window.open(meetingUrl, '_blank', 'noopener,noreferrer');
        } else {
            // Fallback: call the parent handler if no meeting link is available
            if (onJoinSession) {
                onJoinSession(session.id);
            } else {
                console.warn('No meeting link available for this session');
                alert('Meeting link is not available for this session. Please contact the teacher.');
            }
        }
    };

    const handleAddNotes = () => {
        if (onAddTeacherNotes && teacherNotes.trim()) {
            onAddTeacherNotes(session.id, teacherNotes);
            setTeacherNotes('');
        }
    };

    const handleRateSession = () => {
        if (onRateSession && rating > 0) {
            onRateSession(session.id, rating, review);
            setRating(0);
            setReview('');
        }
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent
                className="w-[95vw] max-w-[1400px] max-h-[90vh] overflow-y-auto p-0 rounded-[30px]"
                style={{
                    width: '95vw',
                    maxWidth: '700px',
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none',
                }}
            >
                <DialogHeader className="sr-only">
                    <DialogTitle>
                        {session.subject} Class with {session.teacher_name || 'Teacher'} - Session Details
                    </DialogTitle>
                    <DialogDescription>
                        View and manage session details for {session.student_name}. 
                        Session scheduled for {formatDate(session.date)} at {session.start_time ? formatTime(session.start_time) : 'TBD'}.
                    </DialogDescription>
                </DialogHeader>
                <div className="p-6 space-y-6 rounded-[20px]">
                    {/* Header with Student Info */}
                    <div className="flex items-start space-x-6">
                        {/* Student Avatar */}
                        <Avatar className="w-20 h-20 rounded-lg flex-shrink-0">
                            <AvatarImage src={session.student_avatar} />
                            <AvatarFallback className="bg-orange-100 text-orange-800 text-xl font-semibold rounded-lg">
                                {getInitials(session.student_name)}
                            </AvatarFallback>
                        </Avatar>

                        {/* Main Content */}
                        <div className="flex-1 space-y-4">
                            {/* Title and Status Row */}
                            <div className="flex items-start justify-between">
                                <h1 className="text-3xl font-bold text-gray-900 leading-tight">
                                    {session.subject} Class with {session.teacher_name || 'Teacher'}
                                </h1>
                            </div>

                            {/* Student Info Row */}
                            <div className="flex items-center justify-between">
                                <div className="items-center space-x-6 w-full">
                                    <div className="flex items-center space-x-2">
                                        <span className=" text-gray-600">Student:</span>
                                        <span className=" font-medium text-gray-900">{session.student_name}</span>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        <span className="text-gray-600">Class Duration:</span>
                                        <span className="font-medium text-gray-900">{session.duration || '1 Hour'}</span>
                                    </div>
                                </div>

                                <div className="">
                                    <Badge className={`px-3 py-1 rounded-full text-sm font-medium ${getStatusColor(session.status)}`}>
                                        {session.status.charAt(0).toUpperCase() + session.status.slice(1)}
                                    </Badge>
                                </div>
                            </div>
                            {/* Class Schedule */}
                            <div className="bg-[#FFF9E9] rounded-lg p-4">
                                <div className="flex items-center justify-between">
                                    <div className="text-lg font-semibold text-[#338078]">
                                        {formatDate(session.date)} | {session.start_time ? formatTime(session.start_time) : 'TBD'} - {session.end_time ? formatTime(session.end_time) : 'TBD'}
                                    </div>
                                </div>
                            </div>
                            {/* Meeting Platform */}
                            <div className="">
                                <div className="flex items-center justify-start space-x-4">
                                    <div className="flex items-center space-x-2">
                                        <span className="font-medium text-[#338078]">Mode:</span>
                                    </div>
                                    <div className="flex items-center space-x-4 bg-[#FFF9E9] rounded-lg p-4">
                                        <div className="flex items-center space-x-2">
                                            <div className="w-6 h-6 rounded flex items-center justify-center">
                                                <Video className="w-4 h-4 text-[#338078]" />
                                            </div>
                                            <span className="text-sm font-medium text-[#338078]">Zoom</span>
                                        </div>
                                        <div className="w-px h-4 bg-[#338078]"></div>
                                        <div className="flex items-center space-x-2">
                                            <div className="w-6 h-6 rounded flex items-center justify-center">
                                                <GoogleMeetIcon className="w-4 h-4 text-[#338078]" />
                                            </div>
                                            <span className="text-sm font-medium text-[#338078]">Google Meet</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="border border-gray-200"></div>

                    {/* Student Notes */}
                    {session.student_notes && (
                        <div className="space-y-2">
                            <p className="text-sm text-gray-600">
                                <span className="text-green-600 font-medium">Notes from student:</span>
                                <span className="text-gray-900 ml-2">{session.student_notes}</span>
                            </p>
                        </div>
                    )}

                    {/* Teacher Notes */}
                    <div className="space-y-2">
                        <h3 className="font-medium text-gray-900">Teacher's Notes:</h3>
                        <div className="space-y-2">
                            <p className="text-sm text-gray-600">
                                <span className="text-green-600 cursor-pointer hover:underline">add personal notes</span>
                            </p>
                            <Textarea
                                placeholder="Add personal notes about this session..."
                                value={teacherNotes}
                                onChange={(e) => setTeacherNotes(e.target.value)}
                                className="min-h-[100px]"
                            />
                            <Button
                                onClick={handleAddNotes}
                                disabled={!teacherNotes.trim()}
                                className="bg-green-600 hover:bg-green-700 text-white"
                            >
                                Add Notes
                            </Button>
                        </div>
                    </div>

                    {/* Rate & Review */}
                    <div className="space-y-3">
                        <h3 className="font-medium text-gray-900">Rate & Review:</h3>
                        <div className="flex items-center space-x-2">
                            {[1, 2, 3, 4, 5].map((star) => (
                                <button
                                    key={star}
                                    onClick={() => setRating(star)}
                                    className={`w-6 h-6 ${star <= rating ? 'text-yellow-400' : 'text-gray-300'
                                        }`}
                                >
                                    <Star className="w-6 h-6 fill-current" />
                                </button>
                            ))}
                            <span className="text-sm text-gray-500 ml-2">
                                {rating > 0 ? `(${rating} star${rating > 1 ? 's' : ''})` : '(Leave a Star Rating)'}
                            </span>
                        </div>
                        <Textarea
                            placeholder="Write your feedback..."
                            value={review}
                            onChange={(e) => setReview(e.target.value)}
                            className="min-h-[100px] bg-gray-50"
                        />
                        <Button
                            onClick={handleRateSession}
                            disabled={rating === 0}
                            className="bg-yellow-600 hover:bg-yellow-700 text-white"
                        >
                            Submit Review
                        </Button>
                    </div>

                    {/* Action Buttons */}
                    <div className="flex items-center space-x-4 pt-4 border-t">
                        <Button
                            onClick={handleJoinSession}
                            className={`flex font-medium py-3 rounded-full ${
                                getMeetingUrl() 
                                    ? 'bg-teal-600 hover:bg-teal-700 text-white' 
                                    : 'bg-gray-400 hover:bg-gray-500 text-white cursor-not-allowed'
                            }`}
                            disabled={!getMeetingUrl()}
                        >
                            {session.meeting_platform === 'zoom' ? (
                                <div className="flex items-center space-x-2">
                                    <Video className="w-4 h-4" />
                                    <span>{getMeetingUrl() ? 'Join Zoom Session' : 'Zoom Link Not Available'}</span>
                                </div>
                            ) : session.meeting_platform === 'google_meet' ? (
                                <div className="flex items-center space-x-2">
                                    <GoogleMeetIcon className="w-4 h-4" />
                                    <span>{getMeetingUrl() ? 'Join Google Meet' : 'Google Meet Link Not Available'}</span>
                                </div>
                            ) : (
                                getMeetingUrl() ? 'Join Session Now' : 'Meeting Link Not Available'
                            )}
                        </Button>
                        <div className="border-b-2 border-[#338078] rounded-full text-[#338078] hover:text-[#338078]">
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => onStartChat?.(session.id)}
                                className="p-2 hover:bg-transparent"
                            >
                                <MessageCircleStudentIcon className="w-5 h-5" />
                            </Button>
                            <Button
                                variant="ghost"
                                size="icon"
                                onClick={() => onStartVideoCall?.(session.id)}
                                className="p-2 hover:bg-transparent"
                            >
                                <Video className="w-5 h-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
