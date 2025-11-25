import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { ScrollArea } from '@/components/ui/scroll-area';
import { MessageSquare } from 'lucide-react';
import { format, isToday, isYesterday } from 'date-fns';
import { useInitials } from '@/hooks/use-initials';
import { Conversation, Message as MessageType, User } from '@/types';
import { RefObject, useState } from 'react';
import { MessageBubble } from './message-bubble';
import { MessageHeader } from './message-header';
import { MessageInput } from './message-input';
import AttachmentPreview, { AttachmentFile } from './attachment-preview';
import { validateFiles } from '@/lib/upload-utils';
import { showError, showValidationErrors, showInfo } from '@/lib/toast-utils';
import { compressImages } from '@/lib/image-compression';

interface MessageAreaProps {
    selectedConversation?: Conversation;
    messages?: MessageType[];
    currentUserId: number;
    messageText: string;
    onMessageChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onSendMessage: (content?: string, type?: string, files?: Array<{ file: File; type: string; metadata?: any }>) => void;
    onSendTextMessage: () => void;
    isLoading: boolean;
    typingUsers: string[];
    scrollRef: RefObject<HTMLDivElement | null>;
}

export function MessageArea({
    selectedConversation,
    messages,
    currentUserId,
    messageText,
    onMessageChange,
    onSendMessage,
    onSendTextMessage,
    isLoading,
    typingUsers,
    scrollRef,
}: MessageAreaProps) {
    const getInitials = useInitials();
    
    // State for attachments
    const [selectedFiles, setSelectedFiles] = useState<AttachmentFile[]>([]);
    const [showAttachmentPreview, setShowAttachmentPreview] = useState(false);

    const handleImageSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        // Validate files
        const fileArray = Array.from(files);
        const validation = validateFiles(fileArray, 'image');

        if (!validation.valid) {
            showValidationErrors(validation.errors);
            e.target.value = '';
            return;
        }

        // Compress images before preview
        try {
            showInfo('Compressing images...');
            const compressedFiles = await compressImages(validation.validFiles);

            // Create preview data for compressed files
            const attachmentFiles: AttachmentFile[] = compressedFiles.map(file => ({
                file,
                type: 'image',
                preview: URL.createObjectURL(file)
            }));

            setSelectedFiles(attachmentFiles);
            setShowAttachmentPreview(true);
        } catch (error) {
            console.error('Image compression failed:', error);
            showError('Failed to compress images. Please try again.');
        }

        e.target.value = '';
    };

    const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        // Validate files
        const fileArray = Array.from(files);
        const validation = validateFiles(fileArray, 'file');

        if (!validation.valid) {
            showValidationErrors(validation.errors);
            e.target.value = '';
            return;
        }

        // Create preview data for valid files
        const attachmentFiles: AttachmentFile[] = validation.validFiles.map(file => ({
            file,
            type: file.type.startsWith('image/') ? 'image' : 'file',
            preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : undefined
        }));

        setSelectedFiles(attachmentFiles);
        setShowAttachmentPreview(true);

        e.target.value = '';
    };

    const handleRemoveFile = (index: number) => {
        setSelectedFiles(prev => {
            const newFiles = [...prev];
            // Revoke object URL to free memory
            if (newFiles[index].preview) {
                URL.revokeObjectURL(newFiles[index].preview!);
            }
            newFiles.splice(index, 1);
            return newFiles;
        });
    };

    const handleConfirmAttachments = () => {
        // Prepare files for upload
        const filesToUpload = selectedFiles.map(file => ({
            file: file.file,
            type: file.type,
            metadata: {}
        }));

        // Determine message type based on first file
        const messageType = selectedFiles[0]?.type === 'image' ? 'image' : 'file';

        // Send message with attachments
        onSendMessage('', messageType, filesToUpload);

        // Clean up
        selectedFiles.forEach(file => {
            if (file.preview) {
                URL.revokeObjectURL(file.preview);
            }
        });
        
        setShowAttachmentPreview(false);
        setSelectedFiles([]);
    };

    const handleCancelAttachments = () => {
        // Revoke all object URLs
        selectedFiles.forEach(file => {
            if (file.preview) {
                URL.revokeObjectURL(file.preview);
            }
        });
        setSelectedFiles([]);
        setShowAttachmentPreview(false);
    };

    const handleVoiceMessage = (audioFile: File, duration: number) => {
        // Send voice message
        onSendMessage('', 'voice', [
            {
                file: audioFile,
                type: 'voice',
                metadata: { duration }
            }
        ]);
    };

    if (!selectedConversation) {
        return (
            <div className="flex-1 bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col">
                <div className="flex items-center justify-center h-full text-center">
                    <div>
                        <MessageSquare className="mx-auto h-16 w-16 text-gray-400 mb-4" />
                        <h3 className="text-lg font-semibold text-gray-900">Select a conversation</h3>
                        <p className="text-sm text-gray-500 mt-1">
                            Choose a conversation from the list to start messaging
                        </p>
                    </div>
                </div>
            </div>
        );
    }

    const otherParticipant = selectedConversation.participants?.find(
        (p: User) => p.id !== currentUserId
    );

    const formatMessageDate = (date: string) => {
        const messageDate = new Date(date);
        if (isToday(messageDate)) return 'Today';
        if (isYesterday(messageDate)) return 'Yesterday';
        return format(messageDate, 'MMMM d, yyyy');
    };

    // Group messages by date
    const groupedMessages = messages?.reduce((groups: { [key: string]: MessageType[] }, message) => {
        const date = formatMessageDate(message.created_at);
        if (!groups[date]) {
            groups[date] = [];
        }
        groups[date].push(message);
        return groups;
    }, {});

    return (
        <div className="w-full h-full bg-white rounded-2xl shadow-lg overflow-hidden flex flex-col">
            {/* Header */}
            <MessageHeader 
                otherParticipant={otherParticipant}
                currentUserId={currentUserId}
            />

            {/* Messages */}
            <ScrollArea className="flex-1 px-4 md:px-6 py-4">
                {messages && messages.length > 0 ? (
                    <div className="space-y-6">
                        {groupedMessages && Object.entries(groupedMessages).map(([date, dateMessages]) => (
                            <div key={date}>
                                {/* Date Separator */}
                                <div className="flex justify-center mb-4">
                                    <span className="text-xs text-gray-500 bg-gray-100 px-3 py-1 rounded-full">
                                        {date}
                                    </span>
                                </div>

                                {/* Messages for this date */}
                                <div className="space-y-4">
                                    {dateMessages.map((message) => {
                                        const isOwn = message.sender_id === currentUserId;
                                        const isAdmin = (message as any).sender?.role === 'admin' || (message as any).sender?.role === 'super-admin';
                                        
                                        return (
                                            <MessageBubble
                                                key={message.id}
                                                message={message}
                                                isOwn={isOwn}
                                                otherParticipant={otherParticipant}
                                                isAdmin={isAdmin}
                                            />
                                        );
                                    })}
                                </div>
                            </div>
                        ))}

                        {/* Typing Indicator */}
                        {typingUsers.length > 0 && (
                            <div className="flex gap-2 items-start">
                                <Avatar className="h-10 w-10 flex-shrink-0">
                                    {otherParticipant?.avatar && (
                                        <AvatarImage
                                            src={otherParticipant.avatar}
                                            alt={otherParticipant.name}
                                        />
                                    )}
                                    <AvatarFallback className="bg-gray-300 text-gray-700 text-sm">
                                        {getInitials(otherParticipant?.name || 'User')}
                                    </AvatarFallback>
                                </Avatar>
                                <div className="flex flex-col">
                                    <span className="text-sm font-medium text-gray-900 mb-1 px-1">
                                        {otherParticipant?.name}
                                    </span>
                                    <div className="bg-gray-100 rounded-2xl rounded-tl-sm px-4 py-2.5">
                                        <p className="text-sm text-gray-500 italic">is typing...</p>
                                    </div>
                                </div>
                            </div>
                        )}
                        <div ref={scrollRef} />
                    </div>
                ) : (
                    <div className="flex items-center justify-center h-full text-center">
                        <div>
                            <MessageSquare className="mx-auto h-12 w-12 text-gray-400 mb-3" />
                            <p className="text-sm text-gray-500">
                                No messages yet. Start the conversation!
                            </p>
                        </div>
                    </div>
                )}
            </ScrollArea>

            {/* Attachment Preview */}
            {showAttachmentPreview && (
                <div className="px-6 py-4 border-t border-gray-100">
                    <AttachmentPreview
                        files={selectedFiles}
                        onRemove={handleRemoveFile}
                        onConfirm={handleConfirmAttachments}
                        onCancel={handleCancelAttachments}
                    />
                </div>
            )}

            {/* Input */}
            <MessageInput
                messageText={messageText}
                onMessageChange={onMessageChange}
                onSendMessage={onSendTextMessage}
                onVoiceMessage={handleVoiceMessage}
                onImageSelect={handleImageSelect}
                onFileSelect={handleFileSelect}
                isLoading={isLoading}
            />
        </div>
    );
}
