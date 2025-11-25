import { useState } from 'react';
import { Dialog, DialogContent } from '@/components/ui/dialog';
import { cn } from '@/lib/utils';
import LazyImage from './lazy-image';

interface ImageAttachmentProps {
    images: Array<{
        url: string;
        alt?: string;
    }>;
    className?: string;
}

export default function ImageAttachment({ images, className }: ImageAttachmentProps) {
    const [selectedImage, setSelectedImage] = useState<number | null>(null);

    const getGridClass = () => {
        switch (images.length) {
            case 1:
                return 'grid-cols-1';
            case 2:
                return 'grid-cols-2';
            case 3:
                return 'grid-cols-2';
            case 4:
                return 'grid-cols-2';
            default:
                return 'grid-cols-3';
        }
    };

    return (
        <>
            <div className={cn('grid gap-2', getGridClass(), className)}>
                {images.map((image, index) => (
                    <div
                        key={index}
                        className={cn(
                            'relative overflow-hidden rounded-lg cursor-pointer hover:opacity-90 transition-opacity',
                            images.length === 3 && index === 0 ? 'col-span-2' : '',
                            images.length > 4 && index >= 4 ? 'hidden' : ''
                        )}
                    >
                        <LazyImage
                            src={image.url}
                            alt={image.alt || `Image ${index + 1}`}
                            className="w-full h-full object-cover aspect-square"
                            placeholderClassName="aspect-square"
                            onClick={() => setSelectedImage(index)}
                        />
                        {images.length > 4 && index === 3 && (
                            <div className="absolute inset-0 bg-black/60 flex items-center justify-center pointer-events-none">
                                <span className="text-white text-2xl font-semibold">
                                    +{images.length - 4}
                                </span>
                            </div>
                        )}
                    </div>
                ))}
            </div>

            {/* Fullscreen viewer */}
            <Dialog open={selectedImage !== null} onOpenChange={() => setSelectedImage(null)}>
                <DialogContent className="max-w-4xl p-0">
                    {selectedImage !== null && (
                        <div className="relative">
                            <img
                                src={images[selectedImage].url}
                                alt={images[selectedImage].alt || `Image ${selectedImage + 1}`}
                                className="w-full h-auto max-h-[80vh] object-contain"
                            />
                            {images.length > 1 && (
                                <div className="absolute bottom-4 left-1/2 transform -translate-x-1/2 flex gap-2">
                                    {images.map((_, index) => (
                                        <button
                                            key={index}
                                            onClick={() => setSelectedImage(index)}
                                            className={cn(
                                                'w-2 h-2 rounded-full transition-all',
                                                index === selectedImage
                                                    ? 'bg-white w-6'
                                                    : 'bg-white/50 hover:bg-white/75'
                                            )}
                                        />
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}
