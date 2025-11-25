import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { render, screen, waitFor, act } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import VoiceRecorder from '../voice-recorder';

// Mock MediaRecorder
class MockMediaRecorder {
    state: string = 'inactive';
    ondataavailable: ((event: any) => void) | null = null;
    onstop: (() => void) | null = null;
    chunks: Blob[] = [];

    constructor(stream: MediaStream, options?: any) {
        this.state = 'inactive';
    }

    start() {
        this.state = 'recording';
        // Immediately trigger data available
        if (this.ondataavailable) {
            this.ondataavailable({
                data: new Blob(['mock audio data'], { type: 'audio/webm' })
            });
        }
    }

    stop() {
        this.state = 'inactive';
        if (this.onstop) {
            this.onstop();
        }
    }
}

// Mock getUserMedia
const mockGetUserMedia = vi.fn();

describe('VoiceRecorder', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        
        // Setup MediaRecorder mock
        global.MediaRecorder = MockMediaRecorder as any;
        
        // Setup getUserMedia mock with immediate resolution
        mockGetUserMedia.mockResolvedValue({
            getTracks: () => [{ stop: vi.fn() }]
        });
        
        Object.defineProperty(global.navigator, 'mediaDevices', {
            value: {
                getUserMedia: mockGetUserMedia
            },
            configurable: true,
            writable: true
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        vi.useRealTimers();
    });

    /**
     * **Feature: message-attachments, Property 1: Recording timer increments**
     * **Validates: Requirements 1.2**
     * 
     * Property: For any recording session, the timer should increment by 1 second for each second that passes
     */
    it('should increment timer by 1 second for each second that passes', async () => {
        const onSend = vi.fn();
        const onCancel = vi.fn();

        // Test multiple time increments (property: for any duration)
        const testDurations = [1, 2, 3, 5, 10];

        for (const seconds of testDurations) {
            const { unmount } = render(<VoiceRecorder onSend={onSend} onCancel={onCancel} />);

            // Wait for recording to start
            await vi.waitFor(() => {
                expect(screen.getByText('Recording')).toBeInTheDocument();
            }, { timeout: 1000 });

            // Initial time should be 0:00
            expect(screen.getByText('0:00')).toBeInTheDocument();

            // Advance time
            act(() => {
                vi.advanceTimersByTime(seconds * 1000);
            });

            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            const expectedTime = `${mins}:${secs.toString().padStart(2, '0')}`;

            expect(screen.getByText(expectedTime)).toBeInTheDocument();

            unmount();
        }
    });

    /**
     * **Feature: message-attachments, Property 2: Recording produces audio file**
     * **Validates: Requirements 1.3**
     * 
     * Property: For any recording session that is stopped, an audio blob should be generated
     */
    it('should generate audio blob when recording is stopped', async () => {
        const onSend = vi.fn();
        const onCancel = vi.fn();

        render(<VoiceRecorder onSend={onSend} onCancel={onCancel} />);

        // Wait for recording to start
        await vi.waitFor(() => {
            expect(screen.getByText('Recording')).toBeInTheDocument();
        }, { timeout: 1000 });

        // Advance time by 5 seconds
        act(() => {
            vi.advanceTimersByTime(5000);
        });

        // Stop recording
        const stopButton = screen.getByTitle('Stop recording');
        
        await act(async () => {
            await userEvent.click(stopButton);
        });

        // Wait for audio blob to be generated
        await vi.waitFor(() => {
            expect(screen.getByTitle('Send voice message')).toBeInTheDocument();
        }, { timeout: 1000 });

        // Send the recording
        const sendButton = screen.getByTitle('Send voice message');
        
        await act(async () => {
            await userEvent.click(sendButton);
        });

        // Verify onSend was called with blob and duration
        expect(onSend).toHaveBeenCalled();
        const [blob, recordedDuration] = onSend.mock.calls[0];
        expect(blob).toBeInstanceOf(Blob);
        expect(recordedDuration).toBe(5);
    });

    /**
     * **Feature: message-attachments, Property 3: Cancel discards recording**
     * **Validates: Requirements 1.4**
     * 
     * Property: For any recording session, canceling should discard the recording and reset state
     */
    it('should discard recording and reset state when canceled', async () => {
        const onSend = vi.fn();
        const onCancel = vi.fn();

        render(<VoiceRecorder onSend={onSend} onCancel={onCancel} />);

        // Wait for recording to start
        await vi.waitFor(() => {
            expect(screen.getByText('Recording')).toBeInTheDocument();
        }, { timeout: 1000 });

        // Advance time by 3 seconds
        act(() => {
            vi.advanceTimersByTime(3000);
        });

        // Stop recording first
        const stopButton = screen.getByTitle('Stop recording');
        
        await act(async () => {
            await userEvent.click(stopButton);
        });

        await vi.waitFor(() => {
            expect(screen.getByTitle('Send voice message')).toBeInTheDocument();
        }, { timeout: 1000 });

        // Cancel after stopping
        const cancelButton = screen.getByTitle('Cancel');
        
        await act(async () => {
            await userEvent.click(cancelButton);
        });

        // Verify onCancel was called
        expect(onCancel).toHaveBeenCalled();
        
        // Verify onSend was NOT called
        expect(onSend).not.toHaveBeenCalled();
    });

    /**
     * Test that timer format is correct for various durations
     */
    it('should format timer correctly for various durations', async () => {
        const onSend = vi.fn();
        const onCancel = vi.fn();

        const testCases = [
            { seconds: 0, expected: '0:00' },
            { seconds: 5, expected: '0:05' },
            { seconds: 30, expected: '0:30' },
            { seconds: 60, expected: '1:00' },
            { seconds: 65, expected: '1:05' },
        ];

        for (const { seconds, expected } of testCases) {
            const { unmount } = render(<VoiceRecorder onSend={onSend} onCancel={onCancel} />);

            await vi.waitFor(() => {
                expect(screen.getByText('Recording')).toBeInTheDocument();
            }, { timeout: 1000 });

            act(() => {
                vi.advanceTimersByTime(seconds * 1000);
            });

            expect(screen.getByText(expected)).toBeInTheDocument();

            unmount();
        }
    });

    /**
     * Test that recording indicator is visible while recording
     */
    it('should show recording indicator while recording', async () => {
        const onSend = vi.fn();
        const onCancel = vi.fn();

        render(<VoiceRecorder onSend={onSend} onCancel={onCancel} />);

        await vi.waitFor(() => {
            expect(screen.getByText('Recording')).toBeInTheDocument();
        }, { timeout: 1000 });

        // Verify pulsing animation element exists
        const pulsingDot = document.querySelector('.animate-pulse');
        expect(pulsingDot).toBeInTheDocument();
    });
});
