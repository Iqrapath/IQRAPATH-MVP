import { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { MoreVertical } from 'lucide-react';
import axios from 'axios';
import { toast } from 'sonner';

interface PendingRequest {
    id: number;
    student: {
        name: string;
        avatar?: string;
    };
    note: string;
    subject: string;
    requestedDate: string;
    requestedTime: string;
    status: 'pending';
}

interface PendingRequestTabProps {
    requests: PendingRequest[];
    onAccept: (request: PendingRequest) => void;
    onDecline: (request: PendingRequest) => void;
    onViewDetails: (request: PendingRequest) => void;
}

export function PendingRequestTab({ requests, onAccept, onDecline, onViewDetails }: PendingRequestTabProps) {
    const [loadingRequests, setLoadingRequests] = useState<Set<number>>(new Set());

    const handleAccept = async (request: PendingRequest) => {
        setLoadingRequests(prev => new Set(prev).add(request.id));
        
        try {
            const response = await axios.post(`/teacher/sessions/requests/${request.id}/accept`);
            
            if (response.data.success) {
                toast.success('Booking request accepted successfully!');
                onAccept(request);
            } else {
                toast.error(response.data.message || 'Failed to accept request');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to accept request');
        } finally {
            setLoadingRequests(prev => {
                const newSet = new Set(prev);
                newSet.delete(request.id);
                return newSet;
            });
        }
    };

    const handleDecline = async (request: PendingRequest) => {
        setLoadingRequests(prev => new Set(prev).add(request.id));
        
        try {
            const response = await axios.post(`/teacher/sessions/requests/${request.id}/decline`);
            
            if (response.data.success) {
                toast.success('Booking request declined successfully!');
                onDecline(request);
            } else {
                toast.error(response.data.message || 'Failed to decline request');
            }
        } catch (error: any) {
            toast.error(error.response?.data?.message || 'Failed to decline request');
        } finally {
            setLoadingRequests(prev => {
                const newSet = new Set(prev);
                newSet.delete(request.id);
                return newSet;
            });
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

    const formatDate = (dateString: string) => {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric' 
        });
    };

    return (
        <div className="space-y-6">
            {/* Section Title */}
            <h2 className="text-2xl font-bold text-gray-900">Pending Requests</h2>

            {/* Pending Requests Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-2 gap-6">
                {requests.length > 0 ? (
                    requests.map((request) => (
                        <Card key={request.id} className="bg-white rounded-lg border border-gray-200 shadow-sm">
                            <CardContent className="">
                                {/* Header with New Badge and Menu */}
                                <div className="flex items-center justify-between mb-4">
                                    <Badge className="bg-green-100 text-gray-700 px-3 py-1 rounded-full text-sm font-medium">
                                        New
                                    </Badge>
                                    <Button 
                                        variant="ghost" 
                                        size="sm" 
                                        className="p-1 h-auto text-gray-500 hover:text-gray-700"
                                        onClick={() => onViewDetails(request)}
                                    >
                                        <MoreVertical className="w-4 h-4" />
                                    </Button>
                                </div>

                                {/* Main Content - Side by Side Layout */}
                                <div className=" items-start gap-4 mb-4">
                                    {/* Left Side - Student Info */}
                                    <div className="flex items-center space-x-4">
                                        {/* Student Avatar */}
                                        <Avatar className="w-16 h-16">
                                            <AvatarImage src={request.student.avatar} />
                                            <AvatarFallback className="bg-orange-100 text-orange-800 text-lg font-semibold">
                                                {getInitials(request.student.name)}
                                            </AvatarFallback>
                                        </Avatar>

                                        {/* Student Name Only */}
                                        <div>
                                            <h3 className="text-lg font-bold text-gray-900">{request.student.name}</h3>
                                        </div>
                                    </div>

                                    {/* Note - Below both sections */}
                                <div className="mb-6 mt-4">
                                    <p className="text-sm text-gray-600">{request.note}</p>
                                </div>

                                    {/* Right Side - Session Details */}
                                    <div className="flex items-center space-x-4">
                                        <div className="space-y-3">
                                            <div>
                                                <span className="text-sm text-gray-600">Subject</span>
                                            </div>
                                            <div>
                                                <span className="text-sm text-gray-600">Requested Date:</span>
                                            </div>
                                            <div>
                                                <span className="text-sm text-gray-600">Requested Time:</span>
                                            </div>
                                        </div>
                                        <div className="space-y-3">
                                            <div>
                                                <span className="text-sm font-medium text-gray-900">{request.subject}</span>
                                            </div>
                                            <div>
                                                <span className="text-sm font-medium text-gray-900">{formatDate(request.requestedDate)}</span>
                                            </div>
                                            <div>
                                                <span className="text-sm font-medium text-gray-900">{request.requestedTime}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                

                                {/* Action Buttons */}
                                <div className="flex space-x-3">
                                    <Button 
                                        onClick={() => handleAccept(request)}
                                        disabled={loadingRequests.has(request.id)}
                                        className="flex bg-[#2C7870] hover:bg-[#2C7870]/90 text-white font-bold py-3 rounded-full disabled:opacity-50"
                                    >
                                        {loadingRequests.has(request.id) ? 'Processing...' : 'Accept'}
                                    </Button>
                                    <Button 
                                        variant="outline"
                                        onClick={() => handleDecline(request)}
                                        disabled={loadingRequests.has(request.id)}
                                        className="flex border-[#2C7870] text-[#2C7870] hover:bg-[#2C7870]/10 font-bold py-3 rounded-full disabled:opacity-50"
                                    >
                                        {loadingRequests.has(request.id) ? 'Processing...' : 'Decline'}
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                    ))
                ) : (
                    <Card className="bg-white rounded-lg border border-gray-200">
                        <CardContent className="p-8 text-center">
                            <div className="w-24 h-24 mx-auto mb-4 bg-gray-100 rounded-full flex items-center justify-center">
                                <MoreVertical className="w-12 h-12 text-gray-400" />
                            </div>
                            <h3 className="text-lg font-medium text-gray-800 mb-2">No Pending Requests</h3>
                            <p className="text-gray-600">You don't have any pending session requests at the moment.</p>
                        </CardContent>
                    </Card>
                )}
            </div>
        </div>
    );
}
