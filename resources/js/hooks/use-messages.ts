import { useState, useEffect, useCallback } from 'react';
import axios from 'axios';
import { Message, User } from '@/types';

interface UseMessagesOptions {
  pollingInterval?: number;
  initialFetch?: boolean;
  withUserId?: number;
}

interface UseMessagesReturn {
  messages: Message[];
  unreadCount: number;
  isLoading: boolean;
  error: Error | null;
  fetchMessages: () => Promise<void>;
  fetchMessagesWithUser: (userId: number) => Promise<void>;
  sendMessage: (recipientId: number, content: string) => Promise<Message | null>;
  markAsRead: (messageId: number) => Promise<void>;
  markAllAsRead: () => Promise<void>;
  deleteMessage: (messageId: number) => Promise<void>;
}

export const useMessages = ({
  pollingInterval = 30000, // 30 seconds by default
  initialFetch = true,
  withUserId,
}: UseMessagesOptions = {}): UseMessagesReturn => {
  const [messages, setMessages] = useState<Message[]>([]);
  const [unreadCount, setUnreadCount] = useState<number>(0);
  const [isLoading, setIsLoading] = useState<boolean>(false);
  const [error, setError] = useState<Error | null>(null);

  const fetchMessages = useCallback(async () => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await axios.get('/api/messages');
      setMessages(response.data.data);
      
      // Get unread count
      const unreadMessages = response.data.data.filter((message: Message) => !message.read_at);
      setUnreadCount(unreadMessages.length);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to fetch messages'));
    } finally {
      setIsLoading(false);
    }
  }, []);

  const fetchMessagesWithUser = useCallback(async (userId: number) => {
    setIsLoading(true);
    setError(null);
    
    try {
      const response = await axios.get(`/api/messages/user/${userId}`);
      setMessages(response.data.data);
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to fetch messages with user'));
    } finally {
      setIsLoading(false);
    }
  }, []);

  const sendMessage = useCallback(async (recipientId: number, content: string): Promise<Message | null> => {
    try {
      const response = await axios.post('/api/messages', {
        recipient_id: recipientId,
        content
      });
      
      // Add the new message to the list if we're in a conversation with this user
      if (withUserId === recipientId) {
        setMessages(prev => [...prev, response.data.data]);
      }
      
      return response.data.data;
    } catch (err) {
      setError(err instanceof Error ? err : new Error('Failed to send message'));
      return null;
    }
  }, [withUserId]);

  const markAsRead = useCallback(async (messageId: number) => {
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
      setError(err instanceof Error ? err : new Error('Failed to mark message as read'));
    }
  }, []);

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
      setError(err instanceof Error ? err : new Error('Failed to mark all messages as read'));
    }
  }, []);

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
      setError(err instanceof Error ? err : new Error('Failed to delete message'));
    }
  }, [messages]);

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

  // Polling for new messages
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
    fetchMessages,
    fetchMessagesWithUser,
    sendMessage,
    markAsRead,
    markAllAsRead,
    deleteMessage,
  };
}; 