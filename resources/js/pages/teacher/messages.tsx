import { Head } from '@inertiajs/react';
import TeacherLayout from '@/layouts/teacher/teacher-layout';
import { PageProps, Conversation, Message as MessageType, User } from '@/types';
import { useState, useEffect, useRef } from 'react';
import { useMessages } from '@/hooks/use-messages';
import { ConversationList } from '@/components/messages/conversation-list';
import { MessageArea } from '@/components/messages/message-area';
import axios from 'axios';

interface Props extends PageProps {
    conversations: Conversation[];
    selectedConversation?: Conversation;
    messages?: MessageType[];
}

export default function TeacherMessages({ auth, conversations: initialConversations, selectedConversation, messages: initialMessages }: Props) {
    const [searchQuery, setSearchQuery] = useState('');
    const [messageText, setMessageText] = useState('');
    const [typingUsers, setTypingUsers] = useState<string[]>([]);
    const [isTyping, setIsTyping] = useState(false);
    const [localMessages, setLocalMessages] = useState<MessageType[]>(initialMessages || []);
    const [conversations, setConversations] = useState<Conversation[]>(initialConversations || []);
    const scrollRef = useRef<HTMLDivElement>(null);
    const typingTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    const {
        sendMessage,
        isLoading,
    } = useMessages({
        enableRealtime: false, // Disable global real-time, we handle it per conversation
        initialFetch: false
    });

    const handleSendMessage = async (content?: string, type?: string, files?: Array<{ file: File; type: string; metadata?: any }>) => {
        // Use provided content or messageText
        const textContent = content !== undefined ? content : messageText;
        
        // For voice messages and attachments, content can be empty
        if (!textContent.trim() && !files?.length) return;
        if (!selectedConversation) return;

        const otherParticipant = selectedConversation.participants?.find(
            (p: User) => p.id !== auth.user.id
        );

        if (!otherParticipant) return;

        // Handle file uploads (voice, images, files)
        if (files && files.length > 0) {
            try {
                // Create FormData for file upload
                const formData = new FormData();
                
                // Add required fields
                formData.append('conversation_id', selectedConversation.id.toString());
                
                // Determine message type - send the actual type to backend
                let messageType = type || 'file';
                if (type === 'voice') {
                    messageType = 'voice'; // Keep voice as voice for proper validation
                } else if (type === 'image') {
                    messageType = 'image';
                }
                
                // Content is required - use descriptive text for attachments
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
                
                // Add files as attachments array
                files.forEach((fileData) => {
                    formData.append('attachments[]', fileData.file);
                });

                // Upload files and send message
                const response = await axios.post('/api/messages', formData, {
                    headers: {
                        'Content-Type': 'multipart/form-data',
                    },
                });

                // Add the new message to local state
                if (response.data.data) {
                    setLocalMessages(prev => [...prev, response.data.data]);
                    
                    // Update conversation's latest message
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
                if (axios.isAxiosError(error) && error.response) {
                    console.error('Error details:', error.response.data);
                }
                alert('Failed to upload files. Please try again.');
                return;
            }
        }

        const messageToSend = textContent;

        // Create optimistic message
        const optimisticMessage: MessageType = {
            id: Date.now(), // Temporary ID
            conversation_id: selectedConversation.id,
            sender_id: auth.user.id,
            recipient_id: otherParticipant.id,
            content: messageToSend,
            created_at: new Date().toISOString(),
            updated_at: new Date().toISOString(),
            read_at: null,
        };

        // Add optimistic message immediately
        setLocalMessages(prev => [...prev, optimisticMessage]);

        // Clear input immediately for instant feel
        setMessageText('');

        // Stop typing indicator
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }
        sendTypingIndicator(false);
        setIsTyping(false);

        // Send message in background
        const sentMessage = await sendMessage(otherParticipant.id, messageToSend);

        // Replace optimistic message with real one
        if (sentMessage) {
            setLocalMessages(prev =>
                prev.map(msg => msg.id === optimisticMessage.id ? sentMessage : msg)
            );

            // Update conversation's latest message
            setConversations(prev =>
                prev.map(conv => {
                    if (conv.id === selectedConversation.id) {
                        return {
                            ...conv,
                            latest_message: sentMessage
                        };
                    }
                    return conv;
                })
            );
        }
    };

    // Handler for text-only messages (from input field)
    const handleSendTextMessage = () => {
        handleSendMessage(messageText, 'text');
    };

    // Handle typing indicator
    const sendTypingIndicator = (typing: boolean) => {
        if (!selectedConversation) return;

        // Only send if state actually changed
        if (typing === isTyping) return;

        console.log(`📤 Sending typing indicator: ${typing}`);
        setIsTyping(typing);
        axios.post(`/api/conversations/${selectedConversation.id}/typing`, {
            is_typing: typing
        })
            .then(() => console.log('✅ Typing indicator sent successfully'))
            .catch(err => console.error('❌ Failed to send typing indicator:', err));
    };

    const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const value = e.target.value;
        setMessageText(value);

        if (!selectedConversation) return;

        // If input is empty, stop typing indicator immediately
        if (!value.trim()) {
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
                typingTimeoutRef.current = null;
            }
            sendTypingIndicator(false);
            return;
        }

        // Only send "start typing" on first keystroke (prevents API spam)
        if (!isTyping) {
            sendTypingIndicator(true);
        }

        // Clear existing timeout
        if (typingTimeoutRef.current) {
            clearTimeout(typingTimeoutRef.current);
        }

        // Stop typing after 1 second of inactivity
        typingTimeoutRef.current = setTimeout(() => {
            sendTypingIndicator(false);
            typingTimeoutRef.current = null;
        }, 3000);
    };

    // Reset messages when conversation changes
    useEffect(() => {
        setLocalMessages(initialMessages || []);
    }, [selectedConversation?.id, initialMessages]);

    // Mark messages as read when opening conversation
    useEffect(() => {
        if (!selectedConversation) return;

        axios.post(`/api/conversations/${selectedConversation.id}/mark-read`)
            .then(() => {
                // Update conversation's latest_message.read_at in local state
                setConversations(prev =>
                    prev.map(conv => {
                        if (conv.id === selectedConversation.id && conv.latest_message) {
                            return {
                                ...conv,
                                latest_message: {
                                    ...conv.latest_message,
                                    read_at: new Date().toISOString()
                                }
                            };
                        }
                        return conv;
                    })
                );
            })
            .catch(err => console.error('Failed to mark messages as read:', err));
    }, [selectedConversation?.id]);

    // Listen for new messages across ALL user's conversations (for conversation list updates)
    useEffect(() => {
        if (typeof window === 'undefined' || !window.Echo) {
            console.warn('⚠️ Echo not available');
            return;
        }

        console.log(`🔌 Connecting to user channel: user.${auth.user.id}`);
        const userChannel = window.Echo.private(`user.${auth.user.id}`);

        // Listen for new messages in any conversation
        userChannel.listen('.message.sent', (event: { message: MessageType }) => {
            console.log('📬 New message notification received:', event.message);

            // Update conversation list with new message
            setConversations(prev => {
                const updatedConversations = prev.map(conv => {
                    if (conv.id === event.message.conversation_id) {
                        return {
                            ...conv,
                            latest_message: {
                                ...event.message,
                                // Mark as unread if not currently viewing this conversation
                                read_at: selectedConversation?.id === conv.id ? new Date().toISOString() : null
                            }
                        };
                    }
                    return conv;
                });

                // Sort conversations by latest message time
                return updatedConversations.sort((a, b) => {
                    const aTime = a.latest_message?.created_at ? new Date(a.latest_message.created_at).getTime() : 0;
                    const bTime = b.latest_message?.created_at ? new Date(b.latest_message.created_at).getTime() : 0;
                    return bTime - aTime;
                });
            });
        });

        // Listen for read receipts
        userChannel.listen('.message.read', (event: { message: MessageType; user_id: number; read_at: string }) => {
            console.log('✅ Message read receipt received:', event);

            // Update conversation list to mark latest message as read
            setConversations(prev =>
                prev.map(conv => {
                    if (conv.id === event.message.conversation_id && conv.latest_message?.id === event.message.id) {
                        return {
                            ...conv,
                            latest_message: {
                                ...conv.latest_message,
                                read_at: event.read_at
                            }
                        };
                    }
                    return conv;
                })
            );
        });

        return () => {
            userChannel.stopListening('.message.sent');
            userChannel.stopListening('.message.read');
        };
    }, [auth.user.id, selectedConversation?.id]);

    // Listen for typing indicators and new messages in the CURRENT conversation
    useEffect(() => {
        if (!selectedConversation || typeof window === 'undefined' || !window.Echo) {
            console.warn('⚠️ Echo not available or no conversation selected');
            return;
        }

        console.log(`🔌 Connecting to conversation channel: conversation.${selectedConversation.id}`);
        const channel = window.Echo.private(`conversation.${selectedConversation.id}`);

        // Listen for typing indicators
        channel.listen('.typing.indicator', (event: { user_id: number; user_name: string; is_typing: boolean }) => {
            console.log('👀 Typing indicator received:', event);
            console.log('Current typing users:', typingUsers);

            if (event.user_id === auth.user.id) {
                console.log('⏭️ Skipping own typing indicator');
                return;
            }

            if (event.is_typing) {
                console.log('✍️ User started typing:', event.user_name);
                setTypingUsers(prev => {
                    const updated = !prev.includes(event.user_name) ? [...prev, event.user_name] : prev;
                    console.log('Updated typing users:', updated);
                    return updated;
                });
            } else {
                console.log('🛑 User stopped typing:', event.user_name);
                setTypingUsers(prev => {
                    const updated = prev.filter(name => name !== event.user_name);
                    console.log('Updated typing users:', updated);
                    return updated;
                });
            }
        });

        // Listen for new messages in this conversation
        channel.listen('.message.sent', (event: { message: MessageType }) => {
            const receivedAt = new Date();
            const sentAt = new Date(event.message.created_at);
            const delay = receivedAt.getTime() - sentAt.getTime();

            console.log('📨 Message received via WebSocket:', event.message);
            console.log(`⏱️ Delivery time: ${delay}ms`);

            // Don't add own messages (already added optimistically)
            if (event.message.sender_id === auth.user.id) {
                console.log('⏭️ Skipping own message');
                return;
            }

            // Add new message to local state
            setLocalMessages(prev => {
                // Avoid duplicates
                const exists = prev.some(m => m.id === event.message.id);
                if (exists) {
                    console.log('⚠️ Duplicate message, skipping');
                    return prev;
                }
                console.log('✅ Adding new message to local state');
                return [...prev, event.message];
            });

            // Immediately mark as read since we're viewing this conversation
            console.log('📖 Marking message as read immediately');
            axios.post(`/api/conversations/${selectedConversation.id}/mark-read`)
                .then(() => {
                    console.log('✅ Message marked as read');
                })
                .catch(err => console.error('❌ Failed to mark message as read:', err));
        });

        return () => {
            // Stop own typing indicator when leaving conversation
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
                typingTimeoutRef.current = null;
            }
            sendTypingIndicator(false);

            // Clear typing users list
            setTypingUsers([]);

            channel.stopListening('.typing.indicator');
            channel.stopListening('.message.sent');
        };
    }, [selectedConversation?.id, auth.user.id]);

    // Auto-scroll to bottom when new messages arrive
    useEffect(() => {
        if (scrollRef.current) {
            scrollRef.current.scrollIntoView({ behavior: 'smooth' });
        }
    }, [localMessages]);

    // Cleanup typing timeout on unmount
    useEffect(() => {
        return () => {
            if (typingTimeoutRef.current) {
                clearTimeout(typingTimeoutRef.current);
            }
        };
    }, []);

    return (
        <TeacherLayout pageTitle="Messages" showRightSidebar={false}>
            <Head title="Messages" />

            <div className="h-full -m-4 bg-gray-50 p-4 md:p-6">
                {/* Page Header */}
                <div className="mb-4 md:mb-6">
                    <h1 className="text-2xl md:text-3xl font-bold text-gray-900">
                        Messages
                    </h1>
                </div>
                
                {/* Conversation List and Message Area */}
                <div className="mx-auto max-w-7xl h-[calc(100vh-180px)] md:h-[calc(100vh-200px)]">
                    <div className="flex flex-col md:flex-row h-full gap-4 md:gap-6">
                        {/* Conversation List - Hidden on mobile when conversation is selected */}
                        <div className={`${selectedConversation ? 'hidden md:block' : 'block'} w-full md:w-80 lg:w-96 h-full flex-shrink-0`}>
                            <ConversationList
                                conversations={conversations}
                                selectedConversationId={selectedConversation?.id}
                                currentUserId={auth.user.id}
                                currentUserRole={auth.user.role || 'teacher'}
                                searchQuery={searchQuery}
                                onSearchChange={setSearchQuery}
                            />
                        </div>

                        {/* Message Area - Hidden on mobile when no conversation is selected */}
                        <div className={`${!selectedConversation ? 'hidden md:flex' : 'flex'} flex-1 h-full min-w-0`}>
                            <MessageArea
                                selectedConversation={selectedConversation}
                                messages={localMessages}
                                currentUserId={auth.user.id}
                                messageText={messageText}
                                onMessageChange={handleInputChange}
                                onSendMessage={handleSendMessage}
                                onSendTextMessage={handleSendTextMessage}
                                isLoading={isLoading}
                                typingUsers={typingUsers}
                                scrollRef={scrollRef}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </TeacherLayout>
    );
}
