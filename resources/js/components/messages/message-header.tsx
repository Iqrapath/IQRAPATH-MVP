import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ArrowLeft, Phone, Video } from 'lucide-react';
import { router } from '@inertiajs/react';
import { useInitials } from '@/hooks/use-initials';
import { useOnlineStatus } from '@/hooks/use-online-status';
import { User } from '@/types';

interface MessageHeaderProps {
    otherParticipant?: User;
    currentUserId: number;
}

export function MessageHeader({ otherParticipant, currentUserId }: MessageHeaderProps) {
    const getInitials = useInitials();
    const { isOnline } = useOnlineStatus();

    return (
        <div className="px-6 py-4 border-b border-gray-100">
            <div className="flex items-center justify-between">
                <div className="flex items-center gap-3">
                    <button
                        onClick={() => router.visit(window.location.pathname.split('/messages')[0] + '/messages')}
                        className="md:hidden -ml-2 p-2 text-gray-600 hover:text-gray-900 hover:bg-gray-100 rounded-full transition-colors"
                        aria-label="Back to conversations"
                    >
                        <ArrowLeft className="h-5 w-5" />
                    </button>
                    <Avatar className="h-10 w-10 flex-shrink-0">
                        {otherParticipant?.avatar && (
                            <AvatarImage
                                src={otherParticipant.avatar}
                                alt={otherParticipant.name}
                            />
                        )}
                        <AvatarFallback className="bg-teal-100 text-teal-700 text-sm font-medium">
                            {getInitials(otherParticipant?.name || 'User')}
                        </AvatarFallback>
                    </Avatar>
                    <div className="min-w-0">
                        <h3 className="font-semibold text-gray-900 text-base truncate">
                            {otherParticipant?.name}
                        </h3>
                        <p className="text-xs text-gray-500 flex items-center gap-1">
                            {otherParticipant && isOnline(otherParticipant.id) && (
                                <span className="w-2 h-2 bg-green-500 rounded-full"></span>
                            )}
                            {otherParticipant && isOnline(otherParticipant.id) ? 'Online' : 'Offline'}
                        </p>
                    </div>
                </div>
                {/* <div className="flex items-center gap-2 md:gap-3 flex-shrink-0 border-b-3 border-teal-700 rounded-full">
                    <button className="flex items-center gap-1.5 md:gap-2 px-3 py-2 text-teal-600 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors">
                        <Phone className="h-4 w-4 md:h-5 md:w-5" />
                        <span className="hidden sm:inline font-medium text-sm md:text-base">Call</span>
                    </button>
                    <div className="hidden sm:block h-6 w-px bg-gray-200"></div>
                    <button className="flex items-center gap-1.5 md:gap-2 px-3 py-2 text-teal-600 hover:text-teal-700 hover:bg-teal-50 rounded-lg transition-colors">
                        <Video className="h-4 w-4 md:h-5 md:w-5" />
                        <span className="hidden sm:inline font-medium text-sm md:text-base">Start Class</span>
                    </button>
                </div> */}
            </div>
        </div>
    );
}
