import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { CheckCircle, XCircle, Clock, MessageCircle, Users } from 'lucide-react';
import axios from 'axios';

interface SessionRequest {
    id: number;
    student: {
        id: number;
        name: string;
        avatar?: string;
        is_online: boolean;
    };
    subject: string;
    scheduled_at: string;
    time_ago: string;
}

interface Message {
    id: number;
    sender: {
        id: number;
        name: string;
        avatar?: string;
        is_online: boolean;
    };
    message: string;
    time_ago: string;
    is_read: boolean;
}

interface OnlineStudent {
    id: number;
    name: string;
    avatar?: string;
    is_online: boolean;
}

interface SidebarData {
    sessionRequests: SessionRequest[];
    messages: Message[];
    onlineStudents: OnlineStudent[];
    pendingRequestCount: number;
    unreadMessageCount: number;
}

export default function TestTeacherSidebar() {
    const [data, setData] = useState<SidebarData | null>(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const fetchData = async () => {
        try {
            setLoading(true);
            setError(null);
            
            const response = await axios.get('/teacher/sidebar-data');
            
            if (response.data.success) {
                setData(response.data.data);
            } else {
                setError(response.data.message || 'API returned error');
            }
        } catch (err: any) {
            console.error('Failed to fetch data:', err);
            setError(err.response?.data?.message || 'Failed to fetch data');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        fetchData();
    }, []);

    const handleAcceptRequest = async (id: number) => {
        try {
            const response = await axios.post(`/teacher/requests/${id}/accept`);
            if (response.data.success) {
                await fetchData(); // Refresh data
            }
        } catch (err) {
            console.error('Failed to accept request:', err);
        }
    };

    const handleDeclineRequest = async (id: number) => {
        try {
            const response = await axios.post(`/teacher/requests/${id}/decline`);
            if (response.data.success) {
                await fetchData(); // Refresh data
            }
        } catch (err) {
            console.error('Failed to decline request:', err);
        }
    };

    if (loading) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <Head title="Test Teacher Sidebar" />
                <div className="text-center">
                    <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-teal-600 mx-auto mb-4"></div>
                    <p>Loading sidebar data...</p>
                </div>
            </div>
        );
    }

    if (error) {
        return (
            <div className="min-h-screen bg-gray-50 flex items-center justify-center">
                <Head title="Test Teacher Sidebar" />
                <Card className="w-full max-w-md">
                    <CardHeader>
                        <CardTitle className="text-red-600">Error</CardTitle>
                        <CardDescription>{error}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Button onClick={fetchData} className="w-full">
                            Try Again
                        </Button>
                    </CardContent>
                </Card>
            </div>
        );
    }

    return (
        <div className="min-h-screen bg-gray-50 p-6">
            <Head title="Test Teacher Sidebar" />
            
            <div className="max-w-6xl mx-auto">
                <div className="mb-6">
                    <h1 className="text-3xl font-bold text-gray-900">Teacher Sidebar API Test</h1>
                    <p className="text-gray-600 mt-2">Testing the teacher sidebar data fetching functionality</p>
                    <Button onClick={fetchData} className="mt-4">
                        Refresh Data
                    </Button>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Session Requests */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="h-5 w-5" />
                                Session Requests ({data?.pendingRequestCount || 0})
                            </CardTitle>
                            <CardDescription>
                                Pending session requests from students
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {data?.sessionRequests && data.sessionRequests.length > 0 ? (
                                <div className="space-y-3">
                                    {data.sessionRequests.map((request) => (
                                        <div key={request.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                            <div className="flex items-center gap-3">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={request.student.avatar} />
                                                    <AvatarFallback>
                                                        {request.student.name.charAt(0)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                <div>
                                                    <p className="font-medium text-sm">{request.student.name}</p>
                                                    <p className="text-xs text-gray-500">{request.subject}</p>
                                                    <p className="text-xs text-gray-400">{request.time_ago}</p>
                                                </div>
                                            </div>
                                            <div className="flex gap-1">
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleAcceptRequest(request.id)}
                                                    className="h-6 w-6 p-0 bg-green-600 hover:bg-green-700"
                                                >
                                                    <CheckCircle className="h-3 w-3" />
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => handleDeclineRequest(request.id)}
                                                    className="h-6 w-6 p-0"
                                                >
                                                    <XCircle className="h-3 w-3" />
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">No pending session requests</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Messages */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MessageCircle className="h-5 w-5" />
                                Messages ({data?.unreadMessageCount || 0})
                            </CardTitle>
                            <CardDescription>
                                Recent messages and notifications
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {data?.messages && data.messages.length > 0 ? (
                                <div className="space-y-3">
                                    {data.messages.map((message) => (
                                        <div key={message.id} className="flex items-start gap-3 p-3 bg-gray-50 rounded-lg">
                                            <Avatar className="h-8 w-8">
                                                <AvatarImage src={message.sender.avatar} />
                                                <AvatarFallback>
                                                    {message.sender.name.charAt(0)}
                                                </AvatarFallback>
                                            </Avatar>
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-sm">{message.sender.name}</p>
                                                    {!message.is_read && (
                                                        <Badge variant="secondary" className="text-xs">
                                                            Unread
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="text-sm text-gray-600 mt-1">{message.message}</p>
                                                <p className="text-xs text-gray-400 mt-1">{message.time_ago}</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">No recent messages</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Online Students */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Online Students ({data?.onlineStudents?.length || 0})
                            </CardTitle>
                            <CardDescription>
                                Students currently online
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {data?.onlineStudents && data.onlineStudents.length > 0 ? (
                                <div className="space-y-3">
                                    {data.onlineStudents.map((student) => (
                                        <div key={student.id} className="flex items-center gap-3 p-3 bg-gray-50 rounded-lg">
                                            <div className="relative">
                                                <Avatar className="h-8 w-8">
                                                    <AvatarImage src={student.avatar} />
                                                    <AvatarFallback>
                                                        {student.name.charAt(0)}
                                                    </AvatarFallback>
                                                </Avatar>
                                                {student.is_online && (
                                                    <div className="absolute -bottom-1 -right-1 h-3 w-3 bg-green-500 rounded-full border-2 border-white"></div>
                                                )}
                                            </div>
                                            <div>
                                                <p className="font-medium text-sm">{student.name}</p>
                                                <p className="text-xs text-green-600">Online</p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-gray-500 text-sm">No students online</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Raw Data Display */}
                <Card className="mt-6">
                    <CardHeader>
                        <CardTitle>Raw API Response</CardTitle>
                        <CardDescription>
                            Complete data structure returned by the API
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <pre className="bg-gray-100 p-4 rounded-lg overflow-auto text-xs">
                            {JSON.stringify(data, null, 2)}
                        </pre>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}
