import { useState } from 'react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { 
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle
} from '@/components/ui/alert-dialog';
import { Heart, CheckCircle, XCircle } from 'lucide-react';

interface RequestCardProps {
    request: {
        id: number;
        student: {
            id: number;
            name: string;
            avatar?: string | null;
            level: string;
        };
        subject: string;
        requestedDays: string;
        requestedTime: string;
        subjects: string[];
        note: string;
        status: 'pending' | 'accepted' | 'declined' | 'expired';
        price?: number;
        priceUSD?: number;
    };
    onAccept: (request: any) => void;
    onDecline: (request: any) => void;
    onFavorite?: (request: any) => void;
    isLoading?: boolean;
}

export function RequestCard({ 
    request, 
    onAccept, 
    onDecline, 
    onFavorite,
    isLoading = false 
}: RequestCardProps) {
    const [isFavorited, setIsFavorited] = useState(false);
    const [showAcceptDialog, setShowAcceptDialog] = useState(false);
    const [showDeclineDialog, setShowDeclineDialog] = useState(false);

    const getInitials = (name: string) => {
        return name
            .split(' ')
            .map(word => word.charAt(0))
            .join('')
            .toUpperCase()
            .slice(0, 2);
    };

    const handleFavorite = () => {
        setIsFavorited(!isFavorited);
        onFavorite?.(request);
    };

    const handleAcceptClick = () => {
        setShowAcceptDialog(true);
    };

    const handleDeclineClick = () => {
        setShowDeclineDialog(true);
    };

    const confirmAccept = () => {
        onAccept(request);
        setShowAcceptDialog(false);
    };

    const confirmDecline = () => {
        onDecline(request);
        setShowDeclineDialog(false);
    };

    return (
        <>
        <Card className="w-full max-w-md mx-auto bg-white rounded-2xl shadow-sm border border-gray-100 hover:shadow-md transition-all duration-200">
            <CardContent className="p-6">
                {/* Header Section */}
                <div className="flex items-start justify-between mb-4">
                    <div className="flex items-center space-x-3">
                        <Avatar className="w-16 h-16">
                            <AvatarImage src={request.student.avatar || undefined} />
                            <AvatarFallback className="bg-teal-100 text-teal-800 text-lg font-semibold">
                                {getInitials(request.student.name)}
                            </AvatarFallback>
                        </Avatar>
                        <div>
                            <h3 className="text-xl font-bold text-gray-900">{request.student.name}</h3>
                            <p className="text-sm text-gray-600">
                                {request.subject} ({request.student.level})
                            </p>
                        </div>
                    </div>
                    
                    {/* Favorite Button */}
                    <button
                        onClick={handleFavorite}
                        className="p-2 rounded-full hover:bg-gray-100 transition-colors"
                    >
                        <Heart 
                            className={`w-5 h-5 ${
                                isFavorited 
                                    ? 'fill-pink-500 text-pink-500' 
                                    : 'text-gray-400 hover:text-pink-500'
                            }`} 
                        />
                    </button>
                </div>

                {/* Description */}
                <p className="text-gray-700 text-sm mb-4 leading-relaxed">
                    {request.note}
                </p>

                {/* Requested Details */}
                <div className="space-y-3 mb-6">
                    <div className="flex items-start text-sm">
                        <span className="text-gray-600 font-medium w-24">Requested Days:</span>
                        <span className="text-gray-900">{request.requestedDays}</span>
                    </div>
                    
                    <div className="flex items-start text-sm">
                        <span className="text-gray-600 font-medium w-24">Time:</span>
                        <span className="text-gray-900">{request.requestedTime}</span>
                    </div>
                    
                    <div className="flex items-start text-sm">
                        <span className="text-gray-600 font-medium w-24">Subject:</span>
                        <div className="flex flex-wrap gap-2">
                            {request.subjects.map((subject, index) => (
                                <Badge 
                                    key={index}
                                    className="px-3 py-1 text-xs bg-gray-100 text-gray-700 rounded-full"
                                >
                                    {subject}
                                </Badge>
                            ))}
                        </div>
                    </div>
                </div>

                {/* Pricing and Actions */}
                <div className="flex items-center justify-between">
                    <div className="text-sm">
                        {request.priceUSD && request.price && (
                            <div className="text-gray-900 font-semibold">
                                ${request.priceUSD} / ₦{request.price.toLocaleString()}
                            </div>
                        )}
                        <div className="text-gray-500 text-xs">Per session</div>
                    </div>
                    
                    <div className="flex items-center space-x-3">
                        {request.status === 'pending' ? (
                            <>
                                <Button
                                    size="sm"
                                    onClick={handleAcceptClick}
                                    disabled={isLoading}
                                    className="bg-teal-600 hover:bg-teal-700 text-white px-6 py-2 rounded-full font-medium"
                                >
                                    {isLoading ? (
                                        <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                    ) : (
                                        'Accept'
                                    )}
                                </Button>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    onClick={handleDeclineClick}
                                    disabled={isLoading}
                                    className="border-teal-600 text-teal-600 hover:bg-teal-50 px-6 py-2 rounded-full font-medium"
                                >
                                    Decline
                                </Button>
                            </>
                        ) : (
                            <Badge 
                                className={`px-3 py-1 text-xs ${
                                    request.status === 'accepted' 
                                        ? 'bg-green-100 text-green-800' 
                                        : request.status === 'declined'
                                        ? 'bg-red-100 text-red-800'
                                        : 'bg-gray-100 text-gray-800'
                                }`}
                            >
                                {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                            </Badge>
                        )}
                    </div>
                </div>
            </CardContent>
        </Card>

        {/* Accept Confirmation Dialog */}
        <AlertDialog open={showAcceptDialog} onOpenChange={setShowAcceptDialog}>
            <AlertDialogContent className="sm:max-w-md">
                <AlertDialogHeader>
                    <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                            <CheckCircle className="w-6 h-6 text-green-600" />
                        </div>
                        <div>
                            <AlertDialogTitle>Accept Request</AlertDialogTitle>
                            <AlertDialogDescription>
                                Are you sure you want to accept this booking request?
                            </AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>
                
                <div className="py-4">
                    <div className="bg-gray-50 rounded-lg p-4">
                        <div className="flex items-center space-x-3 mb-3">
                            <Avatar className="w-10 h-10">
                                <AvatarImage src={request.student.avatar || undefined} />
                                <AvatarFallback className="bg-teal-100 text-teal-800 text-sm font-semibold">
                                    {getInitials(request.student.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h4 className="font-semibold text-gray-900">{request.student.name}</h4>
                                <p className="text-sm text-gray-600">{request.subject} ({request.student.level})</p>
                            </div>
                        </div>
                        <div className="text-sm text-gray-600 space-y-1">
                            <p><span className="font-medium">Days:</span> {request.requestedDays}</p>
                            <p><span className="font-medium">Time:</span> {request.requestedTime}</p>
                            {request.price && request.priceUSD && (
                                <p><span className="font-medium">Price:</span> ${request.priceUSD} / ₦{request.price?.toLocaleString()}</p>
                            )}
                        </div>
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isLoading}>
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={confirmAccept}
                        disabled={isLoading}
                        className="bg-green-600 hover:bg-green-700 text-white"
                    >
                        {isLoading ? (
                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        ) : (
                            'Yes, Accept'
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>

        {/* Decline Confirmation Dialog */}
        <AlertDialog open={showDeclineDialog} onOpenChange={setShowDeclineDialog}>
            <AlertDialogContent className="sm:max-w-md">
                <AlertDialogHeader>
                    <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                            <XCircle className="w-6 h-6 text-red-600" />
                        </div>
                        <div>
                            <AlertDialogTitle>Decline Request</AlertDialogTitle>
                            <AlertDialogDescription>
                                Are you sure you want to decline this booking request?
                            </AlertDialogDescription>
                        </div>
                    </div>
                </AlertDialogHeader>
                
                <div className="py-4">
                    <div className="bg-gray-50 rounded-lg p-4">
                        <div className="flex items-center space-x-3 mb-3">
                            <Avatar className="w-10 h-10">
                                <AvatarImage src={request.student.avatar || undefined} />
                                <AvatarFallback className="bg-teal-100 text-teal-800 text-sm font-semibold">
                                    {getInitials(request.student.name)}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <h4 className="font-semibold text-gray-900">{request.student.name}</h4>
                                <p className="text-sm text-gray-600">{request.subject} ({request.student.level})</p>
                            </div>
                        </div>
                        <div className="text-sm text-gray-600 space-y-1">
                            <p><span className="font-medium">Days:</span> {request.requestedDays}</p>
                            <p><span className="font-medium">Time:</span> {request.requestedTime}</p>
                            {request.price && request.priceUSD && (
                                <p><span className="font-medium">Price:</span> ${request.priceUSD} / ₦{request.price?.toLocaleString()}</p>
                            )}
                        </div>
                    </div>
                </div>

                <AlertDialogFooter>
                    <AlertDialogCancel disabled={isLoading}>
                        Cancel
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={confirmDecline}
                        disabled={isLoading}
                        className="bg-red-600 hover:bg-red-700 text-white"
                    >
                        {isLoading ? (
                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        ) : (
                            'Yes, Decline'
                        )}
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
        </>
    );
}
