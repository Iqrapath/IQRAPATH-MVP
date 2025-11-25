import axios, { AxiosProgressEvent } from 'axios';

export interface UploadProgress {
    id: string;
    file: File;
    progress: number;
    status: 'uploading' | 'complete' | 'error';
    error?: string;
}

export interface UploadOptions {
    onProgress?: (progress: UploadProgress) => void;
    onComplete?: (uploadId: string, response: any) => void;
    onError?: (uploadId: string, error: string) => void;
}

/**
 * Upload a file with progress tracking
 */
export async function uploadFile(
    messageId: number,
    file: File,
    attachmentType: 'voice' | 'image' | 'file',
    metadata: Record<string, any> = {},
    options: UploadOptions = {}
): Promise<any> {
    const uploadId = `${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
    
    const formData = new FormData();
    formData.append('file', file);
    formData.append('attachment_type', attachmentType);
    
    // Add metadata
    Object.keys(metadata).forEach(key => {
        formData.append(key, metadata[key]);
    });

    try {
        const response = await axios.post(
            `/api/messages/${messageId}/attachments`,
            formData,
            {
                headers: {
                    'Content-Type': 'multipart/form-data',
                },
                onUploadProgress: (progressEvent: AxiosProgressEvent) => {
                    if (progressEvent.total) {
                        const progress = Math.round(
                            (progressEvent.loaded * 100) / progressEvent.total
                        );
                        
                        options.onProgress?.({
                            id: uploadId,
                            file,
                            progress,
                            status: 'uploading'
                        });
                    }
                }
            }
        );

        options.onProgress?.({
            id: uploadId,
            file,
            progress: 100,
            status: 'complete'
        });

        options.onComplete?.(uploadId, response.data);

        return response.data;
    } catch (error: any) {
        const errorMessage = error.response?.data?.message || 'Upload failed';
        
        options.onProgress?.({
            id: uploadId,
            file,
            progress: 0,
            status: 'error',
            error: errorMessage
        });

        options.onError?.(uploadId, errorMessage);

        throw error;
    }
}

/**
 * Upload multiple files concurrently with progress tracking
 */
export async function uploadMultipleFiles(
    messageId: number,
    files: Array<{
        file: File;
        type: 'voice' | 'image' | 'file';
        metadata?: Record<string, any>;
    }>,
    options: UploadOptions = {}
): Promise<any[]> {
    const uploadPromises = files.map(({ file, type, metadata = {} }) =>
        uploadFile(messageId, file, type, metadata, options)
    );

    try {
        const results = await Promise.all(uploadPromises);
        return results;
    } catch (error) {
        // Even if some uploads fail, return what succeeded
        const results = await Promise.allSettled(uploadPromises);
        return results
            .filter((result): result is PromiseFulfilledResult<any> => result.status === 'fulfilled')
            .map(result => result.value);
    }
}

/**
 * Retry a failed upload
 */
export async function retryUpload(
    messageId: number,
    file: File,
    attachmentType: 'voice' | 'image' | 'file',
    metadata: Record<string, any> = {},
    options: UploadOptions = {}
): Promise<any> {
    return uploadFile(messageId, file, attachmentType, metadata, options);
}

/**
 * File size limits by type (in bytes)
 */
export const FILE_SIZE_LIMITS = {
    voice: 5 * 1024 * 1024,  // 5MB for voice messages
    image: 10 * 1024 * 1024, // 10MB for images
    file: 10 * 1024 * 1024   // 10MB for general files
};

/**
 * Allowed MIME types by attachment type
 */
export const ALLOWED_MIME_TYPES = {
    voice: [
        'audio/webm',
        'audio/mp3',
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',
        'audio/mp4',
        'audio/x-m4a'
    ],
    image: [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml'
    ],
    file: [
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv',
        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',
        // Images (also allowed as files)
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'image/webp'
    ]
};

/**
 * Dangerous file types that should never be allowed
 */
const DANGEROUS_MIME_TYPES = [
    'application/x-executable',
    'application/x-msdownload',
    'application/x-msdos-program',
    'application/x-sh',
    'application/x-bat',
    'application/x-dosexec',
    'text/x-shellscript'
];

/**
 * Dangerous file extensions that should never be allowed
 */
const DANGEROUS_EXTENSIONS = [
    '.exe', '.bat', '.cmd', '.com', '.pif', '.scr',
    '.vbs', '.js', '.jar', '.app', '.deb', '.rpm'
];

/**
 * Format file size in human-readable format
 */
export function formatFileSize(bytes: number): string {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

/**
 * Get file extension from filename
 */
function getFileExtension(filename: string): string {
    const parts = filename.split('.');
    return parts.length > 1 ? '.' + parts[parts.length - 1].toLowerCase() : '';
}

/**
 * Validate file before upload
 * 
 * **Property: File validation before upload**
 * **Validates: Requirements 4.1, 4.2**
 */
export function validateFile(
    file: File,
    type: 'voice' | 'image' | 'file'
): { valid: boolean; error?: string } {
    // Check if file exists
    if (!file) {
        return {
            valid: false,
            error: 'No file provided'
        };
    }

    // Check file size
    const maxSize = FILE_SIZE_LIMITS[type];
    if (file.size > maxSize) {
        return {
            valid: false,
            error: `File size exceeds maximum allowed size of ${formatFileSize(maxSize)}`
        };
    }

    // Check for zero-byte files
    if (file.size === 0) {
        return {
            valid: false,
            error: 'File is empty (0 bytes)'
        };
    }

    // Check file extension for dangerous types
    const extension = getFileExtension(file.name);
    if (DANGEROUS_EXTENSIONS.includes(extension)) {
        return {
            valid: false,
            error: `File type ${extension} is not allowed for security reasons`
        };
    }

    // Check MIME type for dangerous types
    if (DANGEROUS_MIME_TYPES.includes(file.type)) {
        return {
            valid: false,
            error: 'File type not allowed for security reasons'
        };
    }

    // Check if MIME type is allowed for this attachment type
    const allowedTypes = ALLOWED_MIME_TYPES[type];
    if (allowedTypes.length > 0 && !allowedTypes.includes(file.type)) {
        // For files without MIME type, check extension
        if (!file.type && type === 'file') {
            // Allow files without MIME type if they have safe extensions
            const safeExtensions = ['.txt', '.pdf', '.doc', '.docx', '.xls', '.xlsx', '.ppt', '.pptx'];
            if (!safeExtensions.includes(extension)) {
                return {
                    valid: false,
                    error: `File type not recognized. Supported formats: ${safeExtensions.join(', ')}`
                };
            }
        } else {
            const typeNames = {
                voice: 'audio',
                image: 'image',
                file: 'document'
            };
            return {
                valid: false,
                error: `Invalid ${typeNames[type]} format. Please select a valid ${typeNames[type]} file.`
            };
        }
    }

    return { valid: true };
}

/**
 * Validate multiple files before upload
 * 
 * **Property: Batch file validation**
 * **Validates: Requirements 4.1, 4.2**
 */
export function validateFiles(
    files: File[],
    type: 'voice' | 'image' | 'file'
): { valid: boolean; errors: string[]; validFiles: File[] } {
    const errors: string[] = [];
    const validFiles: File[] = [];

    files.forEach((file, index) => {
        const validation = validateFile(file, type);
        if (validation.valid) {
            validFiles.push(file);
        } else {
            errors.push(`${file.name}: ${validation.error}`);
        }
    });

    return {
        valid: errors.length === 0,
        errors,
        validFiles
    };
}
