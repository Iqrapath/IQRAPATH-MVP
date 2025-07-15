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
        
        // Listen for real-time events
        setupEventListeners();
        
        return () => {
            clearInterval(interval);
            removeEventListeners();
        };
    }, []);
    
    const setupEventListeners = () => {
        // Try multiple methods to get the user ID
        let userId = null;
        
        // Method 1: Try to get from meta tag
        userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        if (userId) {
            console.log('Found user ID in meta tag:', userId);
        }
        
        // Method 2: Try to get from Inertia page props
        // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
        if (!userId && window.Inertia?.page?.props?.auth?.user?.id) {
            // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
            userId = window.Inertia.page.props.auth.user.id.toString();
            console.log('Found user ID in Inertia page props:', userId);
        }
        
        // Method 3: Try to get from window object (might be set by the server)
        // @ts-ignore - userId might be set on window
        if (!userId && window.userId) {
            // @ts-ignore - userId might be set on window
            userId = window.userId.toString();
            console.log('Found user ID in window object:', userId);
        }
        
        // Method 4: Try to get from localStorage (might be set during login)
        if (!userId && localStorage.getItem('user_id')) {
            userId = localStorage.getItem('user_id');
            console.log('Found user ID in localStorage:', userId);
        }
        
        // Method 5: Try to get from the URL (if it's in the URL)
        const urlParams = new URLSearchParams(window.location.search);
        if (!userId && urlParams.get('user_id')) {
            userId = urlParams.get('user_id');
            console.log('Found user ID in URL:', userId);
        }
        
        // Method 6: Try to get from the HTML (might be embedded in a data attribute)
        const userIdElement = document.getElementById('user-id');
        if (!userId && userIdElement?.getAttribute('data-user-id')) {
            userId = userIdElement.getAttribute('data-user-id');
            console.log('Found user ID in HTML element:', userId);
        }
        
        // Method 7: Try to get from an API call as a last resort
        if (!userId) {
                         // Make an API call to get the user ID
             axios.get('/api/user-id')
                 .then(response => {
                     if (response.data && response.data.id) {
                         userId = response.data.id.toString();
                        console.log('Found user ID from API call:', userId);
                        
                        // Add meta tag dynamically
                        const meta = document.createElement('meta');
                        meta.name = 'user-id';
                        meta.content = userId;
                        document.head.appendChild(meta);
                        
                        // Save in localStorage for future use
                        localStorage.setItem('user_id', userId);
                        
                        // Continue with setup now that we have the user ID
                        continueSetup(userId);
                    }
                })
                .catch(error => {
                    console.error('Failed to get user ID from API:', error);
                });
        }
        
        // If we found a user ID through any of the methods above
        if (userId) {
            // Add meta tag dynamically if it doesn't exist
            if (!document.querySelector('meta[name="user-id"]')) {
                const meta = document.createElement('meta');
                meta.name = 'user-id';
                meta.content = userId;
                document.head.appendChild(meta);
                console.log('Added user ID meta tag dynamically');
                
                // Save in localStorage for future use
                localStorage.setItem('user_id', userId);
            }
            
            // Continue with setup
            return continueSetup(userId);
        } else {
            console.error('User ID not found. Real-time notifications will not work.');
            return;
        }
    };
    
    // Continue setup once we have a user ID
    const continueSetup = (userId: string) => {
        
        if (!window.Echo) {
            console.error('Laravel Echo is not initialized. Real-time notifications will not work.');
            
            // Wait a bit and check again - Echo might be initialized asynchronously in app.tsx
            setTimeout(() => {
                if (window.Echo) {
                    console.log('Echo is now available, setting up event listeners...');
                    continueSetup(userId);
                } else {
                    console.error('Echo still not available after waiting');
                }
            }, 2000);
            
            return;
        }
        
        try {
            console.log(`Setting up event listeners for user ${userId}`);
            
            // Listen for new session requests
            window.Echo.private(`session-requests.${userId}`)
                .listen('.session.requested', (data: { session: SessionRequest }) => {
                    console.log('New session request received:', data);
                    
                    // Add the new session request to the list
                    setSessionRequests(prev => [data.session, ...prev]);
                    setPendingRequestCount(prev => prev + 1);
                    
                    // Show a toast notification
                    toast.info(`New session request from ${data.session.student.name}`);
                });
            
            // Listen for new messages
            window.Echo.private(`messages.${userId}`)
                .listen('.message.received', (data: { message: Message }) => {
                    console.log('New message received:', data);
                    
                    // Add the new message to the list
                    setMessages(prev => [data.message, ...prev]);
                    setUnreadMessageCount(prev => prev + 1);
                    
                    // Show a toast notification
                    toast.info(`New message from ${data.message.sender.name}`);
                });
            
            // Listen for notifications
            window.Echo.private(`notifications.${userId}`)
                .listen('.notification.received', (data: any) => {
                    console.log('New notification received:', data);
                    
                    // Show a toast notification
                    toast.info(`New notification: ${data.notification?.title || 'System notification'}`);
                })
                .listen('.test.broadcast', (data: any) => {
                    console.log('Test broadcast received:', data);
                    
                    // Show a toast notification
                    toast.info(`Test broadcast: ${data.message || 'Test message'}`);
                });
            
            console.log('Real-time event listeners set up successfully');
        } catch (error) {
            console.error('Error setting up real-time event listeners:', error);
        }
    };
    
    const removeEventListeners = () => {
        // Try to get user ID using the same methods as setupEventListeners
        let userId = document.querySelector('meta[name="user-id"]')?.getAttribute('content');
        
        if (!userId) {
            // Try localStorage as a backup
            userId = localStorage.getItem('user_id');
        }
        
        // If not found, try to get from Inertia page props
        // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
        if (!userId && window.Inertia?.page?.props?.auth?.user?.id) {
            // @ts-ignore - Inertia is available globally but TypeScript doesn't know about it
            userId = window.Inertia.page.props.auth.user.id.toString();
        }
        
        if (!userId || !window.Echo) {
            return;
        }
        
        try {
            console.log(`Removing event listeners for user ${userId}`);
            
            window.Echo.leave(`session-requests.${userId}`);
            window.Echo.leave(`messages.${userId}`);
            window.Echo.leave(`notifications.${userId}`);
            
            console.log('Real-time event listeners removed successfully');
        } catch (error) {
            console.error('Error removing real-time event listeners:', error);
        }
    };
    
    const fetchSidebarData = async () => {
        try {
            setLoading(true);
            console.log('Fetching sidebar data...');
            
            console.log('Trying endpoint: /api/teacher/sidebar-data');
            const response = await axios.get('/api/teacher/sidebar-data');
            console.log('Endpoint response:', response.data);
            
            setSessionRequests(response.data.session_requests || []);
            setMessages(response.data.messages || []);
            setOnlineStudents(response.data.online_students || []);
            setUnreadMessageCount(response.data.unread_message_count || 0);
            setPendingRequestCount(response.data.pending_request_count || 0);
        } catch (error) {
            console.error('Failed to fetch sidebar data:', error);
            
            // Set fallback data in case of error
            setSessionRequests([]);
            setMessages([]);
            setOnlineStudents([]);
            setUnreadMessageCount(0);
            setPendingRequestCount(0);
            
            // Show a toast notification for the error
            toast.error('Failed to load sidebar data. Please try again later.');
        } finally {
            setLoading(false);
        }
    };
    
    const handleAcceptRequest = async (id: number) => {
        try {
            setProcessingRequestId(id);
            await axios.post(`/api/teacher/session-requests/${id}/accept`);
            
            // Remove the request from the list
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
            await axios.post(`/api/teacher/session-requests/${id}/decline`);
            
            // Remove the request from the list
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