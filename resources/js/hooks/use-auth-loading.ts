import { useLoading } from '@/contexts/loading-context';
import { useCallback } from 'react';

/**
 * Hook to handle loading states during authentication processes (login, logout, etc.)
 * 
 * @example
 * // In a login component:
 * const { handleAuthAction } = useAuthLoading();
 * 
 * const submit = (e) => {
 *   e.preventDefault();
 *   handleAuthAction(() => {
 *     post(route('login'));
 *   }, 'Logging in...');
 * };
 */
export function useAuthLoading() {
  const { showLoading, hideLoading } = useLoading();

  /**
   * Wraps an authentication action with loading indicators
   * 
   * @param action - The auth action function to perform (e.g., login form submission)
   * @param message - Optional loading message to display
   */
  const handleAuthAction = useCallback((action: () => void, message = 'Processing...') => {
    showLoading(message);
    
    // Small timeout to ensure the loading screen appears
    setTimeout(() => {
      // Perform the action
      action();
    }, 50);
  }, [showLoading]);

  return { handleAuthAction };
} 