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
import { Check, MessageSquare, Plus, AlertCircle, RefreshCw, ShieldAlert } from 'lucide-react';
import { Link, usePage } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { Skeleton } from '@/components/ui/skeleton';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { User, PageProps } from '@/types';

interface MessageDropdownProps {
  className?: string;
  iconSize?: number;
  onAuthError?: () => void;
  onPermissionError?: (error: { message: string; type: string }) => void;
}

export function MessageDropdown({ 
  className, 
  iconSize = 24,
  onAuthError,
  onPermissionError 
}: MessageDropdownProps) {
  const { auth } = usePage<PageProps>().props;
  const {
    messages,
    unreadCount,
    isLoading,
    error,
    authError,
    permissionError,
    fetchMessages,
    markAllAsRead,
    clearErrors,
  } = useMessages();
  
  const [isRetrying, setIsRetrying] = useState(false);
  
  const getInitials = useInitials();
  
  // Group messages by sender for conversation view
  const conversationsByUser: Record<number, {
    id: number;
    user: User | undefined;
    lastMessage: string;
    unreadCount: number;
    timestamp: string;
  }> = {};
  
  // Safely handle messages array
  if (messages && Array.isArray(messages)) {
    messages.forEach(message => {
      if (!message.conversation_id) return; // Skip messages without conversation ID
      
      const otherUserId = message.sender_id;
      const otherUser = message.sender;
      
      if (!conversationsByUser[otherUserId]) {
        conversationsByUser[otherUserId] = {
          id: message.conversation_id,
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

  const handleRetry = async () => {
    setIsRetrying(true);
    clearErrors();
    await fetchMessages();
    setIsRetrying(false);
  };

  // Trigger callbacks for errors
  React.useEffect(() => {
    if (authError && onAuthError) {
      onAuthError();
    }
  }, [authError, onAuthError]);

  React.useEffect(() => {
    if (permissionError && onPermissionError) {
      onPermissionError(permissionError);
    }
  }, [permissionError, onPermissionError]);

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
            {/* TODO: Implement new message functionality */}
            {/* <Link href="/messages/new">
              <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                <Plus className="h-4 w-4" />
              </Button>
            </Link> */}
          </div>
        </DropdownMenuLabel>
        <DropdownMenuSeparator />
        
        {/* Auth Error State */}
        {authError && (
          <div className="p-4">
            <Alert variant="destructive">
              <ShieldAlert className="h-4 w-4" />
              <AlertTitle>Authentication Required</AlertTitle>
              <AlertDescription className="mt-2 space-y-2">
                <p className="text-sm">{authError.message}</p>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={() => window.location.href = '/login'}
                  >
                    Log In
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={clearErrors}
                  >
                    Dismiss
                  </Button>
                </div>
              </AlertDescription>
            </Alert>
          </div>
        )}
        
        {/* Permission Error State */}
        {permissionError && !authError && (
          <div className="p-4">
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertTitle>Access Denied</AlertTitle>
              <AlertDescription className="mt-2 space-y-2">
                <p className="text-sm">{permissionError.message}</p>
                {permissionError.details && (
                  <p className="text-xs text-muted-foreground">
                    {permissionError.details.reason && `Reason: ${permissionError.details.reason}`}
                  </p>
                )}
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={handleRetry}
                    disabled={isRetrying}
                  >
                    <RefreshCw className={cn("mr-1 h-3 w-3", isRetrying && "animate-spin")} />
                    Retry
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={clearErrors}
                  >
                    Dismiss
                  </Button>
                </div>
              </AlertDescription>
            </Alert>
          </div>
        )}
        
        {/* Network/General Error State */}
        {error && !authError && !permissionError && (
          <div className="p-4">
            <Alert variant="destructive">
              <AlertCircle className="h-4 w-4" />
              <AlertTitle>Error Loading Messages</AlertTitle>
              <AlertDescription className="mt-2 space-y-2">
                <p className="text-sm">{error.message}</p>
                <div className="flex gap-2">
                  <Button
                    size="sm"
                    variant="outline"
                    onClick={handleRetry}
                    disabled={isRetrying}
                  >
                    <RefreshCw className={cn("mr-1 h-3 w-3", isRetrying && "animate-spin")} />
                    Retry
                  </Button>
                  <Button
                    size="sm"
                    variant="ghost"
                    onClick={clearErrors}
                  >
                    Dismiss
                  </Button>
                </div>
              </AlertDescription>
            </Alert>
          </div>
        )}
        
        {/* Loading Skeleton */}
        {isLoading && !error && !authError && !permissionError && (
          <div className="space-y-3 p-3">
            {[1, 2, 3].map((i) => (
              <div key={i} className="flex items-start gap-2">
                <Skeleton className="h-10 w-10 rounded-full" />
                <div className="flex-1 space-y-2">
                  <div className="flex items-center justify-between">
                    <Skeleton className="h-4 w-24" />
                    <Skeleton className="h-3 w-16" />
                  </div>
                  <Skeleton className="h-3 w-full" />
                  <Skeleton className="h-3 w-20" />
                </div>
              </div>
            ))}
          </div>
        )}
        
        {/* Empty State - No Authorized Conversations */}
        {!isLoading && !error && !authError && !permissionError && conversations.length === 0 && (
          <div className="flex flex-col items-center justify-center py-6 px-4 text-center">
            <MessageSquare className="mb-2 h-8 w-8 text-muted-foreground" />
            <p className="text-sm font-medium">No messages</p>
            <p className="text-xs text-muted-foreground">
              Start a conversation with someone to see messages here.
            </p>
          </div>
        )}
        
        {/* Conversation List */}
        {!isLoading && !error && !authError && !permissionError && conversations.length > 0 && (
          <ScrollArea className="h-[300px]">
            <DropdownMenuGroup>
              {conversations.map((conversation, index) => {
                if (!conversation.id) return null;
                const hasUnread = conversation.unreadCount > 0;
                const userRole = auth.user.role;
                const messagesRoute = userRole === 'teacher' ? 'teacher.messages.show' : 'student.messages.show';
                return (
                  <Link href={route(messagesRoute, conversation.id)} key={index}>
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
          href={route(auth.user.role === 'teacher' ? 'teacher.messages' : 'student.messages')}
          className="block w-full rounded-sm px-3 py-2 text-center text-xs font-medium hover:bg-muted"
        >
          View all messages
        </Link>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export default MessageDropdown; 