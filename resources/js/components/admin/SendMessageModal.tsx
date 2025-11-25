import { useState, useEffect, useRef } from 'react';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { XCircle } from 'lucide-react';
import { toast } from 'sonner';
import axios from 'axios';
import { ConversationList } from '@/components/messages/conversation-list';
import { MessageArea } from '@/components/messages/message-area';
import { Conversation, Message as MessageType, User } from '@/types';
import { Button } from '@/components/ui/button';
import { VisuallyHidden } from '@radix-ui/react-visually-hidden';

const restoreBodyScroll = () => {
    document.body.style.overflow = '';
    document.body.style.pointerEvents = '';
};

interface SendMessageModalProps {
    isOpen: boolean;
    onClose: () => void;
    onSuccess?: () => void;
    recipientId?: number;
    recipientName?: string;
    currentUserId: number;
    currentUserRole: string;
}

export default function SendMessageModal({ 
    isOpen, 
    onClose, 
    onSuccess,
    recipientId,
    recipientName,
    currentUserId,
    currentUserRole
}: SendMessageModalProps) {
    const [conversations, setConversations] = useState<Conversation[]>([]);
    const [selectedConversation, setSelectedConversation] = useState<Conversation | undefined>();
    const [messages, setMessages] = useState<MessageType[]>([]);
    const [searchQuery, setSearchQuery] = useState('');
    const [messageText, setMessageText] = useState('');
    const [typingUsers, setTypingUsers] = useState<string[]>([]);
    const [isTyping, setIsTyping] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [isInitializing, setIsInitializing] = useState(false);
    const scrollRef = useRef<HTMLDivElement>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    // Initialize modal when it opens
    useEffect(() => {
        if (isOpen) {
            const initializeModal = async () => {
                console.log('🚀 Initializing modal with recipientId:', recipientId);
                setIsInitializing(true);
                
                try {
                    // Fetch all conversations
                    await fetchConversations();
                    console.log('✅ Conversations fetched');
                    
                    // If recipientId is provided, create/select conversation with them
                    if (recipientId) {
                        console.log('👤 Creating/selecting conversation with recipient:', recipientId);
                        await createConversationWithRecipient();
                    }
                } catch (error) {
                    console.error('❌ Failed to initialize modal:', error);
                } finally {
                    setIsInitializing(false);
                }
            };
            
            initializeModal();
        }
    }, [isOpen, recipientId]);

    // Reset state when modal closes
    useEffect(() => {
        if (!isOpen) {
            restoreBodyScroll();
            
            const timer = setTimeout(() => {
                setConversations([]);
                setSelectedConversation(undefined);
                setMessages([]);
                setMessageText('');
                setTypingUsers([]);
                setIsTyping(false);
                setSearchQuery('');
            }, 200);
            
            return () => clearTimeout(timer);
        }
    }, [isOpen]);

    // Cleanup on unmount
    useEffect(() => {
        return () => {
            restoreBodyScroll();
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        };
    }, []);

    const fetchConversations = async () => {
        try {
            console.log('📡 Fetching conversations...');
            const response = await axios.get('/api/conversations');
            console.log('📡 Response:', response.data);
            
            if (response.data.success) {
                const conversationsData = response.data.data;
                // Ensure it's always an array
                const convs = Array.isArray(conversationsData) ? conversationsData : [];
                console.log('📋 Setting conversations:', convs.length, 'conversations');
                setConversations(convs);
            } else {
                console.warn('⚠️ API returned success=false');
                setConversations([]);
            }
        } catch (error) {
            console.error('❌ Failed to fetch conversations:', error);
            toast.error('Failed to load conversations');
            setConversations([]);
        }
    };

    const createConversationWithRecipient = async () => {
        if (!recipientId) {
            console.warn('⚠️ No recipientId provided');
            return;
        }

        try {
            console.log('🔍 Checking for existing conversation with recipient:', recipientId);
            console.log('📋 Current conversations:', conversations);
            
            // First check if conversation already exists in current list
            const existingConv = conversations.find(conv => 
                conv.participants?.some((p: User) => p.id === recipientId)
            );
            
            if (existingConv) {
                console.log('✅ Found existing conversation:', existingConv.id);
                await handleSelectConversation(existingConv);
                return;
            }

            console.log('➕ Creating new conversation with recipient:', recipientId);
            // Create new conversation
            const response = await axios.post('/api/conversations', {
                recipient_id: recipientId
            });

            console.log('📡 Create conversation response:', response.data);

            if (response.data.success && response.data.data) {
                const newConv = response.data.data;
                console.log('✅ New conversation created:', newConv.id);
                setConversations(prev => [newConv, ...prev]);
                await handleSelectConversation(newConv);
            } else {
                console.error('❌ Failed to create conversation - no data returned');
            }
        } catch (error) {
            console.error('❌ Failed to create conversation:', error);
            if (axios.isAxiosError(error) && error.response) {
                const errorData = error.response.data;
                console.error('❌ Error details:', errorData);
                toast.error(errorData.message || 'Failed to start conversation');
            } else {
                toast.error('Failed to start conversation');
            }
        }
    };

    const handleSelectConversation = async (conversation: Conversation) => {
        console.log('📬 Selecting conversation:', conversation.id);
        setSelectedConversation(conversation);
        setMessages([]);
        
        // Fetch conversation details which includes messages
        try {
            console.log('📡 Fetching conversation details:', conversation.id);
            const response = await axios.get(`/api/conversations/${conversation.id}`);
            console.log('📡 Conversation response:', response.data);
            
            if (response.data.success && response.data.data) {
                const convData = response.data.data;
                const msgs = convData.messages || [];
                console.log('📨 Setting messages:', msgs.length, 'messages');
                setMessages(msgs);
            }
        } catch (error) {
            console.error('❌ Failed to fetch conversation:', error);
            toast.error('Failed to load messages');
        }

        // Mark as read
        try {
            await axios.post(`/api/conversations/${conversation.id}/mark-read`);
        } catch (error) {
            console.error('❌ Failed to mark as read:', error);
        }
    };

    const handleSendMessage = async (content?: string, type?: string, files?: Array<{ file: File; type: string; metadata?: any }>) => {
        const textContent = content !== undefined ? content : messageText;
        
        if (!textContent.trim() && !files?.length) return;
        if (!selectedConversation) return;

        const otherParticipant = selectedConversation.participants?.find(
            (p: User) => p.id !== currentUserId
        );

        if (!otherParticipant) return;

        // Handle file uploads
        if (files && files.length > 0) {
            try {
                const formData = new FormData();
                formData.append('conversation_id', selectedConversation.id.toString());
                
                let messageType = type || 'file';
                if (type === 'voice') {
                    messageType = 'voice';
                } else if (type === 'image') {
                    messageType = 'image';
                }
                
                let messageContent = textContent;
                if (!messageContent || !messageContent.trim()) {
                    if (type === 'voice') {
                        messageContent = '🎤 Voice message';
                    } else if (type === 'image') {
                        messageContent = '📷 Image';
                    } else {
                        messageContent = '📎 File attachment';
                    }
                }
                
                formData.append('content', messageContent);
                formData.append('type', messageType);
                
                files.forEach((fileData) => {
                    formData.append('attachments[]', fileData.file);
                });

                const response = await axios.post('/api/messages', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                if (response.data.data) {
                    setMessages(prev => [...prev, response.data.data]);
                    
                    setConversations(prev =>
                        prev.map(conv => {
                            if (conv.id === selectedConversation.id) {
                                return {
                                    ...conv,
                                    latest_message: response.data.data
                                };
                            }
                            return conv;
                        })
                    );
                }

                return;
            } catch (error) {
                console.error('File upload failed:', error);
                toast.error('Failed to upload files');
                return;
            }
        }

        const messageToSend = textContent;

        // Create optimistic message
        const optimisticMessage: MessageType = {
            id: Date.now(),
            conversation_id: selectedConversation.id,
            sender_id: currentUserId,
            recipient_id: otherParticipant.id,
            content: messageToSend,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            read_at: null,
        };

        setMessages(prev => [...prev, optimisticMessage]);
        setMessageText('');

        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }
        sendTypingIndicator(false);
        setIsTyping(false);

        try {
            const response = await axios.post(`/api/conversations/${selectedConversation.id}/messages`, {
                content: messageToSend
            });

            if (response.data.success && response.data.data) {
                setMessages(prev =>
                    prev.map(msg => msg.id === optimisticMessage.id ? response.data.data : msg)
                );

                setConversations(prev =>
                    prev.map(conv => {
                        if (conv.id === selectedConversation.id) {
                            return {
                                ...conv,
                                latest_message: response.data.data
                            };
                        }
                        return conv;
                    })
                );

                if (onSuccess) onSuccess();
            }
        } catch (error) {
            console.error('Failed to send message:', error);
            toast.error('Failed to send message');
            // Remove optimistic message on error
            setMessages(prev => prev.filter(msg => msg.id !== optimisticMessage.id));
        }
    };

    const handleSendTextMessage = () => {
        handleSendMessage(messageText, 'text');
    };

    const sendTypingIndicator = (typing: boolean) => {
        if (!selectedConversation) return;
        if (typing === isTyping) return;

        setIsTyping(typing);
        axios.post(`/api/conversations/${selectedConversation.id}/typing`, {
            is_typing: typing
        }).catch(err => console.error('Failed to send typing indicator:', err));
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setMessageText(value);
        
        if (!selectedConversation) return;

        if (!value.trim()) {
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
                typingTimeoutRef.current = null;
            }
            sendTypingIndicator(false);
            return;
        }

        if (!isTyping) {
            sendTypingIndicator(true);
        }

        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        typingTimeoutRef.current = setTimeout(() => {
            sendTypingIndicator(false);
            typingTimeoutRef.current = null;
        }, 3000);
    };

    const handleClose = () => {
        restoreBodyScroll();
        onClose();
    };

    if (!isOpen) return null;

    return (
        <Dialog 
            open={isOpen} 
            onOpenChange={(open) => { 
                if (!open) {
                    handleClose();
                }
            }}
            modal={true}
        >
            <DialogContent 
                className="sm:max-w-[95vw] md:max-w-[90vw] lg:max-w-[1200px] h-[90vh] p-0"
                onPointerDownOutside={(e) => e.preventDefault()}
                onInteractOutside={(e) => e.preventDefault()}
            >
                <VisuallyHidden>
                    <DialogTitle>Admin Messages</DialogTitle>
                    <DialogDescription>
                        Send and manage messages with users
                    </DialogDescription>
                </VisuallyHidden>
                
                {/* Header */}
                <div className="flex items-center justify-between p-4 border-b bg-white">
                    <h2 className="text-xl font-semibold text-gray-900">
                        Messages {recipientName && `- ${recipientName}`}
                    </h2>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleClose}
                        className="text-gray-500 hover:text-gray-700"
                    >
                        <XCircle className="w-5 h-5" />
                    </Button>
                </div>

                {/* Content */}
                <div className="flex h-[calc(90vh-80px)] bg-gray-50">
                    {isInitializing ? (
                        <div className="flex items-center justify-center w-full h-full">
                            <div className="text-center">
                                <div className="w-12 h-12 border-4 border-teal-600 border-t-transparent rounded-full animate-spin mx-auto mb-4"></div>
                                <p className="text-gray-600">Loading messages...</p>
                            </div>
                        </div>
                    ) : (
                        <>
                            {/* Conversation List */}
                            <div className={`${selectedConversation ? 'hidden md:block' : 'block'} w-full md:w-80 lg:w-96 h-full flex-shrink-0 border-r bg-white`}>
                                <ConversationList
                                    conversations={conversations}
                                    selectedConversationId={selectedConversation?.id}
                                    currentUserId={currentUserId}
                                    currentUserRole={currentUserRole}
                                    searchQuery={searchQuery}
                                    onSearchChange={setSearchQuery}
                                    onConversationSelect={(convId) => {
                                        const conv = conversations.find(c => c.id === convId);
                                        if (conv) handleSelectConversation(conv);
                                    }}
                                />
                            </div>

                            {/* Message Area */}
                            <div className={`${!selectedConversation ? 'hidden md:flex' : 'flex'} flex-1 h-full min-w-0`}>
                                <MessageArea
                                    selectedConversation={selectedConversation}
                                    messages={messages}
                                    currentUserId={currentUserId}
                                    messageText={messageText}
                                    onMessageChange={handleInputChange}
                                    onSendMessage={handleSendMessage}
                                    onSendTextMessage={handleSendTextMessage}
                                    isLoading={isLoading}
                                    typingUsers={typingUsers}
                                    scrollRef={scrollRef}
                                />
                            </div>
                        </>
                    )}
                </div>
            </DialogContent>
        </Dialog>
    );
}
