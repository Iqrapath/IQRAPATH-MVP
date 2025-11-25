import { X, File, Image as ImageIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

export interface AttachmentFile {
    file: File;
    preview?: string;
    type: 'image' | 'file';
}

interface AttachmentPreviewProps {
    files: AttachmentFile[];
    onRemove: (index: number) => void;
    onConfirm: () => void;
    onCancel: () => void;
    className?: string;
}

export default function AttachmentPreview({
    files,
    onRemove,
    onConfirm,
    onCancel,
    className
}: AttachmentPreviewProps) {
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const getFileIcon = (file: File) => {
        if (file.type.startsWith('image/')) {
            return <ImageIcon className="h-8 w-8 text-muted-foreground" />;
        }
        return <File className="h-8 w-8 text-muted-foreground" />;
    };

    return (
        <div className={cn('bg-muted rounded-lg p-4 space-y-4', className)}>
            {/* Preview grid */}
            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                {files.map((fileData, index) => (
                    <div
                        key={index}
                        className="relative group bg-background rounded-lg overflow-hidden border border-border"
                    >
                        {/* Remove button */}
                        <Button
                            type="button"
                            variant="destructive"
                            size="icon"
                            className="absolute top-1 right-1 h-6 w-6 opacity-0 group-hover:opacity-100 transition-opacity z-10"
                            onClick={() => onRemove(index)}
                        >
                            <X className="h-3 w-3" />
                        </Button>

                        {/* Preview content */}
                        <div className="aspect-square flex flex-col items-center justify-center p-2">
                            {fileData.type === 'image' && fileData.preview ? (
                                <img
                                    src={fileData.preview}
                                    alt={fileData.file.name}
                                    className="w-full h-full object-cover rounded"
                                />
                            ) : (
                                <div className="flex flex-col items-center justify-center gap-2">
                                    {getFileIcon(fileData.file)}
                                    <span className="text-xs text-center text-muted-foreground truncate w-full px-1">
                                        {fileData.file.name.split('.').pop()?.toUpperCase()}
                                    </span>
                                </div>
                            )}
                        </div>

                        {/* File metadata */}
                        <div className="p-2 border-t border-border bg-muted/50">
                            <p className="text-xs font-medium truncate" title={fileData.file.name}>
                                {fileData.file.name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                {formatFileSize(fileData.file.size)}
                            </p>
                        </div>
                    </div>
                ))}
            </div>

            {/* Action buttons */}
            <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onCancel}
                >
                    Cancel
                </Button>
                <Button
                    type="button"
                    onClick={onConfirm}
                    disabled={files.length === 0}
                >
                    Send {files.length} {files.length === 1 ? 'file' : 'files'}
                </Button>
            </div>
        </div>
    );
}
