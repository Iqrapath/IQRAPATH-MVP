import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AttachmentPreview, { AttachmentFile } from '../attachment-preview';

describe('AttachmentPreview', () => {
    /**
     * **Feature: message-attachments, Property 5: Image previews generated**
     * **Validates: Requirements 2.2**
     * 
     * Property: For any set of image files, preview thumbnails should be generated and displayed
     */
    it('should generate and display preview thumbnails for all images', () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        // Test with different numbers of images (property: for any set)
        const testCases = [1, 2, 3, 5, 10];

        testCases.forEach(imageCount => {
            const files: AttachmentFile[] = [];
            
            for (let i = 0; i < imageCount; i++) {
                files.push({
                    file: new File(['image content'], `image${i}.jpg`, { type: 'image/jpeg' }),
                    preview: `data:image/jpeg;base64,mockpreview${i}`,
                    type: 'image'
                });
            }

            const { unmount } = render(
                <AttachmentPreview
                    files={files}
                    onRemove={onRemove}
                    onConfirm={onConfirm}
                    onCancel={onCancel}
                />
            );

            // Verify all images have preview thumbnails
            const images = screen.getAllByRole('img');
            expect(images).toHaveLength(imageCount);

            // Verify each image has correct src
            images.forEach((img, index) => {
                expect(img).toHaveAttribute('src', `data:image/jpeg;base64,mockpreview${index}`);
                expect(img).toHaveAttribute('alt', `image${index}.jpg`);
            });

            unmount();
        });
    });

    /**
     * **Feature: message-attachments, Property 9: File metadata displayed**
     * **Validates: Requirements 3.2**
     * 
     * Property: For any file, the name, size, and type should be displayed in the preview
     */
    it('should display file metadata for all files', () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        // Test with various file types and sizes
        const testFiles: AttachmentFile[] = [
            {
                file: new File(['a'.repeat(1024)], 'document.pdf', { type: 'application/pdf' }),
                type: 'file'
            },
            {
                file: new File(['b'.repeat(2048)], 'spreadsheet.xlsx', { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' }),
                type: 'file'
            },
            {
                file: new File(['c'.repeat(512)], 'image.jpg', { type: 'image/jpeg' }),
                preview: 'data:image/jpeg;base64,mockpreview',
                type: 'image'
            },
            {
                file: new File(['d'.repeat(10240)], 'large-file.zip', { type: 'application/zip' }),
                type: 'file'
            }
        ];

        render(
            <AttachmentPreview
                files={testFiles}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        // Verify file names are displayed
        expect(screen.getByText('document.pdf')).toBeInTheDocument();
        expect(screen.getByText('spreadsheet.xlsx')).toBeInTheDocument();
        expect(screen.getByText('image.jpg')).toBeInTheDocument();
        expect(screen.getByText('large-file.zip')).toBeInTheDocument();

        // Verify file sizes are displayed (approximate due to formatting)
        expect(screen.getByText(/1 KB/)).toBeInTheDocument();
        expect(screen.getByText(/2 KB/)).toBeInTheDocument();
        expect(screen.getByText(/10 KB/)).toBeInTheDocument();

        // Verify file type indicators are displayed
        expect(screen.getByText('PDF')).toBeInTheDocument();
        expect(screen.getByText('XLSX')).toBeInTheDocument();
        // Image files show as images, not file type text
        expect(screen.getByText('ZIP')).toBeInTheDocument();
    });

    /**
     * Test that remove button works for each file
     */
    it('should call onRemove with correct index when remove button is clicked', async () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        const files: AttachmentFile[] = [
            {
                file: new File(['content1'], 'file1.pdf', { type: 'application/pdf' }),
                type: 'file'
            },
            {
                file: new File(['content2'], 'file2.pdf', { type: 'application/pdf' }),
                type: 'file'
            },
            {
                file: new File(['content3'], 'file3.pdf', { type: 'application/pdf' }),
                type: 'file'
            }
        ];

        render(
            <AttachmentPreview
                files={files}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        // Get all remove buttons
        const removeButtons = screen.getAllByRole('button').filter(
            button => button.querySelector('svg')?.classList.contains('lucide-x')
        );

        expect(removeButtons).toHaveLength(3);

        // Click each remove button
        for (let i = 0; i < removeButtons.length; i++) {
            await userEvent.click(removeButtons[i]);
            expect(onRemove).toHaveBeenCalledWith(i);
        }
    });

    /**
     * Test that confirm button shows correct file count
     */
    it('should display correct file count on confirm button', () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        // Test with 1 file
        const { rerender } = render(
            <AttachmentPreview
                files={[{
                    file: new File(['content'], 'file.pdf', { type: 'application/pdf' }),
                    type: 'file'
                }]}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        expect(screen.getByText('Send 1 file')).toBeInTheDocument();

        // Test with multiple files
        rerender(
            <AttachmentPreview
                files={[
                    {
                        file: new File(['content1'], 'file1.pdf', { type: 'application/pdf' }),
                        type: 'file'
                    },
                    {
                        file: new File(['content2'], 'file2.pdf', { type: 'application/pdf' }),
                        type: 'file'
                    },
                    {
                        file: new File(['content3'], 'file3.pdf', { type: 'application/pdf' }),
                        type: 'file'
                    }
                ]}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        expect(screen.getByText('Send 3 files')).toBeInTheDocument();
    });

    /**
     * Test that confirm and cancel buttons work
     */
    it('should call onConfirm and onCancel when buttons are clicked', async () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        const files: AttachmentFile[] = [{
            file: new File(['content'], 'file.pdf', { type: 'application/pdf' }),
            type: 'file'
        }];

        render(
            <AttachmentPreview
                files={files}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        // Click confirm button
        const confirmButton = screen.getByText(/Send \d+ file/);
        await userEvent.click(confirmButton);
        expect(onConfirm).toHaveBeenCalledTimes(1);

        // Click cancel button
        const cancelButton = screen.getByText('Cancel');
        await userEvent.click(cancelButton);
        expect(onCancel).toHaveBeenCalledTimes(1);
    });

    /**
     * Test that confirm button is disabled when no files
     */
    it('should disable confirm button when no files are present', () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        render(
            <AttachmentPreview
                files={[]}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        const confirmButton = screen.getByText('Send 0 files');
        expect(confirmButton).toBeDisabled();
    });

    /**
     * Test file size formatting
     */
    it('should format file sizes correctly', () => {
        const onRemove = vi.fn();
        const onConfirm = vi.fn();
        const onCancel = vi.fn();

        const files: AttachmentFile[] = [
            {
                file: new File(['a'.repeat(500)], 'small.txt', { type: 'text/plain' }),
                type: 'file'
            },
            {
                file: new File(['b'.repeat(1024)], 'medium.txt', { type: 'text/plain' }),
                type: 'file'
            },
            {
                file: new File(['c'.repeat(1024 * 1024)], 'large.txt', { type: 'text/plain' }),
                type: 'file'
            }
        ];

        render(
            <AttachmentPreview
                files={files}
                onRemove={onRemove}
                onConfirm={onConfirm}
                onCancel={onCancel}
            />
        );

        // Verify different size formats are displayed
        expect(screen.getByText('500 B')).toBeInTheDocument(); // Bytes
        expect(screen.getByText('1 KB')).toBeInTheDocument(); // Kilobytes
        expect(screen.getByText('1 MB')).toBeInTheDocument(); // Megabytes
    });
});
