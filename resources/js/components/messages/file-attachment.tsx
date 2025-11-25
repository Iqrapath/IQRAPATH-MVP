import { Button } from '@/components/ui/button';
import { Download, File, FileText, FileSpreadsheet, FileImage, FileVideo, FileAudio } from 'lucide-react';
import { cn } from '@/lib/utils';

interface FileAttachmentProps {
    filename: string;
    fileSize: number;
    mimeType: string;
    downloadUrl: string;
    className?: string;
}

export default function FileAttachment({
    filename,
    fileSize,
    mimeType,
    downloadUrl,
    className
}: FileAttachmentProps) {
    const formatFileSize = (bytes: number): string => {
        if (bytes === 0) return '0 B';
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    };

    const getFileIcon = () => {
        if (mimeType.startsWith('image/')) {
            return <FileImage className="h-8 w-8 text-blue-500" />;
        } else if (mimeType.startsWith('video/')) {
            return <FileVideo className="h-8 w-8 text-purple-500" />;
        } else if (mimeType.startsWith('audio/')) {
            return <FileAudio className="h-8 w-8 text-green-500" />;
        } else if (mimeType.includes('pdf')) {
            return <FileText className="h-8 w-8 text-red-500" />;
        } else if (
            mimeType.includes('spreadsheet') ||
            mimeType.includes('excel') ||
            mimeType.includes('csv')
        ) {
            return <FileSpreadsheet className="h-8 w-8 text-green-600" />;
        } else if (
            mimeType.includes('document') ||
            mimeType.includes('word') ||
            mimeType.includes('text')
        ) {
            return <FileText className="h-8 w-8 text-blue-600" />;
        }
        return <File className="h-8 w-8 text-gray-500" />;
    };

    const handleDownload = async () => {
        try {
            const response = await fetch(downloadUrl);
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            console.error('Download failed:', error);
            // Fallback to opening in new tab
            window.open(downloadUrl, '_blank');
        }
    };

    return (
        <div className={cn('flex items-center gap-3 bg-muted/50 rounded-lg p-3 max-w-sm', className)}>
            {/* File icon */}
            <div className="flex-shrink-0">
                {getFileIcon()}
            </div>

            {/* File info */}
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium truncate" title={filename}>
                    {filename}
                </p>
                <p className="text-xs text-muted-foreground">
                    {formatFileSize(fileSize)}
                </p>
            </div>

            {/* Download button */}
            <Button
                type="button"
                variant="ghost"
                size="icon"
                onClick={handleDownload}
                className="flex-shrink-0"
                title="Download file"
            >
                <Download className="h-4 w-4" />
            </Button>
        </div>
    );
}
