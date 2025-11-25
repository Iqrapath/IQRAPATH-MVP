import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Search, MessageSquare } from 'lucide-react';
import { formatDistanceToNow } from 'date-fns';
import { useInitials } from '@/hooks/use-initials';
import { useOnlineStatus } from '@/hooks/use-online-status';
import { cn } from '@/lib/utils';
import { Conversation, User } from '@/types';
import { Link } from '@inertiajs/react';

interface ConversationListProps {
    conversations: Conversation[];
    selectedConversationId?: number;
    currentUserId: number;
    currentUserRole: string;
    searchQuery: string;
    onSearchChange: (query: string) => void;
    onConversationSelect?: (conversationId: number) => void;
}

export function ConversationList({
    conversations,
    selectedConversationId,
    currentUserId,
    currentUserRole,
    searchQuery,
    onSearchChange,
    onConversationSelect,
}: ConversationListProps) {
    const getInitials = useInitials();
    const { isOnline } = useOnlineStatus();

    const filteredConversations = conversations.filter(conv =>
        conv.participants?.some((p: User) =>
            p.id !== currentUserId &&
            p.name.toLowerCase().includes(searchQuery.toLowerCase())
        )
    );

    return (
        <div className="w-full h-full bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col">
            {/* Header */}
            <div className="p-4 md:p-6 border-b border-gray-100">
                <div className="flex items-center justify-between mb-4">
                    <h2 className="text-lg md:text-xl font-semibold text-gray-900">Messages</h2>
                    <span className="flex items-center justify-center min-w-[28px] md:min-w-[32px] h-7 md:h-8 px-2 bg-gray-100 rounded-full text-xs md:text-sm font-medium text-gray-700">
                        {conversations.length}
                    </span>
                </div>
                <div className="relative">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                    <Input
                        placeholder={
                            currentUserRole === 'student' ? 'Find a teacher by name' :
                            currentUserRole === 'teacher' ? 'Find a student by name' :
                            currentUserRole === 'guardian' ? 'Find a teacher by name' :
                            'Search conversations'
                        }
                        className="pl-10 bg-gray-50 border-0 rounded-lg h-11 focus-visible:ring-1 focus-visible:ring-gray-200"
                        value={searchQuery}
                        onChange={(e) => onSearchChange(e.target.value)}
                    />
                </div>
            </div>

            {/* Conversations */}
            <ScrollArea className="flex-1">
                {filteredConversations.length === 0 ? (
                    <div className="flex flex-col items-center justify-center py-12 text-center px-4">
                        <MessageSquare className="mb-3 h-12 w-12 text-gray-400" />
                        <h3 className="text-sm font-medium text-gray-900">No conversations</h3>
                        <p className="text-xs text-gray-500 mt-1">
                            {currentUserRole === 'student' && 'Start messaging your teachers'}
                            {currentUserRole === 'teacher' && 'Start messaging your students'}
                            {currentUserRole === 'guardian' && 'Start messaging your children\'s teachers'}
                            {(currentUserRole === 'admin' || currentUserRole === 'super-admin') && 'No conversations yet'}
                        </p>
                    </div>
                ) : (
                    <div className="space-y-1 p-4">
                        {filteredConversations.map((conversation) => {
                            const otherParticipant = conversation.participants?.find(
                                (p: User) => p.id !== currentUserId
                            );
                            const isSelected = selectedConversationId === conversation.id;
                            const hasUnread = conversation.latest_message && !conversation.latest_message.read_at;

                            return (
                                <Link
                                    key={conversation.id}
                                    href={`/${currentUserRole}/messages/${conversation.id}`}
                                    preserveScroll
                                    preserveState
                                    className={cn(
                                        "block p-4 rounded-xl transition-all cursor-pointer",
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
                                                        {conversation.latest_message.content}
                                                    </p>
                                                    {hasUnread && (
                                                        <span className="px-2 py-0.5 bg-orange-100 text-orange-600 text-[10px] font-medium rounded">
                                                            Unread
                                                        </span>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </ScrollArea>
        </div>
    );
}
