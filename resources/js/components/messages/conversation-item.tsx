import { Conversation, User } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { formatDistanceToNow } from 'date-fns';
import { useInitials } from '@/hooks/use-initials';
import { useOnlineStatus } from '@/hooks/use-online-status';
import { cn } from '@/lib/utils';

interface ConversationItemProps {
    conversation: Conversation;
    currentUserId: number;
    isSelected: boolean;
}

export function ConversationItem({ conversation, currentUserId, isSelected }: ConversationItemProps) {
    const getInitials = useInitials();
    const { isOnline } = useOnlineStatus();

    const otherParticipant = conversation.participants?.find(
        (p: User) => p.id !== currentUserId
    );
    const hasUnread = conversation.latest_message && !conversation.latest_message.read_at;

    return (
        <a
            href={`/student/messages/${conversation.id}`}
            className={cn(
                "block p-4 rounded-xl transition-all",
                isSelected 
                    ? "bg-cyan-100/60" 
                    : "hover:bg-gray-50"
            )}
        >
            <div className="flex items-start gap-3">
                <div className="relative flex-shrink-0">
                    <Avatar className="h-12 w-12">
                        {otherParticipant?.avatar && (
                            <AvatarImage
                                src={otherParticipant.avatar}
                                alt={otherParticipant.name}
                            />
                        )}
                        <AvatarFallback className="bg-teal-500 text-white text-sm font-medium">
                            {getInitials(otherParticipant?.name || 'User')}
                        </AvatarFallback>
                    </Avatar>
                    {otherParticipant && isOnline(otherParticipant.id) && (
                        <div className="absolute bottom-0 right-0 w-3 h-3 bg-green-500 border-2 border-white rounded-full" />
                    )}
                </div>

                <div className="flex-1 min-w-0">
                    <div className="flex items-start justify-between mb-1">
                        <h3 className="text-sm font-semibold text-gray-900 truncate">
                            {otherParticipant?.name}
                        </h3>
                        {conversation.latest_message && (
                            <span className="text-xs text-gray-500 ml-2 flex-shrink-0">
                                {formatDistanceToNow(
                                    new Date(conversation.latest_message.created_at),
                                    { addSuffix: false }
                                ).replace('about ', '')}
                            </span>
                        )}
                    </div>
                    {conversation.latest_message && (
                        <div className="flex items-center gap-2">
                            <p className="text-xs text-gray-600 truncate flex-1">
                                {conversation.latest_message.sender_id === currentUserId && (
                                    <span className="mr-1">
                                        {conversation.latest_message.read_at ? (
                                            <span className="text-blue-500" title="Read">✓✓</span>
                                        ) : (
                                            <span className="text-gray-400" title="Sent">✓</span>
                                        )}
                                    </span>
                                )}
                                {conversation.latest_message.content}
                            </p>
                            {hasUnread && conversation.latest_message.sender_id !== currentUserId && (
                                <span className="flex-shrink-0 w-2 h-2 bg-orange-500 rounded-full" title="Unread message" />
                            )}
                        </div>
                    )}
                </div>
            </div>
        </a>
    );
}
