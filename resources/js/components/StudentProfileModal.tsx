import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { MessageCircle, Video, MapPin, Calendar, Clock, BookOpen, Target, CalendarDays } from 'lucide-react';

interface StudentProfileModalProps {
    isOpen: boolean;
    onClose: () => void;
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
    onChat?: () => void;
    onStartClass?: () => void;
}

export function StudentProfileModal({
    isOpen,
    onClose,
    student,
    onChat,
    onStartClass
}: StudentProfileModalProps) {
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const formatJoinedDate = (dateString?: string) => {
        if (!dateString) return 'Recently joined';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
    };

    return (
        <Dialog open={isOpen} onOpenChange={onClose}>
            <DialogContent className="w-auto max-w-4xl max-h-[90vh] overflow-auto"
                style={{
                    scrollbarWidth: 'none',
                    msOverflowStyle: 'none',
                }}
            >
                <DialogHeader>
                    <DialogTitle className="sr-only">Student Profile - {student.name}</DialogTitle>
                    <DialogDescription className="sr-only">
                        View detailed profile information for {student.name}, including learning preferences, 
                        upcoming sessions, and contact options.
                    </DialogDescription>
                </DialogHeader>

                <div className="space-y-6">
                    {/* Top Profile Section */}
                    <div className="flex items-start space-x-8">
                        {/* Left: Avatar and Name */}
                        <div className="flex flex-col items-center space-y-4">
                            <Avatar className="w-24 h-24">
                                <AvatarImage src={student.avatar} />
                                <AvatarFallback className="bg-teal-100 text-teal-800 text-2xl font-semibold">
                                    {getInitials(student.name)}
                                </AvatarFallback>
                            </Avatar>
                            <h2 className="text-2xl font-bold text-gray-900 text-center">{student.name}</h2>
                        </div>

                        {/* Right: Account Details and Action Buttons */}
                        <div className="flex-1 space-y-4">
                            {/* Account Details */}
                            <div className="space-y-3">
                                <div className="flex items-center text-gray-600">
                                    <Calendar className="w-4 h-4 mr-2" />
                                    <span>Joined: {formatJoinedDate(student.joinedDate)}</span>
                                </div>
                                {student.location && (
                                    <div className="flex items-center text-gray-600">
                                        <MapPin className="w-4 h-4 mr-2" />
                                        <span>{student.location}</span>
                                    </div>
                                )}
                            </div>

                            {/* Action Buttons */}
                            <div className="border-b-2 border-[#338078] rounded-full px-6 py-3 flex items-center justify-center space-x-6">
                                <Button
                                    variant="ghost"
                                    className="flex items-center space-x-2 text-[#338078] hover:text-[#338078] hover:bg-transparent p-0 h-auto cursor-pointer"
                                    onClick={onChat}
                                >
                                    <MessageCircle className="w-5 h-5" />
                                    <span className="font-medium">Chat</span>
                                </Button>
                                <div className="w-px h-6 bg-[#338078]"></div>
                                <Button
                                    variant="ghost"
                                    className="flex items-center space-x-2 text-[#338078] hover:text-[#338078] hover:bg-transparent p-0 h-auto cursor-pointer"
                                    onClick={onStartClass}
                                >
                                    <Video className="w-5 h-5" />
                                    <span className="font-medium">Start Class</span>
                                </Button>
                            </div>
                        </div>
                    </div>

                    {/* Lower Part: Two Cards Side by Side */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Left: Student Details Card */}
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 className="text-lg font-bold text-gray-800 mb-4">Student Details</h3>
                            <div className="space-y-3">
                                {student.age && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Age:</span>
                                        <span className="text-gray-900 font-medium">{student.age} years old</span>
                                    </div>
                                )}
                                {student.gender && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Gender:</span>
                                        <span className="text-gray-900 font-medium">{student.gender}</span>
                                    </div>
                                )}
                                {student.preferredLearningTime && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Preferred Learning Time:</span>
                                        <span className="text-gray-900 font-medium">{student.preferredLearningTime}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Right: Learning Preferences Card */}
                        <div className="bg-white rounded-xl border border-gray-200 p-6">
                            <h3 className="text-lg font-bold text-gray-800 mb-4">Learning Preferences</h3>
                            <div className="space-y-3">
                                {student.subjects && student.subjects.length > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Subjects:</span>
                                        <span className="text-gray-900 font-medium">{student.subjects.join(', ')}</span>
                                    </div>
                                )}
                                {student.learningGoal && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Learning Goal:</span>
                                        <span className="text-gray-900 font-medium">{student.learningGoal}</span>
                                    </div>
                                )}
                                {student.availableDays && student.availableDays.length > 0 && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-600">Available Days:</span>
                                        <span className="text-gray-900 font-medium">{student.availableDays.join(', ')}</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Below: Booking Information Card - Full Width */}
                    <div className="bg-white rounded-xl border border-gray-200 p-6 w-full">
                        <div className="flex items-center justify-between mb-4">
                            <h3 className="text-lg font-bold text-gray-800">Booking Information</h3>
                            <Badge className="bg-teal-100 text-teal-800 border-teal-200 px-3 py-1 rounded-full">
                                Confirmed
                            </Badge>
                        </div>

                        <div>
                            <h4 className="text-md font-semibold text-gray-700 mb-4">Upcoming Sessions:</h4>
                            {student.upcomingSessions && student.upcomingSessions.length > 0 ? (
                                <div className="space-y-3">
                                    {student.upcomingSessions.map((session, index) => (
                                        <div key={index} className="flex items-center space-x-4">
                                            <div className="text-center">
                                                <div className="text-xl font-bold text-gray-900">{session.time}</div>
                                                <div className="text-sm text-gray-500">{session.endTime}</div>
                                            </div>
                                            <div className="bg-teal-100 text-teal-800 px-3 py-2 rounded-lg">
                                                <div className="text-sm font-medium">{session.day}</div>
                                                <div className="text-lg font-bold">{session.lesson}</div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-8 text-gray-500">
                                    <Calendar className="w-12 h-12 mx-auto mb-2 text-gray-300" />
                                    <p>No upcoming sessions scheduled</p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
