import { cn } from '@/lib/utils';
import { ReactNode, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { X, Loader2 } from 'lucide-react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { NotificationIcon } from '@/components/icons/notification-icon';
import { MessageUserIcon } from '@/components/icons/message-user-icon';
import axios from 'axios';
import { Link } from '@inertiajs/react';
import { useInitials } from '@/hooks/use-initials';
import { toast } from 'sonner';

interface TeacherRightSidebarProps {
    children?: ReactNode;
    className?: string;
    isMobile?: boolean;
    onClose?: () => void;
}

interface Student {
    id: number;
    name: string;
    avatar: string | null;
    is_online: boolean;
}

interface SessionRequest {
    id: number;
    student: Student;
    subject: string;
    scheduled_at: string;
    time_ago: string;
}

interface Message {
    id: number;
    sender: Student;
    message: string;
    time_ago: string;
    is_read: boolean;
}

export default function TeacherRightSidebar({ 
    children,
    className,
    isMobile = false,
    onClose
}: TeacherRightSidebarProps) {
    const [loading, setLoading] = useState(true);
    const [sessionRequests, setSessionRequests] = useState<SessionRequest[]>([]);
    const [messages, setMessages] = useState<Message[]>([]);
    const [onlineStudents, setOnlineStudents] = useState<Student[]>([]);
    const [unreadMessageCount, setUnreadMessageCount] = useState(0);
    const [pendingRequestCount, setPendingRequestCount] = useState(0);
    const [processingRequestId, setProcessingRequestId] = useState<number | null>(null);
    
    const getInitials = useInitials();

    useEffect(() => {
        fetchSidebarData();
        
        // Refresh data every 60 seconds
        const interval = setInterval(fetchSidebarData, 60000);
        
        return () => {
            clearInterval(interval);
        };
    }, []);
    
    const fetchSidebarData = async () => {
        try {
            setLoading(true);
            console.log('Fetching sidebar data...');
            
            // Set empty data since API endpoints have been removed
            setSessionRequests([]);
            setMessages([]);
            setOnlineStudents([]);
            setUnreadMessageCount(0);
            setPendingRequestCount(0);
        } catch (error) {
            console.error('Failed to fetch sidebar data:', error);
            
            // Set fallback data in case of error
            setSessionRequests([]);
            setMessages([]);
            setOnlineStudents([]);
            setUnreadMessageCount(0);
            setPendingRequestCount(0);
        } finally {
            setLoading(false);
        }
    };
    
    const handleAcceptRequest = async (id: number) => {
        try {
            setProcessingRequestId(id);
            
            // Remove the request from the list (API endpoint removed)
            setSessionRequests(sessionRequests.filter(request => request.id !== id));
            setPendingRequestCount(prev => prev - 1);
            
            toast.success('Session request accepted');
        } catch (error) {
            console.error('Failed to accept session request:', error);
            toast.error('Failed to accept request');
        } finally {
            setProcessingRequestId(null);
        }
    };
    
    const handleDeclineRequest = async (id: number) => {
        try {
            setProcessingRequestId(id);
            
            // Remove the request from the list (API endpoint removed)
            setSessionRequests(sessionRequests.filter(request => request.id !== id));
            setPendingRequestCount(prev => prev - 1);
            
            toast.success('Session request declined');
        } catch (error) {
            console.error('Failed to decline session request:', error);
            toast.error('Failed to decline request');
        } finally {
            setProcessingRequestId(null);
        }
    };

    const defaultContent = (
        <div className="space-y-8 bg-[#f0f9f6] p-2 rounded-lg">
            {/* New Session Requests Section */}
            <div className="rounded-lg bg-[#f0f9f6] p-4 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-2">
                        <MessageUserIcon className="h-6 w-6" />
                        <h3 className="text-lg font-bold text-gray-800">New Session Requests</h3>
                    </div>
                    {pendingRequestCount > sessionRequests.length && (
                        <Link 
                            href="/teacher/requests" 
                            className="text-xs text-teal-600 hover:text-teal-800"
                        >
                            View all ({pendingRequestCount})
                        </Link>
                    )}
                </div>
                
                {loading ? (
                    <div className="flex justify-center items-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-teal-600" />
                    </div>
                ) : sessionRequests.length > 0 ? (
                    <div className="space-y-4">
                        {sessionRequests.map((request) => (
                            <div key={request.id} className="bg-white rounded-lg p-4 shadow-sm">
                                <div className="flex items-center gap-3">
                                    <div className="text-blue-500">
                                        <Avatar className="h-8 w-8">
                                            <AvatarImage src={request.student.avatar || undefined} alt={request.student.name} />
                                            <AvatarFallback>{getInitials(request.student.name)}</AvatarFallback>
                                        </Avatar>
                                    </div>
                                    <div className="flex-1">
                                        <h4 className="font-bold text-gray-800">{request.student.name}</h4>
                                        <p className="text-xs text-gray-600">Requested a {request.subject} class</p>
                                        <p className="text-xs text-gray-500 mt-1">{request.time_ago}</p>
                                    </div>
                                </div>
                                <div className="flex gap-2 mt-3">
                                    <Button 
                                        className="bg-teal-600 hover:bg-teal-700 text-white text-xs px-4 py-1 h-8 rounded-full"
                                        onClick={() => handleAcceptRequest(request.id)}
                                        disabled={processingRequestId === request.id}
                                    >
                                        {processingRequestId === request.id ? (
                                            <Loader2 className="h-3 w-3 animate-spin mr-1" />
                                        ) : null}
                                        Accept
                                    </Button>
                                    <Button 
                                        variant="outline" 
                                        className="border-red-500 text-red-500 hover:bg-red-50 text-xs px-4 py-1 h-8 rounded-full"
                                        onClick={() => handleDeclineRequest(request.id)}
                                        disabled={processingRequestId === request.id}
                                    >
                                        Decline
                                    </Button>
                                </div>
                            </div>
                        ))}
                    </div>
                ) : (
                    <div className="bg-white rounded-lg p-4 shadow-sm text-center text-gray-500 text-sm">
                        No pending session requests
                    </div>
                )}
            </div>

            {/* Online Student Section */}
            <div className="rounded-lg bg-[#f0f9f6] p-4 shadow-sm">
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-2">
                        <MessageUserIcon className="h-6 w-6" />
                        <h3 className="text-lg font-bold text-gray-800">Messages</h3>
                    </div>
                    {unreadMessageCount > 0 && (
                        <span className="bg-red-500 text-white text-xs rounded-full px-2 py-0.5">
                            {unreadMessageCount}
                        </span>
                    )}
                </div>
                
                {loading ? (
                    <div className="flex justify-center items-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-teal-600" />
                    </div>
                ) : messages.length > 0 ? (
                    <div className="space-y-4">
                        {messages.map((message) => (
                            <div key={message.id} className="flex gap-3">
                                <Avatar className="h-8 w-8">
                                    <AvatarImage src={message.sender.avatar || undefined} alt={message.sender.name} />
                                    <AvatarFallback>{getInitials(message.sender.name)}</AvatarFallback>
                                </Avatar>
                                <div className="flex-1">
                                    <div className="flex justify-between">
                                        <h4 className="font-bold text-gray-800 flex items-center">
                                            {message.sender.name}
                                            {!message.is_read && (
                                                <span className="ml-2 bg-teal-500 h-2 w-2 rounded-full"></span>
                                            )}
                                        </h4>
                                        <span className="text-xs text-gray-500">{message.time_ago}</span>
                                    </div>
                                    <p className="text-xs text-gray-600">{message.message}</p>
                                </div>
                            </div>
                        ))}
                        
                        <Link href="/teacher/messages">
                            <Button variant="ghost" className="w-full text-teal-600 hover:text-teal-700 hover:bg-teal-50 text-sm">
                                View All Messages
                            </Button>
                        </Link>
                    </div>
                ) : (
                    <div className="bg-white rounded-lg p-4 shadow-sm text-center text-gray-500 text-sm">
                        No messages to display
                    </div>
                )}
            </div>
            
            {/* Online Students Section */}
            {onlineStudents.length > 0 && (
                <div className="rounded-lg bg-[#f0f9f6] p-4 shadow-sm">
                    <div className="flex items-center gap-2 mb-4">
                        <div className="h-2 w-2 bg-green-500 rounded-full"></div>
                        <h3 className="text-lg font-bold text-gray-800">Students Online</h3>
                    </div>
                    
                    <div className="flex flex-wrap gap-2">
                        {onlineStudents.map((student) => (
                            <div key={student.id} className="flex items-center gap-1 bg-white rounded-full pl-1 pr-3 py-1">
                                <Avatar className="h-6 w-6">
                                    <AvatarImage src={student.avatar || undefined} alt={student.name} />
                                    <AvatarFallback>{getInitials(student.name)}</AvatarFallback>
                                </Avatar>
                                <span className="text-xs">{student.name}</span>
                            </div>
                        ))}
                    </div>
                </div>
            )}
        </div>
    );

    return (
        <div className={cn(
            "w-72 p-4",
            isMobile && "bg-white shadow-xl h-full",
            className
        )}>
            {isMobile && (
                <div className="flex justify-between items-center mb-4">
                    <h3 className="text-lg font-medium">Details</h3>
                    <Button variant="ghost" size="sm" className="p-1 h-auto" onClick={onClose}>
                        <X className="h-4 w-4" />
                    </Button>
                </div>
            )}
            {children || defaultContent}
        </div>
    );
} 