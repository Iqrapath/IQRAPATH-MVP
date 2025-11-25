import { Send, Paperclip, Smile, Mic, Image as ImageIcon, PencilLineIcon } from 'lucide-react';
import { useState, useRef, useEffect } from 'react';
import EmojiPicker, { EmojiClickData } from 'emoji-picker-react';
import { RecordingControls } from './recording-controls';

interface MessageInputProps {
    messageText: string;
    onMessageChange: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onSendMessage: () => void;
    onVoiceMessage: (audioFile: File, duration: number) => void;
    onImageSelect: (e: React.ChangeEvent<HTMLInputElement>) => void;
    onFileSelect: (e: React.ChangeEvent<HTMLInputElement>) => void;
    isLoading: boolean;
}

export function MessageInput({
    messageText,
    onMessageChange,
    onSendMessage,
    onVoiceMessage,
    onImageSelect,
    onFileSelect,
    isLoading,
}: MessageInputProps) {
    const [showEmojiPicker, setShowEmojiPicker] = useState(false);
    const [isRecording, setIsRecording] = useState(false);
    const [recordingTime, setRecordingTime] = useState(0);
    
    const emojiPickerRef = useRef<HTMLDivElement>(null);
    const imageInputRef = useRef<HTMLInputElement>(null);
    const fileInputRef = useRef<HTMLInputElement>(null);
    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const audioChunksRef = useRef<Blob[]>([]);
    const recordingIntervalRef = useRef<NodeJS.Timeout | null>(null);

    // Close emoji picker when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (emojiPickerRef.current && !emojiPickerRef.current.contains(event.target as Node)) {
                setShowEmojiPicker(false);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    const handleEmojiClick = (emojiData: EmojiClickData) => {
        const syntheticEvent = {
            target: { value: messageText + emojiData.emoji }
        } as React.ChangeEvent<HTMLInputElement>;
        onMessageChange(syntheticEvent);
    };

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            const mediaRecorder = new MediaRecorder(stream);
            mediaRecorderRef.current = mediaRecorder;
            audioChunksRef.current = [];

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    audioChunksRef.current.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                const audioBlob = new Blob(audioChunksRef.current, { type: 'audio/webm' });
                const audioFile = new File([audioBlob], `voice-message-${Date.now()}.webm`, {
                    type: 'audio/webm'
                });
                
                // Send voice message with duration
                onVoiceMessage(audioFile, recordingTime);
                
                // Stop all tracks
                stream.getTracks().forEach(track => track.stop());
                
                // Reset recording state
                setRecordingTime(0);
                if (recordingIntervalRef.current) {
                    clearInterval(recordingIntervalRef.current);
                }
            };

            mediaRecorder.start();
            setIsRecording(true);

            // Start timer
            recordingIntervalRef.current = setInterval(() => {
                setRecordingTime(prev => prev + 1);
            }, 1000);

        } catch (error) {
            console.error('Error accessing microphone:', error);
            alert('Could not access microphone. Please check permissions.');
        }
    };

    const stopRecording = () => {
        if (mediaRecorderRef.current && isRecording) {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
        }
    };

    const cancelRecording = () => {
        if (mediaRecorderRef.current && isRecording) {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
            audioChunksRef.current = [];
            setRecordingTime(0);
            if (recordingIntervalRef.current) {
                clearInterval(recordingIntervalRef.current);
            }
        }
    };

    return (
        <div className="px-6 py-4 border-t border-gray-100">
            <div className="flex items-center gap-4">
                {/* Input container with pencil icon inside */}
                <div className="flex-1 flex items-center gap-3 bg-white border border-gray-200 rounded-full px-5 py-3">
                    <PencilLineIcon className="h-5 w-5 text-gray-400 flex-shrink-0" />
                    <input
                        type="text"
                        placeholder="Type your message ..."
                        value={messageText}
                        onChange={onMessageChange}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter' && !e.shiftKey) {
                                e.preventDefault();
                                onSendMessage();
                            }
                        }}
                        className="flex-1 bg-transparent border-0 outline-none text-sm placeholder:text-gray-300"
                    />
                </div>

                {/* Action icons outside the input */}
                {/* Voice Recording */}
                {isRecording ? (
                    <RecordingControls
                        recordingTime={recordingTime}
                        onCancel={cancelRecording}
                        onSend={stopRecording}
                    />
                ) : (
                    <button 
                        onClick={startRecording}
                        className="text-gray-600 hover:text-gray-800 transition-colors flex-shrink-0"
                    >
                        <Mic className="h-6 w-6" />
                    </button>
                )}

                {/* Emoji Picker */}
                <div className="relative" ref={emojiPickerRef}>
                    <button 
                        onClick={() => setShowEmojiPicker(!showEmojiPicker)}
                        className="text-gray-600 hover:text-gray-800 transition-colors flex-shrink-0"
                    >
                        <Smile className="h-6 w-6" />
                    </button>
                    {showEmojiPicker && (
                        <div className="absolute bottom-12 right-0 z-50">
                            <EmojiPicker onEmojiClick={handleEmojiClick} />
                        </div>
                    )}
                </div>

                {/* Image Picker */}
                <button 
                    onClick={() => imageInputRef.current?.click()}
                    className="text-gray-600 hover:text-gray-800 transition-colors flex-shrink-0"
                >
                    <ImageIcon className="h-6 w-6" />
                </button>
                <input
                    ref={imageInputRef}
                    type="file"
                    accept="image/*"
                    multiple
                    onChange={onImageSelect}
                    className="hidden"
                />

                {/* File Picker */}
                <button 
                    onClick={() => fileInputRef.current?.click()}
                    className="text-gray-600 hover:text-gray-800 transition-colors flex-shrink-0"
                >
                    <Paperclip className="h-6 w-6" />
                </button>
                <input
                    ref={fileInputRef}
                    type="file"
                    multiple
                    onChange={onFileSelect}
                    className="hidden"
                />

                {/* Send Button */}
                <button 
                    onClick={onSendMessage} 
                    disabled={!messageText.trim() || isLoading}
                    className="text-blue-500 hover:text-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors flex-shrink-0"
                >
                    <Send className="h-6 w-6 fill-current" />
                </button>
            </div>
        </div>
    );
}
