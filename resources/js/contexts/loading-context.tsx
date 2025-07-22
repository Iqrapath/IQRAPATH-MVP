import React, { createContext, useContext, useState, ReactNode, useCallback } from "react";
import { Loader } from "@/components/ui/loader";

interface LoadingContextType {
  showLoading: (message?: string) => void;
  hideLoading: () => void;
  isLoading: boolean;
}

const LoadingContext = createContext<LoadingContextType | undefined>(undefined);

interface LoadingProviderProps {
  children: ReactNode;
}

export function LoadingProvider({ children }: LoadingProviderProps) {
  const [isLoading, setIsLoading] = useState(false);
  const [loadingMessage, setLoadingMessage] = useState("Loading...");
  const [hideRequested, setHideRequested] = useState(false);

  const showLoading = useCallback((message: string = "Loading...") => {
    setLoadingMessage(message);
    setIsLoading(true);
    setHideRequested(false);
  }, []);

  const hideLoading = useCallback(() => {
    // Don't immediately hide, just flag that hiding was requested
    setHideRequested(true);
  }, []);
  
  const handleLoadingComplete = useCallback(() => {
    // This will be called when the loader animation completes
    setIsLoading(false);
  }, []);

  return (
    <LoadingContext.Provider
      value={{
        showLoading,
        hideLoading,
        isLoading,
      }}
    >
      {children}
      <Loader 
        isLoading={!hideRequested && isLoading}
        message={loadingMessage} 
        onLoadingComplete={handleLoadingComplete}
      />
    </LoadingContext.Provider>
  );
}

export function useLoading() {
  const context = useContext(LoadingContext);
  if (context === undefined) {
    throw new Error("useLoading must be used within a LoadingProvider");
  }
  return context;
} 