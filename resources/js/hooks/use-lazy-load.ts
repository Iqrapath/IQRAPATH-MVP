import { useEffect, useRef, useState } from 'react';

interface UseLazyLoadOptions {
    threshold?: number;
    rootMargin?: string;
}

/**
 * Custom hook for lazy loading images using Intersection Observer
 * 
 * @param options - Intersection Observer options
 * @returns Object with ref to attach to element and isVisible state
 */
export function useLazyLoad(options: UseLazyLoadOptions = {}) {
    const { threshold = 0.1, rootMargin = '50px' } = options;
    const [isVisible, setIsVisible] = useState(false);
    const elementRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        const element = elementRef.current;
        if (!element) return;

        // If Intersection Observer is not supported, load immediately
        if (!('IntersectionObserver' in window)) {
            setIsVisible(true);
            return;
        }

        const observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        setIsVisible(true);
                        // Once visible, stop observing
                        observer.unobserve(entry.target);
                    }
                });
            },
            {
                threshold,
                rootMargin,
            }
        );

        observer.observe(element);

        return () => {
            if (element) {
                observer.unobserve(element);
            }
        };
    }, [threshold, rootMargin]);

    return { ref: elementRef, isVisible };
}

/**
 * Custom hook for lazy loading images with loading state
 * 
 * @param src - Image source URL
 * @param options - Intersection Observer options
 * @returns Object with ref, loading state, and error state
 */
export function useLazyImage(src: string, options: UseLazyLoadOptions = {}) {
    const { ref, isVisible } = useLazyLoad(options);
    const [isLoading, setIsLoading] = useState(true);
    const [hasError, setHasError] = useState(false);
    const [imageSrc, setImageSrc] = useState<string | null>(null);

    useEffect(() => {
        if (!isVisible || !src) return;

        setIsLoading(true);
        setHasError(false);

        const img = new Image();
        
        img.onload = () => {
            setImageSrc(src);
            setIsLoading(false);
        };

        img.onerror = () => {
            setHasError(true);
            setIsLoading(false);
        };

        img.src = src;

        return () => {
            img.onload = null;
            img.onerror = null;
        };
    }, [isVisible, src]);

    return {
        ref,
        isVisible,
        isLoading,
        hasError,
        src: imageSrc,
    };
}
