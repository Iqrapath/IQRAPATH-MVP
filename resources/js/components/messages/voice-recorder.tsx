import { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Square, Send, X } from 'lucide-react';
import { cn } from '@/lib/utils';
import { validateFile, FILE_SIZE_LIMITS, formatFileSize } from '@/lib/upload-utils';
import { showError } from '@/lib/toast-utils';

interface VoiceRecorderProps {
    onSend: (audioBlob: Blob, duration: number) => void;
    onCancel: () => void;
    className?: string;
}

export default function VoiceRecorder({ onSend, onCancel, className }: VoiceRecorderProps) {
    const [isRecording, setIsRecording] = useState(false);
    const [duration, setDuration] = useState(0);
    const [audioBlob, setAudioBlob] = useState<Blob | null>(null);
    
    const mediaRecorderRef = useRef<MediaRecorder | null>(null);
    const chunksRef = useRef<Blob[]>([]);
    const timerRef = useRef<NodeJS.Timeout | null>(null);
    const startTimeRef = useRef<number>(0);

    useEffect(() => {
        // Cleanup on unmount
        return () => {
            if (timerRef.current) {
                clearInterval(timerRef.current);
            }
            if (mediaRecorderRef.current && mediaRecorderRef.current.state === 'recording') {
                mediaRecorderRef.current.stop();
            }
        };
    }, []);

    const startRecording = async () => {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            
            const mediaRecorder = new MediaRecorder(stream, {
                mimeType: 'audio/webm;codecs=opus'
            });
            
            mediaRecorderRef.current = mediaRecorder;
            chunksRef.current = [];

            mediaRecorder.ondataavailable = (event) => {
                if (event.data.size > 0) {
                    chunksRef.current.push(event.data);
                }
            };

            mediaRecorder.onstop = () => {
                const blob = new Blob(chunksRef.current, { type: 'audio/webm' });
                setAudioBlob(blob);
                
                // Stop all tracks
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start();
            setIsRecording(true);
            startTimeRef.current = Date.now();
            
            // Start timer
            timerRef.current = setInterval(() => {
                const elapsed = Math.floor((Date.now() - startTimeRef.current) / 1000);
                setDuration(elapsed);
            }, 1000);

        } catch (error) {
            console.error('Error accessing microphone:', error);
            alert('Unable to access microphone. Please check your permissions.');
        }
    };

    const stopRecording = () => {
        if (mediaRecorderRef.current && mediaRecorderRef.current.state === 'recording') {
            mediaRecorderRef.current.stop();
            setIsRecording(false);
            
            if (timerRef.current) {
                clearInterval(timerRef.current);
                timerRef.current = null;
            }
        }
    };

    const handleSend = () => {
        if (audioBlob) {
            // Validate audio file before sending
            const audioFile = new File([audioBlob], `voice-${Date.now()}.webm`, {
                type: 'audio/webm'
            });

            const validation = validateFile(audioFile, 'voice');
            
            if (!validation.valid) {
                showError(validation.error || 'Invalid voice message');
                return;
            }

            // Check duration (minimum 1 second)
            if (duration < 1) {
                showError('Voice message is too short (minimum 1 second)');
                return;
            }

            // Check maximum duration (5 minutes)
            if (duration > 300) {
                showError('Voice message is too long (maximum 5 minutes)');
                return;
            }

            onSend(audioBlob, duration);
            handleCancel();
        }
    };

    const handleCancel = () => {
        if (isRecording) {
            stopRecording();
        }
        
        setAudioBlob(null);
        setDuration(0);
        chunksRef.current = [];
        
        if (timerRef.current) {
            clearInterval(timerRef.current);
            timerRef.current = null;
        }
        
        onCancel();
    };

    const formatDuration = (seconds: number): string => {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    // Auto-start recording when component mounts
    useEffect(() => {
        startRecording();
    }, []);

    return (
        <div className={cn('flex items-center gap-3 p-4 bg-muted rounded-lg', className)}>
            {/* Recording indicator */}
            {isRecording && (
                <div className="flex items-center gap-2">
                    <div className="relative">
                        <div className="w-3 h-3 bg-red-500 rounded-full animate-pulse" />
                        <div className="absolute inset-0 w-3 h-3 bg-red-500 rounded-full animate-ping" />
                    </div>
                    <span className="text-sm font-medium">Recording</span>
                </div>
            )}

            {/* Duration display */}
            <div className="flex-1 text-center">
                <span className="text-2xl font-mono font-semibold">
                    {formatDuration(duration)}
                </span>
            </div>

            {/* Control buttons */}
            <div className="flex items-center gap-2">
                {isRecording ? (
                    <Button
                        type="button"
                        variant="destructive"
                        size="icon"
                        onClick={stopRecording}
                        title="Stop recording"
                    >
                        <Square className="h-4 w-4" />
                    </Button>
                ) : audioBlob ? (
                    <>
                        <Button
                            type="button"
                            variant="default"
                            size="icon"
                            onClick={handleSend}
                            title="Send voice message"
                        >
                            <Send className="h-4 w-4" />
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            size="icon"
                            onClick={handleCancel}
                            title="Cancel"
                        >
                            <X className="h-4 w-4" />
                        </Button>
                    </>
                ) : null}
            </div>
        </div>
    );
}
