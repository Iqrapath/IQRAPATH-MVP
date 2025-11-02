import React, { useState } from 'react';
import { Button } from '@/components/ui/button';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useMessages } from '@/hooks/use-messages';
import MessageIcon from '@/components/icons/message-icon';
import { formatDistanceToNow } from 'date-fns';
import { Check, Trash2, MessageSquare, Plus } from 'lucide-react';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { User } from '@/types';

interface MessageDropdownProps {
  className?: string;
  iconSize?: number;
}

export function MessageDropdown({ className, iconSize = 24 }: MessageDropdownProps) {
  const {
    messages,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead,
    deleteMessage,
  } = useMessages();
  
  const getInitials = useInitials();
  
  // Group messages by sender for conversation view
  const conversationsByUser: Record<number, {
    user: User | undefined;
    lastMessage: string;
    unreadCount: number;
    timestamp: string;
  }> = {};
  
  // Safely handle messages array
  if (messages && Array.isArray(messages)) {
    messages.forEach(message => {
      const otherUserId = message.sender_id;
      const otherUser = message.sender;
      
      if (!conversationsByUser[otherUserId]) {
        conversationsByUser[otherUserId] = {
          user: otherUser,
          lastMessage: message.content,
          unreadCount: message.read_at ? 0 : 1,
          timestamp: message.created_at
        };
      } else {
        // Update only if this message is newer
        const existingDate = new Date(conversationsByUser[otherUserId].timestamp);
        const messageDate = new Date(message.created_at);
        
        if (messageDate > existingDate) {
          conversationsByUser[otherUserId].lastMessage = message.content;
          conversationsByUser[otherUserId].timestamp = message.created_at;
        }
        
        if (!message.read_at) {
          conversationsByUser[otherUserId].unreadCount += 1;
        }
      }
    });
  }
  
  // Convert to array and sort by timestamp (newest first)
  const conversations = Object.values(conversationsByUser)
    .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime());

  const handleMarkAsRead = (messageId: number) => {
    markAsRead(messageId);
  };

  const handleDeleteMessage = (
    e: React.MouseEvent,
    messageId: number
  ) => {
    e.preventDefault();
    e.stopPropagation();
    deleteMessage(messageId);
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="ghost" size="icon" className={cn("relative", className)}>
          <MessageIcon style={{ width: iconSize, height: iconSize }} />
          {unreadCount > 0 && (
            <span className="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-[10px] font-medium text-white">
              {unreadCount > 99 ? '99+' : unreadCount}
            </span>
          )}
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent align="end" className="w-80">
        <DropdownMenuLabel className="flex items-center justify-between">
          <span>Messages</span>
          <div className="flex items-center gap-2">
            {unreadCount > 0 && (
              <Button
                variant="ghost"
                size="sm"
                className="h-8 text-xs"
                onClick={() => markAllAsRead()}
              >
                <Check className="mr-1 h-3 w-3" />
                Mark all as read
              </Button>
            )}
            <Link href="/messages/new">
              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                <Plus className="h-4 w-4" />
              </Button>
            </Link>
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        {isLoading && (
          <div className="flex justify-center py-4">
            <div className="h-6 w-6 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
          </div>
        )}
        
        {!isLoading && conversations.length === 0 && (
          <div className="flex flex-col items-center justify-center py-6 px-4 text-center">
            <MessageSquare className="mb-2 h-8 w-8 text-muted-foreground" />
            <p className="text-sm font-medium">No messages</p>
            <p className="text-xs text-muted-foreground">
              Start a conversation with someone to see messages here.
            </p>
          </div>
        )}
        
        {!isLoading && conversations.length > 0 && (
          <ScrollArea className="h-[300px]">
            <DropdownMenuGroup>
              {conversations.map((conversation, index) => {
                const hasUnread = conversation.unreadCount > 0;
                return (
                  <Link href={`/messages/user/${conversation.user?.id}`} key={index}>
                    <DropdownMenuItem
                      className={cn(
                        "flex items-start gap-2 p-3 cursor-pointer",
                        hasUnread && "bg-muted/50"
                      )}
                    >
                      <Avatar className="h-10 w-10">
                        {conversation.user?.avatar && (
                          <AvatarImage src={conversation.user.avatar} alt={conversation.user?.name || 'User'} />
                        )}
                        <AvatarFallback className="bg-primary text-white">
                          {getInitials(conversation.user?.name || 'User')}
                        </AvatarFallback>
                      </Avatar>
                      
                      <div className="flex-1 space-y-1">
                        <div className="flex items-center justify-between">
                          <p className="text-sm font-medium leading-none">
                            {conversation.user?.name || 'Unknown User'}
                          </p>
                          <p className="text-[10px] text-muted-foreground">
                            {formatDistanceToNow(new Date(conversation.timestamp), {
                              addSuffix: true,
                            })}
                          </p>
                        </div>
                        
                        <p className="text-xs text-muted-foreground line-clamp-1">
                          {conversation.lastMessage}
                        </p>
                        
                        {hasUnread && (
                          <div className="flex items-center gap-1 mt-1">
                            <span className="h-2 w-2 rounded-full bg-primary"></span>
                            <span className="text-[10px] text-primary font-medium">
                              {conversation.unreadCount} new {conversation.unreadCount === 1 ? 'message' : 'messages'}
                            </span>
                          </div>
                        )}
                      </div>
                    </DropdownMenuItem>
                  </Link>
                );
              })}
            </DropdownMenuGroup>
          </ScrollArea>
        )}
        
        <DropdownMenuSeparator />
        <Link
          href="/messages"
          className="block w-full rounded-sm px-3 py-2 text-center text-xs font-medium hover:bg-muted"
        >
          View all messages
        </Link>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export default MessageDropdown; 