import React from 'react';
import { useInertiaLoading } from '@/hooks/use-inertia-loading';

/**
 * Component to handle global loading states
 * This component should be included in the app layout to automatically
 * show loading screens during page transitions
 */
export function AppLoading() {
  // Initialize the Inertia loading hook
  useInertiaLoading();
  
  return null; // This component doesn't render anything visually
} 