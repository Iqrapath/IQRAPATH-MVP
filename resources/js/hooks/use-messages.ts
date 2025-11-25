import { useState, useEffect, useCallback } from 'react';
import axios, { AxiosError } from 'axios';
import { Message } from '@/types';
import { AuthorizationHelper, AuthorizationError } from '@/lib/authorization';
import { usePage } from '@inertiajs/react';

// Helper to ensure CSRF cookie is set
const ensureCsrfToken = async () => {
    try {
        await axios.get('/sanctum/csrf-cookie');
    } catch (error) {
        console.error('Failed to fetch CSRF token:', error);
    }
};

interface UseMessagesOptions {
    pollingInterval?: number; // Deprecated: Use real-time instead
    initialFetch?: boolean;
    withUserId?: number;
    enableRealtime?: boolean; // Enable real-time updates via Laravel Echo
}

interface UseMessagesReturn {
    messages: Message[];
    unreadCount: number;
    isLoading: boolean;
    error: Error | null;
    authError: AuthorizationError | null;
    permissionError: AuthorizationError | null;
    isAuthenticated: boolean;
    fetchMessages: () => Promise<void>;
    fetchMessagesWithUser: (userId: number) => Promise<void>;
    sendMessage: (recipientId: number, content: string) => Promise<Message | null>;
    markAsRead: (messageId: number) => Promise<void>;
    markAllAsRead: () => Promise<void>;
    deleteMessage: (messageId: number) => Promise<void>;
    clearErrors: () => void;
}

