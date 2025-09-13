import { cn } from '@/lib/utils';
import { ReactNode, useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { X, Loader2, RefreshCw, MoreHorizontal } from 'lucide-react';
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
    start_time: string;
    end_time: string;
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
    const [isRefreshing, setIsRefreshing] = useState(false);
    const [lastFetchTime, setLastFetchTime] = useState(0);
    
    const getInitials = useInitials();

    useEffect(() => {
        fetchSidebarData();
        
        // Refresh data every 30 seconds for real-time updates
        const interval = setInterval(fetchSidebarData, 30000);
        
        return () => {
            clearInterval(interval);
        };
    }, []);
    
    const fetchSidebarData = async (isManualRefresh = false) => {
        // Prevent multiple simultaneous calls
        if (isRefreshing && !isManualRefresh) {
            console.log('Already refreshing, skipping...');
            return;
        }

        // Debounce: prevent calls within 2 seconds of each other
        const now = Date.now();
        if (now - lastFetchTime < 2000 && !isManualRefresh) {
            console.log('Too soon since last fetch, skipping...');
            return;
        }

        try {
            if (isManualRefresh) {
                setIsRefreshing(true);
            } else {
                setLoading(true);
            }
            
            setLastFetchTime(now);
            
            const response = await axios.get('/teacher/sidebar-data');
            
            if (response.data.success) {
                const data = response.data.data;
                setSessionRequests(data.sessionRequests || []);
                setMessages(data.messages || []);
                setOnlineStudents(data.onlineStudents || []);
                setUnreadMessageCount(data.unreadMessageCount || 0);
                setPendingRequestCount(data.pendingRequestCount || 0);
            } else {
                console.error('API returned error:', response.data.message);
                // Set fallback data
                setSessionRequests([]);
                setMessages([]);
                setOnlineStudents([]);
                setUnreadMessageCount(0);
                setPendingRequestCount(0);
            }
        } catch (error) {
            console.error('Failed to fetch sidebar data:', error);
            
            // Set fallback data in case of error
            setSessionRequests([]);
            setMessages([]);
            setOnlineStudents([]);
            setUnreadMessageCount(0);
            setPendingRequestCount(0);
        } finally {
            if (isManualRefresh) {
                setIsRefreshing(false);
            } else {
                setLoading(false);
            }
        }
    };
    
    const handleAcceptRequest = async (id: number) => {
        try {
            setProcessingRequestId(id);
            
            const response = await axios.post(`/teacher/requests/${id}/accept`);
            
            if (response.data.success) {
                // Remove the request from the list
                setSessionRequests(sessionRequests.filter(request => request.id !== id));
                setPendingRequestCount(prev => prev - 1);
                toast.success('Session request accepted');
                
                // Refresh data immediately for real-time updates
                setTimeout(() => {
                    fetchSidebarData(true);
                }, 500);
            } else {
                toast.error(response.data.message || 'Failed to accept request');
            }
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
            
            const response = await axios.post(`/teacher/requests/${id}/decline`);
            
            if (response.data.success) {
                // Remove the request from the list
                setSessionRequests(sessionRequests.filter(request => request.id !== id));
                setPendingRequestCount(prev => prev - 1);
                toast.success('Session request declined');
                
                // Refresh data immediately for real-time updates
                setTimeout(() => {
                    fetchSidebarData(true);
                }, 500);
            } else {
                toast.error(response.data.message || 'Failed to decline request');
            }
        } catch (error) {
            console.error('Failed to decline session request:', error);
            toast.error('Failed to decline request');
        } finally {
            setProcessingRequestId(null);
        }
    };

    const defaultContent = (
        <div className=" p-4 rounded-lg w-full">
            {/* Page Title */}
            <h1 className="text-xl font-bold text-gray-900 mb-4">Student Request</h1>
            
            <div className="space-y-4">
                {/* New Request Section */}
                <div className="bg-[#F3FFFE] rounded-lg p-3 overflow-hidden">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <MessageUserIcon className="h-5 w-5 text-gray-600" />
                            <h3 className="text-lg font-bold text-gray-800">New Request</h3>
                        </div>
                        <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => fetchSidebarData(true)}
                                disabled={loading}
                                className="h-8 w-8 p-0 hover:bg-gray-200"
                            >
                                <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            </Button>
                            <div className="text-gray-400">⋯</div>
                        </div>
                    </div>
                
                {loading ? (
                    <div className="flex justify-center items-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-teal-600" />
                    </div>
                ) : sessionRequests.length > 0 ? (
                    <div className="space-y-4">
                        {sessionRequests.map((request) => (
                            <div key={request.id} className="bg-white rounded-lg p-6 border border-gray-200 relative overflow-hidden">
                                {/* New Badge */}
                                <div className="absolute top-4 left-4 z-10">
                                    <span className="bg-teal-100 text-teal-800 text-xs font-medium px-2 py-1 rounded-full">
                                        New
                                    </span>
                                </div>
                                
                                {/* Ellipsis Menu */}
                                <div className="absolute top-4 right-4 z-10">
                                    <Button variant="ghost" size="sm" className="h-6 w-6 p-0">
                                        <MoreHorizontal className="h-4 w-4 text-gray-500" />
                                    </Button>
                                </div>
                                
                                <div className="flex flex-col items-start text-start mt-6">
                                    {/* Avatar at the top */}
                                    <Avatar className="h-16 w-16 mb-4">
                                        <AvatarImage src={request.student.avatar || undefined} />
                                        <AvatarFallback className="text-lg">
                                            {getInitials(request.student.name)}
                                        </AvatarFallback>
                                    </Avatar>
                                    
                                    {/* Student Name */}
                                    <h4 className="font-bold text-gray-900 text-lg mb-2">{request.student.name}</h4>
                                    
                                    {/* Note to teacher */}
                                    <p className="text-sm text-gray-600 mb-4 break-words">Looking for a teacher for {request.subject}.</p>
                                    
                                    {/* Session Details */}
                                    <div className="flex flex-col space-y-2 text-sm text-gray-700 mb-6 w-full">
                                        <div className="flex justify-between items-start">
                                            <span className="text-gray-600">Subject:</span>
                                            <span className="font-semibold">{request.subject}</span>
                                        </div>
                                        <div className="flex justify-between items-start">
                                            <span className="text-gray-600">Requested Date:</span>
                                            <span className="font-semibold">{request.scheduled_at.split(' at ')[0]}</span>
                                        </div>
                                        <div className="flex justify-between items-start">
                                            <span className="text-gray-600">Requested Time:</span>
                                            <span className="font-semibold">{request.start_time} - {request.end_time}</span>
                                        </div>
                                    </div>
                                    
                                    {/* Action Buttons */}
                                    <div className="flex gap-3 w-full items-start">
                                        <Button
                                            onClick={() => handleAcceptRequest(request.id)}
                                            disabled={processingRequestId === request.id}
                                            className="flex-1 h-10 bg-teal-600 hover:bg-teal-700 text-white font-medium rounded-full"
                                        >
                                            {processingRequestId === request.id ? (
                                                <Loader2 className="h-4 w-4 animate-spin mr-2" />
                                            ) : null}
                                            Accept
                                        </Button>
                                        <Button
                                            variant="outline"
                                            onClick={() => handleDeclineRequest(request.id)}
                                            disabled={processingRequestId === request.id}
                                            className="flex-1 h-10 border-teal-600 text-teal-600 hover:bg-teal-50 font-medium rounded-full"
                                        >
                                            Decline
                                        </Button>
                                    </div>
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

                {/* Recent Messages Section */}
                <div className="bg-[#F3FFFE] rounded-lg p-3 overflow-hidden">
                    <div className="flex items-center justify-between mb-4">
                        <div className="flex items-center gap-2">
                            <MessageUserIcon className="h-5 w-5 text-gray-600" />
                            <h3 className="text-lg font-bold text-gray-800">Recent Messages</h3>
                        </div>
                        {/* <div className="flex items-center gap-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={() => fetchSidebarData(true)}
                                disabled={loading}
                                className="h-8 w-8 p-0 hover:bg-gray-200"
                            >
                                <RefreshCw className={`h-4 w-4 ${isRefreshing ? 'animate-spin' : ''}`} />
                            </Button>
                            <div className="text-gray-400">⋯</div>
                        </div> */}
                    </div>
                
                {loading ? (
                    <div className="flex justify-center items-center py-8">
                        <Loader2 className="h-6 w-6 animate-spin text-teal-600" />
                    </div>
                ) : messages.length > 0 ? (
                    <div className="space-y-4">
                        {messages.map((message) => (
                            <div key={message.id} className="flex items-start gap-3 bg-white rounded-lg p-4">
                                <Avatar className="h-10 w-10 flex-shrink-0">
                                    <AvatarImage src={message.sender.avatar || undefined} />
                                    <AvatarFallback>
                                        {getInitials(message.sender.name)}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex-1 min-w-0">
                                    <div className="flex items-center justify-between">
                                        <h4 className="font-bold text-gray-900 text-sm truncate">{message.sender.name}</h4>
                                        <span className="text-xs text-gray-500 flex-shrink-0 ml-2">{message.time_ago}</span>
                                    </div>
                                    <p className="text-sm text-gray-600 mt-1 break-words">{message.message}</p>
                                </div>
                            </div>
                        ))}
                        
                        <div className="text-center pt-2">
                            <Link 
                                href="/teacher/messages" 
                                className="text-sm text-teal-600 hover:text-teal-800 font-medium"
                            >
                                View All Messages
                            </Link>
                        </div>
                    </div>
                ) : (
                    <div className="bg-[#F3FFFE] rounded-lg p-4 shadow-sm text-center text-gray-500 text-sm">
                        No messages to display
                    </div>
                )}
                </div>
            </div>
        </div>
    );

    return (
        <div className={cn(
            "w-80 p-4 max-w-sm overflow-y-auto",
            "[&::-webkit-scrollbar]:hidden [-ms-overflow-style:none] [scrollbar-width:none]",
            isMobile && "bg-white shadow-xl h-full w-full max-w-none",
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