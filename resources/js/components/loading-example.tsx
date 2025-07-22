import React from 'react';
import { useManualLoading } from '@/hooks/use-manual-loading';
import { Button } from '@/components/ui/button';

/**
 * Example component showing how to use the loading screen manually.
 * You can include this component in any page to demonstrate loading functionality.
 */
export function LoadingExample() {
  const { withLoading } = useManualLoading();
  
  const simulateLoading = async () => {
    // Simulate a delay of 2 seconds
    await new Promise(resolve => setTimeout(resolve, 2000));
  };
  
  const handleClick = async (message: string) => {
    await withLoading(async () => {
      await simulateLoading();
    }, message);
  };
  
  return (
    <div className="p-4 space-y-4">
      <h2 className="text-lg font-medium">Loading Screen Examples</h2>
      
      <div className="flex flex-col space-y-2 sm:flex-row sm:space-y-0 sm:space-x-4">
        <Button 
          onClick={() => handleClick('Loading...')}
          variant="default"
        >
          Show Default Loading
        </Button>
        
        <Button 
          onClick={() => handleClick('Processing your request...')}
          variant="outline"
        >
          Show Processing Message
        </Button>
        
        <Button 
          onClick={() => handleClick('Saving data to server...')}
          variant="secondary"
        >
          Show Saving Message
        </Button>
      </div>
      
      <p className="text-sm text-gray-500 mt-4">
        Click any button above to show a full-screen loading overlay for 2 seconds.
      </p>
    </div>
  );
} 