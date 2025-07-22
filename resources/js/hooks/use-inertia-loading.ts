import { useEffect } from 'react';
import { router } from '@inertiajs/react';
import { useLoading } from '@/contexts/loading-context';

/**
 * Hook to automatically show/hide loading screen during Inertia.js page transitions
 */
export function useInertiaLoading() {
  const { showLoading, hideLoading } = useLoading();
  
  useEffect(() => {
    let loadingTimeout: NodeJS.Timeout;
    
    const handleStart = (event: any) => {
      // Clear any existing timeout
      if (loadingTimeout) clearTimeout(loadingTimeout);
      
      // Check if this is a full page reload (not a partial update)
      if (event.detail.visit.completed) {
        return;
      }
      
      // Show loading with a descriptive message
      showLoading('Loading page...');
    };
    
    const handleFinish = () => {
      // Add a slight delay before hiding to ensure smooth transitions
      loadingTimeout = setTimeout(() => {
        hideLoading();
      }, 300);
    };
    
    const handleError = () => {
      // Add a slight delay before hiding to ensure smooth transitions
      loadingTimeout = setTimeout(() => {
        hideLoading();
      }, 300);
    };
    
    // Add Inertia event listeners
    document.addEventListener('inertia:start', handleStart);
    document.addEventListener('inertia:finish', handleFinish);
    document.addEventListener('inertia:error', handleError);
    
    return () => {
      // Remove event listeners and clear timeout on cleanup
      document.removeEventListener('inertia:start', handleStart);
      document.removeEventListener('inertia:finish', handleFinish);
      document.removeEventListener('inertia:error', handleError);
      
      if (loadingTimeout) clearTimeout(loadingTimeout);
    };
  }, [showLoading, hideLoading]);
  
  return null;
} 