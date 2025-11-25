import { Progress } from '@/components/ui/progress';
import { Button } from '@/components/ui/button';
import { CheckCircle2, XCircle, RefreshCw, Loader2, File } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface UploadItem {
    id: string;
    file: File;
    progress: number;
    status: 'uploading' | 'complete' | 'error';
    error?: string;
}

interface UploadProgressProps {
    uploads: UploadItem[];
    onRetry: (id: string) => void;
    onCancel?: (id: string) => void;
    className?: string;
}

export default function UploadProgress({
    uploads,
    onRetry,
    onCancel,
    className
}: UploadProgressProps) {
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const getStatusIcon = (status: UploadItem['status']) => {
        switch (status) {
            case 'uploading':
                return <Loader2 className="h-4 w-4 text-blue-500 animate-spin" />;
            case 'complete':
                return <CheckCircle2 className="h-4 w-4 text-green-500" />;
            case 'error':
                return <XCircle className="h-4 w-4 text-red-500" />;
        }
    };

    const getStatusText = (upload: UploadItem): string => {
        switch (upload.status) {
            case 'uploading':
                return `Uploading... ${upload.progress}%`;
            case 'complete':
                return 'Upload complete';
            case 'error':
                return upload.error || 'Upload failed';
        }
    };

    if (uploads.length === 0) {
        return null;
    }

    return (
        <div className={cn('bg-muted rounded-lg p-4 space-y-3', className)}>
            <div className="flex items-center justify-between mb-2">
                <h4 className="text-sm font-medium">
                    Uploading {uploads.length} {uploads.length === 1 ? 'file' : 'files'}
                </h4>
                <span className="text-xs text-muted-foreground">
                    {uploads.filter(u => u.status === 'complete').length} / {uploads.length} complete
                </span>
            </div>

            <div className="space-y-3">
                {uploads.map((upload) => (
                    <div
                        key={upload.id}
                        className="bg-background rounded-lg p-3 border border-border"
                    >
                        <div className="flex items-start gap-3">
                            {/* File icon */}
                            <div className="flex-shrink-0 mt-0.5">
                                <File className="h-5 w-5 text-muted-foreground" />
                            </div>

                            {/* File info and progress */}
                            <div className="flex-1 min-w-0">
                                <div className="flex items-center justify-between gap-2 mb-1">
                                    <p className="text-sm font-medium truncate" title={upload.file.name}>
                                        {upload.file.name}
                                    </p>
                                    <div className="flex items-center gap-2 flex-shrink-0">
                                        {getStatusIcon(upload.status)}
                                        {upload.status === 'error' && (
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => onRetry(upload.id)}
                                                className="h-6 px-2"
                                            >
                                                <RefreshCw className="h-3 w-3 mr-1" />
                                                Retry
                                            </Button>
                                        )}
                                    </div>
                                </div>

                                <div className="flex items-center justify-between gap-2 mb-2">
                                    <span className="text-xs text-muted-foreground">
                                        {formatFileSize(upload.file.size)}
                                    </span>
                                    <span
                                        className={cn(
                                            'text-xs',
                                            upload.status === 'uploading' && 'text-blue-600',
                                            upload.status === 'complete' && 'text-green-600',
                                            upload.status === 'error' && 'text-red-600'
                                        )}
                                    >
                                        {getStatusText(upload)}
                                    </span>
                                </div>

                                {/* Progress bar */}
                                {upload.status === 'uploading' && (
                                    <Progress value={upload.progress} className="h-1.5" />
                                )}
                            </div>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
}
