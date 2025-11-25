import { Message as MessageType, User } from '@/types';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { format } from 'date-fns';
import { cn } from '@/lib/utils';
import { useInitials } from '@/hooks/use-initials';
import AudioPlayer from './audio-player';
import ImageAttachment from './image-attachment';
import FileAttachment from './file-attachment';

interface MessageBubbleProps {
    message: MessageType;
    isOwn: boolean;
    otherParticipant?: User;
    isAdmin?: boolean;
}

export function MessageBubble({ message, isOwn, otherParticipant, isAdmin }: MessageBubbleProps) {
    const getInitials = useInitials();

    const formatMessageTime = (date: string) => {
        return format(new Date(date), 'h:mm a');
    };

    // Check if message contains only emojis (no text)
    const isOnlyEmojis = (content: string) => {
        const emojiRegex = /^[\u{1F300}-\u{1F9FF}\u{2600}-\u{26FF}\u{2700}-\u{27BF}\s]+$/u;
        return emojiRegex.test(content.trim());
    };

    const renderMessageContent = (content: string) => {
        const onlyEmojis = isOnlyEmojis(content);
        
        if (onlyEmojis) {
            // If only emojis, render them larger without splitting
            return <span style={{ fontSize: '48px', lineHeight: '1.2' }}>{content}</span>;
        }
        
        // Mixed content: render with larger emojis inline
        return content.split('').map((char, idx) => {
            const isEmoji = /[\u{1F300}-\u{1F9FF}]|[\u{2600}-\u{26FF}]|[\u{2700}-\u{27BF}]/u.test(char);
            return isEmoji ? (
                <span key={idx} style={{ fontSize: '24px', lineHeight: '1' }}>{char}</span>
            ) : (
                char
            );
        });
    };

    const onlyEmojis = isOnlyEmojis(message.content);

    return (
        <div
            className={cn(
                "flex gap-2",
                isOwn ? "justify-end" : "justify-start"
            )}
        >
            {/* Avatar for received messages */}
            {!isOwn && (
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
            )}

            <div className={cn("flex flex-col", isOwn ? "items-end" : "items-start")}>
                {/* Name and timestamp for received messages */}
                {!isOwn && (
                    <div className="flex items-center gap-2 mb-1 px-1">
                        <span className="text-sm font-medium text-gray-900">
                            {isAdmin ? 'Admin' : otherParticipant?.name}
                        </span>
                        <span className="text-xs text-gray-400">
                            {formatMessageTime(message.created_at)}
                        </span>
                    </div>
                )}

                {/* Attachments */}
                {message.attachments && message.attachments.length > 0 && (
                    <div className="space-y-2 mb-2 max-w-md">
                        {/* Voice attachments */}
                        {message.attachments
                            .filter(att => att.attachment_type === 'voice')
                            .map(attachment => (
                                <AudioPlayer
                                    key={attachment.id}
                                    src={`/api/attachments/${attachment.id}/download`}
                                    duration={attachment.duration}
                                />
                            ))}

                        {/* Image attachments */}
                        {(() => {
                            const images = message.attachments.filter(att => att.attachment_type === 'image');
                            if (images.length > 0) {
                                return (
                                    <ImageAttachment
                                        images={images.map(img => ({
                                            url: `/api/attachments/${img.id}/download`,
                                            alt: img.original_filename
                                        }))}
                                    />
                                );
                            }
                            return null;
                        })()}

                        {/* File attachments */}
                        {message.attachments
                            .filter(att => att.attachment_type === 'file')
                            .map(attachment => (
                                <FileAttachment
                                    key={attachment.id}
                                    filename={attachment.original_filename}
                                    fileSize={attachment.file_size}
                                    mimeType={attachment.mime_type}
                                    downloadUrl={`/api/attachments/${attachment.id}/download`}
                                />
                            ))}
                    </div>
                )}

                {/* Message bubble - no background if only emojis */}
                {message.content && (onlyEmojis ? (
                    <div className="max-w-md">
                        {renderMessageContent(message.content)}
                    </div>
                ) : (
                    <div
                        className={cn(
                            "max-w-md rounded-2xl px-4 py-2.5",
                            isOwn
                                ? "bg-teal-600 text-white rounded-tr-sm"
                                : isAdmin
                                ? "bg-purple-100 text-purple-900 rounded-tl-sm"
                                : "bg-teal-50 text-gray-900 rounded-tl-sm"
                        )}
                    >
                        <p className="text-sm leading-relaxed" style={{ fontSize: '14px', lineHeight: '1.6' }}>
                            {renderMessageContent(message.content)}
                        </p>
                    </div>
                ))}

                {/* Timestamp for sent messages */}
                {isOwn && (
                    <span className="text-xs text-gray-400 mt-1 px-1">
                        You {formatMessageTime(message.created_at)}
                    </span>
                )}
            </div>

            {/* Avatar for sent messages */}
            {isOwn && (
                <Avatar className="h-10 w-10 flex-shrink-0">
                    <AvatarFallback className="bg-teal-500 text-white text-sm">
                        You
                    </AvatarFallback>
                </Avatar>
            )}
        </div>
    );
}
