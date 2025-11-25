import { Card, CardContent } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { MessageCircle, Video, BookOpen, Star } from 'lucide-react';

interface StudentCardProps {
    student: {
        id: number;
        name: string;
        avatar?: string;
        level: string;
        sessionsCompleted: number;
        progress: number;
        rating: number;
        isOnline?: boolean;
    };
    onViewProfile: (student: any) => void;
    onChat: (student: any) => void;
    onVideoCall: (student: any) => void;
}

export function StudentCard({ student, onViewProfile, onChat, onVideoCall }: StudentCardProps) {
    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const renderStars = (rating: number) => {
        return Array.from({ length: 5 }, (_, i) => (
            <Star
                key={i}
                className={`w-4 h-4 ${
                    i < Math.floor(rating) ? 'text-yellow-400 fill-current' : 'text-yellow-300'
                }`}
            />
        ));
    };

    return (
        <Card className="relative overflow-hidden transition-all duration-200 hover:shadow-lg border-gray-200 hover:border-[#2C7870] hover:border-2">
            <CardContent className="">
                {/* Profile Picture */}
                <div className="flex justify-start mb-4">
                    <div className="relative">
                        <Avatar className="w-20 h-20">
                            <AvatarImage src={student.avatar} />
                            <AvatarFallback className="bg-pink-100 text-pink-800 text-xl font-semibold">
                                {getInitials(student.name)}
                            </AvatarFallback>
                        </Avatar>
                        {student.isOnline && (
                            <div className="absolute -bottom-1 -right-1 w-4 h-4 bg-green-500 border-2 border-white rounded-full"></div>
                        )}
                    </div>
                </div>

                {/* Student Name */}
                <h3 className="text-xl font-bold text-gray-900 text-start mb-2">{student.name}</h3>

                {/* Level with Book Icon */}
                <div className="flex items-center justify-start space-x-2 mb-3">
                    <span className="text-gray-600 text-sm">{student.level}</span>
                    <BookOpen className="w-4 h-4 text-gray-600" />
                </div>

                {/* Sessions Completed */}
                <div className="text-start mb-4">
                    <span className="text-gray-900 text-sm">Sessions Completed: {student.sessionsCompleted}</span>
                </div>

                {/* Progress Section */}
                <div className="mb-4">
                    <div className="text-start mb-6">
                        <span className="text-gray-900 text-sm">Progress:</span>
                    </div>
                    <div className="w-full bg-gray-200 rounded-full h-2 relative">
                        <div 
                            className="bg-[#2C7870] h-2 rounded-full transition-all duration-300"
                            style={{ width: `${student.progress}%` }}
                        ></div>
                        <Badge className="absolute -top-7 left-1/2 transform -translate-x-1/2 bg-[#2C7870] text-white text-xs px-2 py-1 rounded">
                            {student.progress}%
                        </Badge>
                    </div>
                </div>

                {/* Rating */}
                <div className="flex items-center justify-start space-x-2 mb-6">
                    <div className="flex items-center space-x-1">
                        {renderStars(student.rating)}
                    </div>
                    <span className="text-sm font-semibold text-gray-900">{student.rating}</span>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-between">
                    <Button 
                        variant="ghost" 
                        className="text-[#2C7870] hover:text-[#2C7870] p-0 h-auto font-medium text-sm cursor-pointer"
                        onClick={() => onViewProfile(student)}
                    >
                        View Profile
                    </Button>
                    
                    <div className="flex items-center space-x-1 border-b-2 border-[#2C7870] rounded-full relative">
                        <Button 
                            variant="ghost" 
                            size="icon" 
                            className="p-2 text-[#2C7870] hover:text-[#2C7870] hover:bg-transparent cursor-pointer"
                            onClick={() => onChat(student)}
                        >
                            <MessageCircle className="w-5 h-5" />
                        </Button>
                        |
                        <Button 
                            variant="ghost" 
                            size="icon" 
                            className="p-2 text-[#2C7870] hover:text-[#2C7870] hover:bg-transparent cursor-pointer"
                            onClick={() => onVideoCall(student)}
                        >
                            <Video className="w-5 h-5" />
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}
