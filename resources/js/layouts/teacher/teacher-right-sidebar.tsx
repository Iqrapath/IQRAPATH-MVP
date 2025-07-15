import { cn } from '@/lib/utils';
import { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import { X } from 'lucide-react';
import { Avatar } from '@/components/ui/avatar';
import { NotificationIcon } from '@/components/icons/notification-icon';
import { MessageUserIcon } from '@/components/icons/message-user-icon';

interface TeacherRightSidebarProps {
    children?: ReactNode;
    className?: string;
    isMobile?: boolean;
    onClose?: () => void;
}

export default function TeacherRightSidebar({ 
    children,
    className,
    isMobile = false,
    onClose
}: TeacherRightSidebarProps) {
    const defaultContent = (
        <div className="space-y-8 bg-[#f0f9f6] p-2 rounded-lg">
            {/* New Session Requests Section */}
            <div className="rounded-lg bg-[#f0f9f6] p-4 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                    <MessageUserIcon className="h-6 w-6" />
                    <h3 className="text-lg font-bold text-gray-800">New Session Requests</h3>
                </div>
                
                <div className="bg-white rounded-lg p-4 shadow-sm">
                    <div className="flex items-center gap-3">
                        <div className="text-blue-500">
                            <NotificationIcon className="h-8 w-8" />
                        </div>
                        <div className="flex-1">
                            <h4 className="font-bold text-gray-800">Fatima Ibrahim</h4>
                            <p className="text-xs text-gray-600">Requested a Tajweed Class on March 10,5pm</p>
                            <p className="text-xs text-gray-500 mt-1">3 hours ago</p>
                        </div>
                    </div>
                    <div className="flex gap-2 mt-3">
                        <Button className="bg-teal-600 hover:bg-teal-700 text-white text-xs px-4 py-1 h-8 rounded-full">Accept</Button>
                        <Button variant="outline" className="border-red-500 text-red-500 hover:bg-red-50 text-xs px-4 py-1 h-8 rounded-full">Decline</Button>
                    </div>
                </div>
            </div>

            {/* Online Student Section */}
            <div className="rounded-lg bg-[#f0f9f6] p-4 shadow-sm">
                <div className="flex items-center gap-2 mb-4">
                    <MessageUserIcon className="h-6 w-6" />
                    <h3 className="text-lg font-bold text-gray-800">Online Student</h3>
                </div>
                
                <div className="space-y-4">
                    {/* First message */}
                    <div className="flex gap-3">
                        <Avatar className="h-8 w-8">
                            <img src="/assets/images/landing/testimonial1.png" alt="Ahmed Khalid" />
                        </Avatar>
                        <div className="flex-1">
                            <div className="flex justify-between">
                                <h4 className="font-bold text-gray-800">Ahmed Khalid</h4>
                                <span className="text-xs text-gray-500">Jul 29</span>
                            </div>
                            <p className="text-xs text-gray-600">I'd like to book a session for Tajweed. Are you available this weekend?</p>
                        </div>
                    </div>

                    {/* Second message */}
                    <div className="flex gap-3">
                        <Avatar className="h-8 w-8">
                            <img src="/assets/images/landing/testimonial2.png" alt="Fatima Noor" />
                        </Avatar>
                        <div className="flex-1">
                            <div className="flex justify-between">
                                <h4 className="font-bold text-gray-800">Fatima Noor</h4>
                                <span className="text-xs text-gray-500">Yesterday</span>
                            </div>
                            <p className="text-xs text-gray-600">Thank you for the last lesson!</p>
                        </div>
                    </div>
                    
                    <Button variant="ghost" className="w-full text-teal-600 hover:text-teal-700 hover:bg-teal-50 text-sm">
                        View All Messages
                    </Button>
                </div>
            </div>
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