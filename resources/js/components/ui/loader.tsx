import React, { useEffect, useState } from "react";
import { cn } from "@/lib/utils";
import AppLogoIcon from "@/components/app-logo-icon";

interface LoaderProps {
  /** Whether the loader is active */
  isLoading?: boolean;
  /** Optional message to display under the spinner */
  message?: string;
  /** Whether the loader should take up the full screen */
  fullScreen?: boolean;
  /** Optional additional classes for the container */
  className?: string;
  /** Optional callback when loading completes */
  onLoadingComplete?: () => void;
}

export function Loader({
  isLoading = true,
  message = "Please wait",
  fullScreen = true,
  className,
  onLoadingComplete,
}: LoaderProps) {
  const [progress, setProgress] = useState(0);
  const [loadingText, setLoadingText] = useState("Initializing");
  const [isVisible, setIsVisible] = useState(true);
  
  // Simulate loading progress
  useEffect(() => {
    if (!isLoading) {
      // If isLoading becomes false, quickly finish to 100%
      const quickFinish = setInterval(() => {
        setProgress(prev => {
          if (prev >= 100) {
            clearInterval(quickFinish);
            return 100;
          }
          return prev + 5; // Faster increment to finish quickly
        });
      }, 50);
      
      return () => clearInterval(quickFinish);
    }
    
    const texts = [
      "Preparing application",
      "Loading resources",
      "Almost ready"
    ];
    
    // Update loading text every few seconds
    const textInterval = setInterval(() => {
      setLoadingText(texts[Math.floor(Math.random() * texts.length)]);
    }, 1500); // Faster text rotation
    
    // Simulate progress - faster now
    const progressInterval = setInterval(() => {
      setProgress(prev => {
        if (prev >= 90) {
          clearInterval(progressInterval);
          return 90;
        }
        return prev + Math.floor(Math.random() * 15) + 5; // Faster progress
      });
    }, 400); // More frequent updates
    
    return () => {
      clearInterval(textInterval);
      clearInterval(progressInterval);
    };
  }, [isLoading]);
  
  // Handle completion
  useEffect(() => {
    if (progress >= 100) {
      // Add a small delay before hiding
      const hideTimer = setTimeout(() => {
        setIsVisible(false);
        if (onLoadingComplete) {
          onLoadingComplete();
        }
      }, 500);
      
      return () => clearTimeout(hideTimer);
    }
  }, [progress, onLoadingComplete]);

  // If not visible, return null
  if (!isVisible) return null;

  return (
    <div
      className={cn(
        "fixed inset-0 z-[9999] bg-slate-50 dark:bg-slate-900 transition-opacity duration-500",
        isLoading || progress < 100 ? "opacity-100" : "opacity-0",
        fullScreen ? "fixed inset-0" : "absolute inset-0",
        className
      )}
    >
      <div className="absolute inset-0 overflow-hidden">
        {/* Decorative elements */}
        <div className="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-teal-500 via-blue-500 to-teal-500" />
        <div className="absolute bottom-0 left-0 w-full h-1 bg-gradient-to-r from-blue-500 via-teal-500 to-blue-500" />
        <div className="absolute top-12 left-12 w-32 h-32 bg-teal-500/5 rounded-full blur-3xl" />
        <div className="absolute bottom-12 right-12 w-48 h-48 bg-blue-500/5 rounded-full blur-3xl" />
      </div>
      
      {/* Main content */}
      <div className="flex flex-col items-center justify-center w-full h-full">
        <div className="w-full max-w-md px-4">
          {/* Logo */}
          <div className="flex justify-center mb-12">
            <AppLogoIcon className="h-20 w-20" />
          </div>
          
          {/* Progress text */}
          <div className="flex justify-between items-center mb-2">
            <div className="text-slate-600 dark:text-slate-300 text-sm font-medium">
              {progress >= 100 ? "Ready" : `${loadingText}...`}
            </div>
            <div className="text-slate-800 dark:text-slate-200 text-sm font-semibold">
              {progress}%
            </div>
          </div>
          
          {/* Progress bar */}
          <div className="h-1 w-full bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
            <div 
              className="h-full bg-gradient-to-r from-teal-500 to-blue-500"
              style={{ width: `${progress}%`, transition: 'width 0.3s ease-out' }}
            />
          </div>
          
          {/* Message */}
          <div className="mt-8 text-center">
            <p className="text-slate-500 dark:text-slate-400 text-sm">{message}</p>
          </div>
        </div>
      </div>
      
      {/* Footer */}
      <div className="absolute bottom-4 w-full text-center text-xs text-slate-400 dark:text-slate-500">
        © {new Date().getFullYear()} IqraQuest
      </div>
    </div>
  );
} 