import { useLoading } from '@/contexts/loading-context';

/**
 * Example of how to manually use the loading screen in components
 * 
 * @example
 * const ExampleComponent = () => {
 *   const { withLoading } = useManualLoading();
 *   
 *   const handleButtonClick = async () => {
 *     await withLoading(async () => {
 *       // Some async operation that takes time
 *       await someAsyncOperation();
 *     }, "Processing your request...");
 *   };
 *   
 *   return <button onClick={handleButtonClick}>Submit</button>;
 * }
 */
export function useManualLoading() {
  const { showLoading, hideLoading } = useLoading();
  
  /**
   * Execute a function with loading indicator
   * @param fn The function to execute
   * @param message Optional loading message
   * @returns The result of the function
   */
  const withLoading = async <T>(fn: () => Promise<T>, message?: string): Promise<T> => {
    try {
      showLoading(message);
      return await fn();
    } finally {
      hideLoading();
    }
  };
  
  return { withLoading, showLoading, hideLoading };
} 