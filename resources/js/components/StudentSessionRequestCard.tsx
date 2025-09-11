import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MessageCircle, Video, User } from 'lucide-react';
import { StudentProfileModal } from './StudentProfileModal';
import { useState } from 'react';

interface StudentSessionRequestCardProps {
    student: {
        id: number;
        name: string;
        avatar?: string;
        specialization?: string;
        isOnline?: boolean;
        joinedDate?: string;
        location?: string;
        age?: number;
        gender?: string;
        preferredLearningTime?: string;
        subjects?: string[];
        learningGoal?: string;
        availableDays?: string[];
        upcomingSessions?: {
            time: string;
            endTime: string;
            day: string;
            lesson: string;
            status: string;
        }[];
    };
    request: {
        description: string;
        dateToStart: string;
        time: string;
        subjects: string[];
        price: string;
        priceNaira: string;
    };
    onViewProfile?: () => void;
    onChat?: () => void;
    onVideoCall?: () => void;
    className?: string;
}

export function StudentSessionRequestCard({
    student,
    request,
    onViewProfile,
    onChat,
    onVideoCall,
    className = ''
}: StudentSessionRequestCardProps) {
    const [isProfileModalOpen, setIsProfileModalOpen] = useState(false);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const handleViewProfile = () => {
        setIsProfileModalOpen(true);
        onViewProfile?.();
    };

    const handleChat = () => {
        onChat?.();
    };

    const handleVideoCall = () => {
        onVideoCall?.();
    };

    return (
        <div className={`bg-white rounded-2xl p-6 border border-gray-100 shadow-sm ${className}`}>
            {/* Student Profile Section */}
            <div className="flex items-start space-x-4 mb-4">
                {/* Avatar with Online Status */}
                <div className="relative">
                    <Avatar className="h-16 w-16">
                        <AvatarImage src={student.avatar} />
                        <AvatarFallback className="bg-yellow-400 text-gray-800 text-lg font-semibold">
                            {getInitials(student.name)}
                        </AvatarFallback>
                    </Avatar>
                    {student.isOnline && (
                        <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                    )}
                </div>

                {/* Student Info */}
                <div className="flex-1">
                    <h3 className="text-lg font-bold text-gray-800 mb-1">{student.name}</h3>
                    {student.specialization && (
                        <p className="text-sm text-gray-600">{student.specialization}</p>
                    )}
                </div>
            </div>

            {/* Request Description */}
            <p className="text-sm text-gray-700 mb-4">{request.description}</p>

            {/* Session Details */}
            <div className="space-y-2 mb-4">
                <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Date to start:</span>
                    <span className="text-gray-800 font-medium">{request.dateToStart}</span>
                </div>
                <div className="flex justify-between text-sm">
                    <span className="text-gray-600">Time:</span>
                    <span className="text-gray-800 font-medium">{request.time}</span>
                </div>
            </div>

            {/* Subject Tags */}
            <div className="mb-4">
                <p className="text-sm text-gray-600 mb-2">Subject</p>
                <div className="flex flex-wrap gap-2">
                    {request.subjects.map((subject, index) => (
                        <Badge
                            key={index}
                            variant="secondary"
                            className="bg-gray-100 text-gray-700 text-xs px-3 py-1"
                        >
                            {subject}
                        </Badge>
                    ))}
                </div>
            </div>

            {/* Bottom Section */}
            <div className="flex items-center justify-between">
                {/* Pricing */}
                <div className="flex flex-col">
                    <div className="bg-teal-100 text-teal-800 px-3 py-1 rounded-full text-sm font-bold">
                        {request.price} / {request.priceNaira}
                    </div>
                    <span className="text-xs text-gray-500 mt-1">Per session</span>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center space-x-3">
                    <Button
                        variant="link"
                        className="text-teal-600 hover:text-teal-700 p-0 text-sm font-medium"
                        onClick={handleViewProfile}
                    >
                        View Profile
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="w-8 h-8 p-0 border-teal-200 text-teal-600 hover:bg-teal-50"
                        onClick={handleChat}
                    >
                        <MessageCircle className="h-4 w-4" />
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        className="w-8 h-8 p-0 border-teal-200 text-teal-600 hover:bg-teal-50"
                        onClick={handleVideoCall}
                    >
                        <Video className="h-4 w-4" />
                    </Button>
                </div>
            </div>

            {/* Student Profile Modal */}
            <StudentProfileModal
                isOpen={isProfileModalOpen}
                onClose={() => setIsProfileModalOpen(false)}
                student={student}
                onChat={handleChat}
                onStartClass={handleVideoCall}
            />
        </div>
    );
}
