/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAPATH?node-id=200-7399&t=barbCY4Jn7yoOuNr-0
 * Export: Past Session Card component
 * 
 * EXACT SPECS FROM FIGMA:
 * - Similar layout to upcoming session card
 * - Different status indicators for completed/cancelled sessions
 * - View details or feedback options instead of join button
 * - Rating and feedback display for completed sessions
 */
import React from 'react';
import { Button } from '@/components/ui/button';
import { Star, CheckCircle, XCircle, Clock } from 'lucide-react';

interface Session {
    id: number;
    date: string;
    startTime: string;
    endTime: string;
    subject: string;
    teacher: string;
    student: string;
    status: 'completed' | 'cancelled' | 'no_show';
    rating?: number;
    feedback?: string;
}

interface PastSessionCardProps {
    session: Session;
}

export default function PastSessionCard({ session }: PastSessionCardProps) {
    const getStatusIcon = () => {
        switch (session.status) {
            case 'completed':
                return <CheckCircle className="w-5 h-5 text-green-500" />;
            case 'cancelled':
                return <XCircle className="w-5 h-5 text-red-500" />;
            case 'no_show':
                return <Clock className="w-5 h-5 text-yellow-500" />;
            default:
                return null;
        }
    };

    const getStatusColor = () => {
        switch (session.status) {
            case 'completed':
                return 'bg-green-50 border-green-200';
            case 'cancelled':
                return 'bg-red-50 border-red-200';
            case 'no_show':
                return 'bg-yellow-50 border-yellow-200';
            default:
                return 'bg-gray-50 border-gray-200';
        }
    };

    const handleViewDetails = () => {
        // TODO: Implement view details functionality
        // This will open a modal or navigate to session details page
    };

    const renderStars = (rating: number) => {
        return Array.from({ length: 5 }, (_, i) => (
            <Star
                key={i}
                className={`w-4 h-4 ${
                    i < rating ? 'text-yellow-400 fill-current' : 'text-gray-300'
                }`}
            />
        ));
    };

    return (
        <div className={`flex items-center space-x-4 p-4 border rounded-xl shadow-sm ${getStatusColor()}`}>
            {/* Time Section */}
            <div className="flex-shrink-0 text-center">
                <div className="text-2xl font-bold text-gray-900">
                    {session.startTime}
                </div>
                <div className="text-sm text-gray-500">
                    {session.endTime}
                </div>
            </div>

            {/* Vertical Separator */}
            <div className="w-px h-16 bg-gray-200"></div>

            {/* Session Details */}
            <div className="flex-1 space-y-2">
                <div className="flex items-center space-x-2">
                    {getStatusIcon()}
                    <span className="text-sm font-medium text-gray-600 capitalize">
                        {session.status.replace('_', ' ')}
                    </span>
                </div>
                
                <div className="space-y-1">
                    <div className="text-lg font-semibold text-gray-900">
                        {session.subject}
                    </div>
                    <div className="text-sm text-gray-600">
                        {session.teacher}
                    </div>
                    <div className="text-xs text-gray-500">
                        Student: {session.student}
                    </div>
                </div>

                {/* Rating and Feedback for completed sessions */}
                {session.status === 'completed' && session.rating && (
                    <div className="space-y-1">
                        <div className="flex items-center space-x-1">
                            {renderStars(session.rating)}
                            <span className="text-sm text-gray-600 ml-1">
                                ({session.rating}/5)
                            </span>
                        </div>
                        {session.feedback && (
                            <div className="text-sm text-gray-600 italic">
                                "{session.feedback}"
                            </div>
                        )}
                    </div>
                )}
            </div>

            {/* Action Button */}
            <div className="flex-shrink-0">
                <Button
                    onClick={handleViewDetails}
                    variant="outline"
                    className="px-6 py-2 rounded-lg font-medium transition-colors"
                >
                    View Details
                </Button>
            </div>
        </div>
    );
}
