import { useState, useRef, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Play, Pause } from 'lucide-react';
import { cn } from '@/lib/utils';

interface AudioPlayerProps {
    src: string;
    duration?: number;
    className?: string;
}

export default function AudioPlayer({ src, duration, className }: AudioPlayerProps) {
    const [isPlaying, setIsPlaying] = useState(false);
    const [currentTime, setCurrentTime] = useState(0);
    const [audioDuration, setAudioDuration] = useState(duration || 0);
    const [waveformData, setWaveformData] = useState<number[]>([]);
    const audioRef = useRef<HTMLAudioElement>(null);

    // Generate waveform data on mount
    useEffect(() => {
        const data = Array.from({ length: 50 }, (_, i) => {
            const base = Math.sin(i * 0.3) * 0.3 + 0.5;
            const variation = Math.random() * 0.3;
            return Math.max(0.3, Math.min(1, base + variation));
        });
        setWaveformData(data);
    }, []);

    useEffect(() => {
        const audio = audioRef.current;
        if (!audio) return;

        const handleTimeUpdate = () => {
            setCurrentTime(audio.currentTime);
        };

        const handleLoadedMetadata = () => {
            setAudioDuration(audio.duration);
        };

        const handleEnded = () => {
            setIsPlaying(false);
            setCurrentTime(0);
        };

        const handleError = (e: Event) => {
            console.error('Audio loading error:', e);
        };

        audio.addEventListener('timeupdate', handleTimeUpdate);
        audio.addEventListener('loadedmetadata', handleLoadedMetadata);
        audio.addEventListener('ended', handleEnded);
        audio.addEventListener('error', handleError);

        return () => {
            audio.removeEventListener('timeupdate', handleTimeUpdate);
            audio.removeEventListener('loadedmetadata', handleLoadedMetadata);
            audio.removeEventListener('ended', handleEnded);
            audio.removeEventListener('error', handleError);
        };
    }, []);

    const togglePlayPause = () => {
        const audio = audioRef.current;
        if (!audio) return;

        if (isPlaying) {
            audio.pause();
        } else {
            audio.play();
        }
        setIsPlaying(!isPlaying);
    };

    const formatTime = (seconds: number): string => {
        if (isNaN(seconds)) return '0:00';
        const mins = Math.floor(seconds / 60);
        const secs = Math.floor(seconds % 60);
        return `${mins}:${secs.toString().padStart(2, '0')}`;
    };

    const handleWaveformClick = (e: React.MouseEvent<HTMLDivElement>) => {
        const audio = audioRef.current;
        if (!audio || !audioDuration) return;

        const rect = e.currentTarget.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const percentage = x / rect.width;
        const time = percentage * audioDuration;
        
        audio.currentTime = time;
        setCurrentTime(time);
    };

    const progress = audioDuration > 0 ? currentTime / audioDuration : 0;

    return (
        <div className={cn('inline-flex items-center gap-2 bg-white border border-gray-200 rounded-full px-3 py-2 shadow-sm max-w-sm', className)}>
            <audio ref={audioRef} src={src} preload="metadata" />
            
            {/* Play/Pause button */}
            <button
                type="button"
                onClick={togglePlayPause}
                className="flex-shrink-0 w-8 h-8 rounded-full bg-teal-600 hover:bg-teal-700 transition-colors flex items-center justify-center text-white focus:outline-none focus:ring-2 focus:ring-teal-500 focus:ring-offset-2"
            >
                {isPlaying ? (
                    <Pause className="h-4 w-4" fill="currentColor" />
                ) : (
                    <Play className="h-4 w-4 ml-0.5" fill="currentColor" />
                )}
            </button>

            {/* Waveform and time */}
            <div className="flex-1 min-w-0 flex items-center gap-2">
                {/* Waveform visualization */}
                {waveformData.length > 0 ? (
                    <div 
                        className="flex-1 flex items-center gap-[1px] cursor-pointer"
                        onClick={handleWaveformClick}
                        style={{ height: '32px' }}
                    >
                        {waveformData.map((amplitude, index) => {
                            const isPassed = index / waveformData.length < progress;
                            const heightPx = Math.max(amplitude * 28, 4); // Min 4px, max 28px
                            
                            return (
                                <div
                                    key={index}
                                    className="flex-1 flex items-center justify-center"
                                    style={{ minWidth: '2px', maxWidth: '3px' }}
                                >
                                    <div
                                        className="w-full rounded-full transition-all duration-150"
                                        style={{
                                            height: `${heightPx}px`,
                                            backgroundColor: isPassed ? '#0d9488' : '#e5e7eb'
                                        }}
                                    />
                                </div>
                            );
                        })}
                    </div>
                ) : (
                    <div className="flex-1 h-8 flex items-center justify-center">
                        <div className="w-4 h-4 border-2 border-teal-600 border-t-transparent rounded-full animate-spin" />
                    </div>
                )}
                
                {/* Time display */}
                <span className="text-xs font-medium text-gray-600 tabular-nums flex-shrink-0">
                    {formatTime(currentTime)} / {formatTime(audioDuration)}
                </span>
            </div>
        </div>
    );
}
