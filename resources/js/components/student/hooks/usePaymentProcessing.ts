import { useState } from 'react';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';

export function usePaymentProcessing(
    stripe: any,
    cardNumberElement: { current: any },
    user: any,
    onPayment: (data: any) => void,
    onClose: () => void
) {
    const [isLoading, setIsLoading] = useState(false);

    const getErrorMessage = (error: any): string => {
        if (error.code === 'card_declined') {
            return 'Your card was declined. Please try another card.';
        }
        if (error.code === 'insufficient_funds') {
            return 'Insufficient funds. Please try another card.';
        }
        if (error.code === 'expired_card') {
            return 'Your card has expired. Please use a different card.';
        }
        if (error.code === 'incorrect_cvc') {
            return 'Incorrect CVC code. Please check and try again.';
        }
        if (error.code === 'processing_error') {
            return 'Processing error. Please try again.';
        }
        if (error.code === 'incorrect_number') {
            return 'Invalid card number. Please check and try again.';
        }
        return error.message || 'Payment failed. Please try again.';
    };

    const handleMakePayment = async (fundingAmount: string, rememberCard: boolean): Promise<any> => {
        setIsLoading(true);
        
        try {
            const paymentAmount = parseFloat(fundingAmount);
            
            console.log('Creating payment method...');
            
            // Create payment method token using cardNumber element
            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardNumberElement.current,
                billing_details: {
                    name: user?.name,
                    email: user?.email,
                    address: {
                        country: user?.country || 'NG'
                    }
                },
            });

            if (error) {
                console.error('Payment method error:', error);
                toast.error(getErrorMessage(error), {
                    duration: 5000,
                });
                return;
            }

            console.log('Payment method created:', paymentMethod.id);
            
            // Determine API endpoint based on user role
            const isGuardian = window.location.pathname.includes('/guardian/');
            const endpoint = isGuardian ? '/guardian/payment/fund-wallet' : '/student/payment/fund-wallet';
            
            console.log('[Payment] Sending payment request to:', endpoint);
            
            // Send payment request
            const response = await window.axios.post(endpoint, {
                amount: paymentAmount,
                gateway: 'stripe',
                payment_method_id: paymentMethod.id,
                rememberCard: rememberCard
            });
            
            console.log('[Payment] Payment response received:', response.data);

            if (response.data.success) {
                console.log('Payment successful:', response.data.data.transaction_id);
                
                // Call the onPayment callback with success data
                onPayment({
                    success: true,
                    transactionId: response.data.data.transaction_id,
                    amount: response.data.data.amount,
                    newBalance: response.data.data.new_balance
                });
                
                // Reload page data to update wallet balance in header
                router.reload({ only: ['auth'] });
                
                // Return success data for success modal
                return {
                    success: true,
                    transactionId: response.data.data.transaction_id,
                    amount: response.data.data.amount,
                    newBalance: response.data.data.new_balance
                };
            } else {
                toast.error(response.data.message || 'Payment failed', {
                    duration: 5000,
                });
                return null;
            }
        } catch (error: any) {
            console.error('Payment error:', error);
            
            let errorMessage = 'Payment failed. Please try again.';
            
            if (error.response?.status === 401) {
                errorMessage = 'Session expired. Please refresh the page and try again.';
            } else if (error.response?.status === 422) {
                // Validation errors
                const errors = error.response.data.errors;
                if (errors) {
                    const firstError = Object.values(errors)[0];
                    errorMessage = Array.isArray(firstError) ? firstError[0] : String(firstError);
                }
            } else if (error.response?.data?.message) {
                errorMessage = error.response.data.message;
            } else if (error.message) {
                errorMessage = error.message;
            }
            
            toast.error(errorMessage, {
                duration: 5000,
            });
            return null;
        } finally {
            setIsLoading(false);
        }
    };

    return {
        isLoading,
        handleMakePayment
    };
}
