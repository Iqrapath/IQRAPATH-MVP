import React, { useState } from 'react';
import AppHeaderLayout from '@/layouts/app/app-header-layout';
import { useMessages } from '@/hooks/use-messages';
import { formatDistanceToNow } from 'date-fns';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Input } from '@/components/ui/input';
import { useInitials } from '@/hooks/use-initials';
import { Link } from '@inertiajs/react';
import { cn } from '@/lib/utils';
import { MessageSquare, Search, Send } from 'lucide-react';
import { usePage } from '@inertiajs/react';
import { type SharedData, User } from '@/types';

export default function MessagesPage() {
  const page = usePage<SharedData>();
  const { auth } = page.props;
  const getInitials = useInitials();
  const [searchQuery, setSearchQuery] = useState('');
  
  // Group messages by conversation
  const { messages, unreadCount, isLoading } = useMessages();
  
  // Group messages by user (conversation)
  const conversationsByUser: Record<number, {
    user: User | undefined;
    lastMessage: string;
    unreadCount: number;
    timestamp: string;
  }> = {};
  
  messages.forEach(message => {
    const otherUserId = message.sender_id === auth.user.id ? message.recipient_id : message.sender_id;
    const otherUser = message.sender_id === auth.user.id ? message.recipient : message.sender;
    
    if (!conversationsByUser[otherUserId]) {
      conversationsByUser[otherUserId] = {
        user: otherUser,
        lastMessage: message.content,
        unreadCount: message.read_at ? 0 : (message.recipient_id === auth.user.id ? 1 : 0),
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
      
      if (!message.read_at && message.recipient_id === auth.user.id) {
        conversationsByUser[otherUserId].unreadCount += 1;
      }
    }
  });
  
  // Convert to array and sort by timestamp (newest first)
  const conversations = Object.values(conversationsByUser)
    .sort((a, b) => new Date(b.timestamp).getTime() - new Date(a.timestamp).getTime())
    .filter(conversation => 
      !searchQuery || 
      conversation.user?.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
      conversation.lastMessage.toLowerCase().includes(searchQuery.toLowerCase())
    );

  return (
    <AppHeaderLayout breadcrumbs={[{ title: 'Messages', href: '/messages' }]}>
      <div className="container py-6">
        <div className="flex items-center justify-between mb-6">
          <h1 className="text-2xl font-bold">Messages</h1>
          <Link href="/messages/new">
            <Button>
              <MessageSquare className="mr-2 h-4 w-4" />
              New Message
            </Button>
          </Link>
        </div>

        <div className="relative mb-4">
          <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
          <Input 
            placeholder="Search messages..." 
            className="pl-10" 
            value={searchQuery}
            onChange={(e) => setSearchQuery(e.target.value)}
          />
        </div>

        {isLoading ? (
          <div className="flex justify-center py-12">
            <div className="h-8 w-8 animate-spin rounded-full border-2 border-primary border-t-transparent"></div>
          </div>
        ) : conversations.length === 0 ? (
          <div className="flex flex-col items-center justify-center py-12 text-center">
            <MessageSquare className="mb-3 h-12 w-12 text-muted-foreground" />
            <h3 className="text-lg font-medium">No messages</h3>
            <p className="text-sm text-muted-foreground mt-1">
              {searchQuery 
                ? "No messages match your search" 
                : "Start a conversation with someone to see messages here"}
            </p>
            {searchQuery && (
              <Button variant="outline" className="mt-4" onClick={() => setSearchQuery('')}>
                Clear search
              </Button>
            )}
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4">
            {conversations.map((conversation, index) => {
              const hasUnread = conversation.unreadCount > 0;
              return (
                <Link href={`/messages/user/${conversation.user?.id}`} key={index}>
                  <Card className={cn(
                    "transition-colors hover:bg-muted/50",
                    hasUnread && "bg-muted/30"
                  )}>
                    <CardHeader className="p-4">
                      <div className="flex items-center gap-4">
                        <Avatar className="h-12 w-12">
                          {conversation.user?.avatar && (
                            <AvatarImage src={conversation.user.avatar} alt={conversation.user?.name || 'User'} />
                          )}
                          <AvatarFallback className="bg-primary text-white">
                            {getInitials(conversation.user?.name || 'User')}
                          </AvatarFallback>
                        </Avatar>
                        
                        <div className="flex-1">
                          <div className="flex items-center justify-between">
                            <h3 className="font-medium">{conversation.user?.name || 'Unknown User'}</h3>
                            <span className="text-xs text-muted-foreground">
                              {formatDistanceToNow(new Date(conversation.timestamp), { addSuffix: true })}
                            </span>
                          </div>
                          
                          <div className="flex items-center justify-between mt-1">
                            <p className="text-sm text-muted-foreground line-clamp-1">
                              {conversation.lastMessage}
                            </p>
                            
                            {hasUnread && (
                              <span className="ml-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-medium text-white">
                                {conversation.unreadCount}
                              </span>
                            )}
                          </div>
                        </div>
                      </div>
                    </CardHeader>
                  </Card>
                </Link>
              );
            })}
          </div>
        )}
      </div>
    </AppHeaderLayout>
  );
} 