/**
 * Image compression utilities for client-side optimization
 */

export interface CompressionOptions {
    maxWidth?: number;
    maxHeight?: number;
    quality?: number;
    mimeType?: string;
}

const DEFAULT_OPTIONS: CompressionOptions = {
    maxWidth: 1920,
    maxHeight: 1920,
    quality: 0.8,
    mimeType: 'image/jpeg'
};

/**
 * Compress an image file before upload
 * 
 * **Property: Image compression reduces file size**
 * **Validates: Requirements 2.3**
 */
export async function compressImage(
    file: File,
    options: CompressionOptions = {}
): Promise<File> {
    const opts = { ...DEFAULT_OPTIONS, ...options };

    // Skip compression for SVG files
    if (file.type === 'image/svg+xml') {
        return file;
    }

    // Skip compression for GIFs (to preserve animation)
    if (file.type === 'image/gif') {
        return file;
    }

    return new Promise((resolve, reject) => {
        const reader = new FileReader();

        reader.onload = (e) => {
            const img = new Image();

            img.onload = () => {
                try {
                    // Calculate new dimensions while maintaining aspect ratio
                    let { width, height } = img;
                    const maxWidth = opts.maxWidth!;
                    const maxHeight = opts.maxHeight!;

                    if (width > maxWidth || height > maxHeight) {
                        const aspectRatio = width / height;

                        if (width > height) {
                            width = maxWidth;
                            height = width / aspectRatio;
                        } else {
                            height = maxHeight;
                            width = height * aspectRatio;
                        }
                    }

                    // Create canvas and draw resized image
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    if (!ctx) {
                        reject(new Error('Failed to get canvas context'));
                        return;
                    }

                    ctx.drawImage(img, 0, 0, width, height);

                    // Convert canvas to blob
                    canvas.toBlob(
                        (blob) => {
                            if (!blob) {
                                reject(new Error('Failed to compress image'));
                                return;
                            }

                            // Create new file from blob
                            const compressedFile = new File(
                                [blob],
                                file.name,
                                {
                                    type: opts.mimeType!,
                                    lastModified: Date.now()
                                }
                            );

                            // Only use compressed version if it's smaller
                            if (compressedFile.size < file.size) {
                                resolve(compressedFile);
                            } else {
                                resolve(file);
                            }
                        },
                        opts.mimeType,
                        opts.quality
                    );
                } catch (error) {
                    reject(error);
                }
            };

            img.onerror = () => {
                reject(new Error('Failed to load image'));
            };

            img.src = e.target?.result as string;
        };

        reader.onerror = () => {
            reject(new Error('Failed to read file'));
        };

        reader.readAsDataURL(file);
    });
}

/**
 * Compress multiple images
 */
export async function compressImages(
    files: File[],
    options: CompressionOptions = {}
): Promise<File[]> {
    const compressionPromises = files.map(file => compressImage(file, options));
    return Promise.all(compressionPromises);
}

/**
 * Get compression statistics
 */
export function getCompressionStats(originalFile: File, compressedFile: File) {
    const originalSize = originalFile.size;
    const compressedSize = compressedFile.size;
    const savedBytes = originalSize - compressedSize;
    const savedPercentage = ((savedBytes / originalSize) * 100).toFixed(1);

    return {
        originalSize,
        compressedSize,
        savedBytes,
        savedPercentage: parseFloat(savedPercentage)
    };
}