import { useState, useEffect, useRef } from 'react';
import { toast } from 'sonner';
import { PAYMENT_CONFIG } from '../fund-account-modal-components/PaymentConfig';

export function useStripeInitialization(isOpen: boolean) {
    const [stripe, setStripe] = useState<any>(null);
    const [elements, setElements] = useState<any>(null);
    const [stripeLoading, setStripeLoading] = useState(true);
    const [stripeError, setStripeError] = useState<string | null>(null);
    const initTimeoutRef = useRef<NodeJS.Timeout | null>(null);

    useEffect(() => {
        if (!isOpen) return;

        setStripeLoading(true);
        setStripeError(null);

        // Wait for DOM to be fully loaded
        const waitForDOMReady = () => {
            return new Promise<void>((resolve) => {
                if (document.readyState === 'complete') {
                    resolve();
                } else {
                    window.addEventListener('load', () => resolve(), { once: true });
                }
            });
        };

        const initializeStripe = async () => {
            try {
                console.log('[Stripe Init] Starting initialization...');
                
                // Wait for DOM to be ready first
                await waitForDOMReady();
                console.log('[Stripe Init] DOM is ready');
                
                // Wait for Stripe.js to be loaded (with retry logic)
                let retries = 0;
                const maxRetries = 30;
                
                while (!window.Stripe && retries < maxRetries) {
                    if (retries === 0 || retries % 5 === 0) {
                        console.log(`[Stripe Init] Waiting for Stripe.js... (attempt ${retries + 1}/${maxRetries})`);
                    }
                    await new Promise(resolve => setTimeout(resolve, 300));
                    retries++;
                }
                
                if (!window.Stripe) {
                    console.error('[Stripe Init] Stripe.js failed to load after', maxRetries, 'retries');
                    console.error('[Stripe Init] Check if script tag exists:', !!document.querySelector('script[src*="stripe.com"]'));
                    throw new Error('Stripe.js failed to load. Please refresh the page.');
                }
                
                console.log('[Stripe Init] Stripe.js loaded successfully after', retries, 'attempts');

                // Get publishable key - always use guardian endpoint
                const endpoint = '/guardian/payment/publishable-key';
                
                console.log('[Stripe Init] Fetching publishable key from:', endpoint);
                
                // Get publishable key
                const response = await window.axios.get(endpoint);
                
                console.log('[Stripe Init] Received response:', response.data);
                
                const key = response.data.publishable_key;
                
                if (!key) {
                    console.error('[Stripe Init] No publishable key in response');
                    throw new Error('Invalid publishable key received');
                }

                console.log('[Stripe Init] Initializing Stripe with key:', key.substring(0, 20) + '...');
                
                // Initialize Stripe
                const stripeInstance = window.Stripe(key);
                setStripe(stripeInstance);
                
                console.log('[Stripe Init] Creating Elements...');
                
                // Create Elements
                const elementsInstance = stripeInstance.elements();
                setElements(elementsInstance);
                
                console.log('[Stripe Init] Initialization complete!');
                
                setStripeLoading(false);
                
                // Clear the timeout since we succeeded
                if (initTimeoutRef.current) {
                    clearTimeout(initTimeoutRef.current);
                    initTimeoutRef.current = null;
                }
            } catch (error: any) {
                console.error('Failed to initialize Stripe:', error);
                
                let errorMessage = 'Failed to initialize payment system';
                
                if (error.name === 'AbortError') {
                    errorMessage = 'Request timeout. Please check your connection.';
                } else if (error.response?.status === 401) {
                    errorMessage = 'Session expired. Please refresh the page.';
                } else if (error.message) {
                    errorMessage = error.message;
                }
                
                setStripeError(errorMessage);
                setStripeLoading(false);
                toast.error(errorMessage);
                
                // Clear the timeout
                if (initTimeoutRef.current) {
                    clearTimeout(initTimeoutRef.current);
                    initTimeoutRef.current = null;
                }
            }
        };

        // Set initialization timeout (fallback safety)
        initTimeoutRef.current = setTimeout(() => {
            if (stripeLoading) {
                console.error('Stripe initialization timeout - this should not happen with retry logic');
                setStripeError('Payment system initialization timeout. Please refresh the page.');
                setStripeLoading(false);
            }
        }, PAYMENT_CONFIG.STRIPE_INIT_TIMEOUT);

        initializeStripe();

        return () => {
            if (initTimeoutRef.current) {
                clearTimeout(initTimeoutRef.current);
                initTimeoutRef.current = null;
            }
        };
    }, [isOpen]);

    return {
        stripe,
        elements,
        stripeLoading,
        stripeError
    };
}

