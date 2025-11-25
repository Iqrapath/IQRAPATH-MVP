import { useLazyImage } from '@/hooks/use-lazy-load';
import { cn } from '@/lib/utils';
import { Loader2 } from 'lucide-react';

interface LazyImageProps {
    src: string;
    alt: string;
    className?: string;
    placeholderClassName?: string;
    onLoad?: () => void;
    onError?: () => void;
    onClick?: () => void;
}

/**
 * LazyImage component that loads images on demand using Intersection Observer
 * 
 * Features:
 * - Loads image only when visible in viewport
 * - Shows loading spinner while loading
 * - Shows error state if image fails to load
 * - Smooth fade-in animation when loaded
 */
export default function LazyImage({
    src,
    alt,
    className,
    placeholderClassName,
    onLoad,
    onError,
    onClick,
}: LazyImageProps) {
    const { ref, isLoading, hasError, src: imageSrc } = useLazyImage(src, {
        threshold: 0.1,
        rootMargin: '50px',
    });

    // Handle load callback
    if (!isLoading && imageSrc && onLoad) {
        onLoad();
    }

    // Handle error callback
    if (hasError && onError) {
        onError();
    }

    return (
        <div
            ref={ref}
            className={cn(
                'relative overflow-hidden bg-muted',
                placeholderClassName
            )}
        >
            {/* Loading state */}
            {isLoading && (
                <div className="absolute inset-0 flex items-center justify-center">
                    <Loader2 className="h-6 w-6 animate-spin text-muted-foreground" />
                </div>
            )}

            {/* Error state */}
            {hasError && (
                <div className="absolute inset-0 flex items-center justify-center bg-muted">
                    <div className="text-center text-sm text-muted-foreground">
                        <p>Failed to load image</p>
                    </div>
                </div>
            )}

            {/* Image */}
            {imageSrc && !hasError && (
                <img
                    src={imageSrc}
                    alt={alt}
                    className={cn(
                        'transition-opacity duration-300',
                        isLoading ? 'opacity-0' : 'opacity-100',
                        className
                    )}
                    onClick={onClick}
                />
            )}
        </div>
    );
}
