import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import UploadProgress, { UploadItem } from '../upload-progress';

describe('UploadProgress', () => {
    /**
     * **Feature: message-attachments, Property 18: Progress indicator appears**
     * **Validates: Requirements 5.1**
     * 
     * Property: For any file being uploaded, a progress indicator should appear
     */
    it('should display progress indicator for all uploading files', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        // Test with different numbers of uploading files
        const testCases = [1, 2, 3, 5];

        testCases.forEach(fileCount => {
            const uploads: UploadItem[] = [];
            
            for (let i = 0; i < fileCount; i++) {
                uploads.push({
                    id: `upload-${i}`,
                    file: new File(['content'], `file${i}.pdf`, { type: 'application/pdf' }),
                    progress: 50,
                    status: 'uploading'
                });
            }

            const { unmount } = render(
                <UploadProgress
                    uploads={uploads}
                    onRetry={onRetry}
                    onCancel={onCancel}
                />
            );

            // Verify progress indicators are displayed
            const progressBars = document.querySelectorAll('[role="progressbar"]');
            expect(progressBars.length).toBe(fileCount);

            // Verify uploading status is shown
            const uploadingTexts = screen.getAllByText(/Uploading\.\.\. \d+%/);
            expect(uploadingTexts.length).toBe(fileCount);

            unmount();
        });
    });

    /**
     * **Feature: message-attachments, Property 19: Progress percentage updates**
     * **Validates: Requirements 5.2**
     * 
     * Property: For any file upload, the progress percentage should update as upload progresses
     */
    it('should display correct progress percentage for each file', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        // Test with various progress values
        const progressValues = [0, 25, 50, 75, 100];

        progressValues.forEach(progress => {
            const uploads: UploadItem[] = [{
                id: 'upload-1',
                file: new File(['content'], 'file.pdf', { type: 'application/pdf' }),
                progress: progress,
                status: progress === 100 ? 'complete' : 'uploading'
            }];

            const { unmount } = render(
                <UploadProgress
                    uploads={uploads}
                    onRetry={onRetry}
                    onCancel={onCancel}
                />
            );

            if (progress < 100) {
                // Verify progress percentage is displayed
                expect(screen.getByText(`Uploading... ${progress}%`)).toBeInTheDocument();
            } else {
                // Verify complete status is displayed
                expect(screen.getByText('Upload complete')).toBeInTheDocument();
            }

            unmount();
        });
    });

    /**
     * **Feature: message-attachments, Property 21: Error message on failure**
     * **Validates: Requirements 5.4**
     * 
     * Property: For any failed upload, an error message should be displayed
     */
    it('should display error message for failed uploads', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        const errorMessages = [
            'Network error',
            'File too large',
            'Invalid file type',
            'Upload timeout',
            'Server error'
        ];

        errorMessages.forEach(errorMessage => {
            const uploads: UploadItem[] = [{
                id: 'upload-1',
                file: new File(['content'], 'file.pdf', { type: 'application/pdf' }),
                progress: 0,
                status: 'error',
                error: errorMessage
            }];

            const { unmount } = render(
                <UploadProgress
                    uploads={uploads}
                    onRetry={onRetry}
                    onCancel={onCancel}
                />
            );

            // Verify error message is displayed
            expect(screen.getByText(errorMessage)).toBeInTheDocument();

            // Verify retry button is displayed
            expect(screen.getByText('Retry')).toBeInTheDocument();

            unmount();
        });
    });

    /**
     * **Feature: message-attachments, Property 22: Individual progress tracking**
     * **Validates: Requirements 5.5**
     * 
     * Property: For any set of concurrent uploads, each file should have its own progress tracking
     */
    it('should track progress individually for multiple concurrent uploads', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        // Create multiple uploads with different progress values
        const uploads: UploadItem[] = [
            {
                id: 'upload-1',
                file: new File(['content1'], 'file1.pdf', { type: 'application/pdf' }),
                progress: 25,
                status: 'uploading'
            },
            {
                id: 'upload-2',
                file: new File(['content2'], 'file2.pdf', { type: 'application/pdf' }),
                progress: 50,
                status: 'uploading'
            },
            {
                id: 'upload-3',
                file: new File(['content3'], 'file3.pdf', { type: 'application/pdf' }),
                progress: 75,
                status: 'uploading'
            },
            {
                id: 'upload-4',
                file: new File(['content4'], 'file4.pdf', { type: 'application/pdf' }),
                progress: 100,
                status: 'complete'
            },
            {
                id: 'upload-5',
                file: new File(['content5'], 'file5.pdf', { type: 'application/pdf' }),
                progress: 0,
                status: 'error',
                error: 'Upload failed'
            }
        ];

        render(
            <UploadProgress
                uploads={uploads}
                onRetry={onRetry}
                onCancel={onCancel}
            />
        );

        // Verify each file has its own progress display
        expect(screen.getByText('file1.pdf')).toBeInTheDocument();
        expect(screen.getByText('file2.pdf')).toBeInTheDocument();
        expect(screen.getByText('file3.pdf')).toBeInTheDocument();
        expect(screen.getByText('file4.pdf')).toBeInTheDocument();
        expect(screen.getByText('file5.pdf')).toBeInTheDocument();

        // Verify different progress percentages
        expect(screen.getByText('Uploading... 25%')).toBeInTheDocument();
        expect(screen.getByText('Uploading... 50%')).toBeInTheDocument();
        expect(screen.getByText('Uploading... 75%')).toBeInTheDocument();
        expect(screen.getByText('Upload complete')).toBeInTheDocument();
        expect(screen.getByText('Upload failed')).toBeInTheDocument();

        // Verify completion count
        expect(screen.getByText('1 / 5 complete')).toBeInTheDocument();
    });

    /**
     * Test that retry button calls onRetry with correct id
     */
    it('should call onRetry with correct upload id when retry button is clicked', async () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        const uploads: UploadItem[] = [
            {
                id: 'upload-1',
                file: new File(['content1'], 'file1.pdf', { type: 'application/pdf' }),
                progress: 0,
                status: 'error',
                error: 'Upload failed'
            },
            {
                id: 'upload-2',
                file: new File(['content2'], 'file2.pdf', { type: 'application/pdf' }),
                progress: 0,
                status: 'error',
                error: 'Network error'
            }
        ];

        render(
            <UploadProgress
                uploads={uploads}
                onRetry={onRetry}
                onCancel={onCancel}
            />
        );

        // Get all retry buttons
        const retryButtons = screen.getAllByText('Retry');
        expect(retryButtons.length).toBe(2);

        // Click first retry button
        await userEvent.click(retryButtons[0]);
        expect(onRetry).toHaveBeenCalledWith('upload-1');

        // Click second retry button
        await userEvent.click(retryButtons[1]);
        expect(onRetry).toHaveBeenCalledWith('upload-2');
    });

    /**
     * Test that component doesn't render when no uploads
     */
    it('should not render when uploads array is empty', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        const { container } = render(
            <UploadProgress
                uploads={[]}
                onRetry={onRetry}
                onCancel={onCancel}
            />
        );

        expect(container.firstChild).toBeNull();
    });

    /**
     * Test file size formatting
     */
    it('should format file sizes correctly', () => {
        const onRetry = vi.fn();
        const onCancel = vi.fn();

        const uploads: UploadItem[] = [
            {
                id: 'upload-1',
                file: new File(['a'.repeat(500)], 'small.txt', { type: 'text/plain' }),
                progress: 50,
                status: 'uploading'
            },
            {
                id: 'upload-2',
                file: new File(['b'.repeat(1024)], 'medium.txt', { type: 'text/plain' }),
                progress: 50,
                status: 'uploading'
            },
            {
                id: 'upload-3',
                file: new File(['c'.repeat(1024 * 1024)], 'large.txt', { type: 'text/plain' }),
                progress: 50,
                status: 'uploading'
            }
        ];

        render(
            <UploadProgress
                uploads={uploads}
                onRetry={onRetry}
                onCancel={onCancel}
            />
        );

        // Verify different size formats
        expect(screen.getByText('500 B')).toBeInTheDocument();
        expect(screen.getByText('1 KB')).toBeInTheDocument();
        expect(screen.getByText('1 MB')).toBeInTheDocument();
    });
});