export const useMessages = ({
    pollingInterval = 0, // Disabled by default - use real-time instead
    initialFetch = true,
    withUserId,
    enableRealtime = true, // Real-time enabled by default
}: UseMessagesOptions = {}): UseMessagesReturn => {
    const [messages, setMessages] = useState<Message[]>([]);
    const [unreadCount, setUnreadCount] = useState<number>(0);
    const [isLoading, setIsLoading] = useState<boolean>(false);
    const [error, setError] = useState<Error | null>(null);
    const [authError, setAuthError] = useState<AuthorizationError | null>(null);
    const [permissionError, setPermissionError] = useState<AuthorizationError | null>(null);
    const [isAuthenticated, setIsAuthenticated] = useState<boolean>(true);
    
    // Get authenticated user from Inertia page props
    const { auth } = usePage<{ auth: { user: { id: number } } }>().props;

    const handleAuthError = useCallback((err: AxiosError) => {
        const authErrorData = AuthorizationHelper.handleApiError(err);

        if (authErrorData.type === 'auth') {
            setAuthError(authErrorData);
            setIsAuthenticated(false);
        } else if (authErrorData.type === 'permission') {
            setPermissionError(authErrorData);
        } else {
            setError(new Error(authErrorData.message));
        }
    }, []);

    const clearErrors = useCallback(() => {
        setError(null);
        setAuthError(null);
        setPermissionError(null);
    }, []);

    const fetchMessages = useCallback(async () => {
        setIsLoading(true);
        setError(null);
        setAuthError(null);
        setPermissionError(null);

        try {
            // Ensure CSRF token is set
            await ensureCsrfToken();
            
            const response = await axios.get('/api/conversations');
            
            // Extract messages from conversations
            const conversations = response.data.data.data || response.data.data;
            const allMessages: Message[] = [];
            let totalUnread = 0;
            
            // Collect all latest messages from conversations
            if (Array.isArray(conversations)) {
                conversations.forEach((conversation: any) => {
                    if (conversation.latestMessage) {
                        allMessages.push(conversation.latestMessage);
                        if (!conversation.latestMessage.read_at) {
                            totalUnread++;
                        }
                    }
                });
            }
            
            setMessages(allMessages);
            setUnreadCount(totalUnread);
            setIsAuthenticated(true);
        } catch (err) {
            if (axios.isAxiosError(err)) {
                handleAuthError(err);
            } else {
                setError(err instanceof Error ? err : new Error('Failed to fetch messages'));
            }
        } finally {
            setIsLoading(false);
        }
    }, [handleAuthError]);

  const fetchMessagesWithUser = useCallback(async (userId: number) => {
    setIsLoading(true);
    setError(null);
    setAuthError(null);
    setPermissionError(null);
    
    try {
      // Ensure CSRF token is set
      await ensureCsrfToken();
      
      // First, get or create conversation with this user
      const convResponse = await axios.post('/api/conversations', {
        recipient_id: userId
      });
      
      const conversationId = convResponse.data.data.id;
      
      // Then fetch messages for that conversation
      const messagesResponse = await axios.get(`/api/conversations/${conversationId}`);
      const messages = messagesResponse.data.data.messages.data || messagesResponse.data.data.messages;
      
      setMessages(Array.isArray(messages) ? messages : []);
      setIsAuthenticated(true);
    } catch (err) {
      if (axios.isAxiosError(err)) {
        handleAuthError(err);
      } else {
        setError(err instanceof Error ? err : new Error('Failed to fetch messages with user'));
      }
    } finally {
      setIsLoading(false);
    }
  }, [handleAuthError]);

  const sendMessage = useCallback(async (recipientId: number, content: string): Promise<Message | null> => {
    setError(null);
    setAuthError(null);
    setPermissionError(null);

    // Frontend validation: Validate recipient is a valid user ID
    if (!recipientId || recipientId <= 0 || !Number.isInteger(recipientId)) {
      setError(new Error('Invalid recipient ID'));
      return null;
    }

    // Frontend validation: Validate content is not empty
    if (!content || content.trim().length === 0) {
      setError(new Error('Message content cannot be empty'));
      return null;
    }

    try {
      // Ensure CSRF token is set
      await ensureCsrfToken();
      
      // First, get or create conversation with recipient
      const convResponse = await axios.post('/api/conversations', {
        recipient_id: recipientId
      });
      
      const conversationId = convResponse.data.data.id;
      
      // Then send the message in that conversation
      const response = await axios.post('/api/messages', {
        conversation_id: conversationId,
        content
      });
      
      // Add the new message to the list if we're in a conversation with this user
      if (withUserId === recipientId) {
        setMessages(prev => [...prev, response.data.data]);
      }
      
      return response.data.data;
    } catch (err) {
      if (axios.isAxiosError(err)) {
        handleAuthError(err);
      } else {
        setError(err instanceof Error ? err : new Error('Failed to send message'));
      }
      return null;
    }
  }, [withUserId, handleAuthError]);

  const markAsRead = useCallback(async (messageId: number) => {
    // Frontend validation: Verify message exists in local state
    const messageExists = messages.some(m => m.id === messageId);
    if (!messageExists) {
      setError(new Error('Message not found in local state'));
      return;
    }

    try {
      await axios.post(`/api/messages/${messageId}/read`);
      
      // Update local state
      setMessages(prev => 
        prev.map(message => 
          message.id === messageId 
            ? { ...message, read_at: new Date().toISOString() } 
            : message
        )
      );
      
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (err) {
      if (axios.isAxiosError(err)) {
        handleAuthError(err);
      } else {
        setError(err instanceof Error ? err : new Error('Failed to mark message as read'));
      }
    }
  }, [messages, handleAuthError]);

  const markAllAsRead = useCallback(async () => {
    try {
      await axios.post('/api/messages/read-all');
      
      // Update local state
      setMessages(prev => 
        prev.map(message => ({ 
          ...message, 
          read_at: message.read_at || new Date().toISOString() 
        }))
      );
      
      setUnreadCount(0);
    } catch (err) {
      if (axios.isAxiosError(err)) {
        handleAuthError(err);
      } else {
        setError(err instanceof Error ? err : new Error('Failed to mark all messages as read'));
      }
    }
  }, [handleAuthError]);

  const deleteMessage = useCallback(async (messageId: number) => {
    try {
      await axios.delete(`/api/messages/${messageId}`);
      
      // Update local state
      const deletedMessage = messages.find(m => m.id === messageId);
      setMessages(prev => prev.filter(message => message.id !== messageId));
      
      // Update unread count if the deleted message was unread
      if (deletedMessage && !deletedMessage.read_at) {
        setUnreadCount(prev => Math.max(0, prev - 1));
      }
    } catch (err) {
      if (axios.isAxiosError(err)) {
        handleAuthError(err);
      } else {
        setError(err instanceof Error ? err : new Error('Failed to delete message'));
      }
    }
  }, [messages, handleAuthError]);

  // Initial fetch
  useEffect(() => {
    if (initialFetch) {
      if (withUserId) {
        fetchMessagesWithUser(withUserId);
      } else {
        fetchMessages();
      }
    }
  }, [fetchMessages, fetchMessagesWithUser, initialFetch, withUserId]);

  // Real-time updates via Laravel Echo
  useEffect(() => {
    if (!enableRealtime || !auth?.user?.id || typeof window === 'undefined' || !window.Echo) {
      return;
    }

    const userId = auth.user.id;
    
    // Subscribe to user's private channel for new messages
    const channel = window.Echo.private(`user.${userId}`);
    
    // Listen for new messages
    channel.listen('.message.sent', (event: { message: Message }) => {
      console.log('Real-time message received:', event.message);
      
      // Add new message to the list
      setMessages(prev => {
        // Avoid duplicates
        const exists = prev.some(m => m.id === event.message.id);
        if (exists) return prev;
        return [...prev, event.message];
      });
      
      // Increment unread count if message is unread
      if (!event.message.read_at) {
        setUnreadCount(prev => prev + 1);
      }
    });
    
    // Listen for message read events
    channel.listen('.message.read', (event: { message: Message }) => {
      console.log('Message marked as read:', event.message);
      
      setMessages(prev =>
        prev.map(message =>
          message.id === event.message.id
            ? { ...message, read_at: event.message.read_at }
            : message
        )
      );
      
      // Decrement unread count
      setUnreadCount(prev => Math.max(0, prev - 1));
    });
    
    // Listen for message deleted events
    channel.listen('.message.deleted', (event: { messageId: number }) => {
      console.log('Message deleted:', event.messageId);
      
      setMessages(prev => {
        const deletedMessage = prev.find(m => m.id === event.messageId);
        if (deletedMessage && !deletedMessage.read_at) {
          setUnreadCount(prevCount => Math.max(0, prevCount - 1));
        }
        return prev.filter(message => message.id !== event.messageId);
      });
    });
    
    // Cleanup: Leave channel when component unmounts
    return () => {
      channel.stopListening('.message.sent');
      channel.stopListening('.message.read');
      channel.stopListening('.message.deleted');
      window.Echo.leave(`user.${userId}`);
    };
  }, [enableRealtime, auth?.user?.id]);

  // Polling for new messages (fallback, disabled by default)
  useEffect(() => {
    if (pollingInterval > 0) {
      const intervalId = setInterval(() => {
        if (withUserId) {
          fetchMessagesWithUser(withUserId);
        } else {
          fetchMessages();
        }
      }, pollingInterval);
      
      return () => clearInterval(intervalId);
    }
  }, [fetchMessages, fetchMessagesWithUser, pollingInterval, withUserId]);

  return {
    messages,
    unreadCount,
    isLoading,
    error,
    authError,
    permissionError,
    isAuthenticated,
    fetchMessages,
    fetchMessagesWithUser,
    sendMessage,
    markAsRead,
    markAllAsRead,
    deleteMessage,
    clearErrors,
  };
}; 