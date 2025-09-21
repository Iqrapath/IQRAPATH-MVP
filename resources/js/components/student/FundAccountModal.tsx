/**
 * 🎨 FIGMA REFERENCE
 * URL: https://www.figma.com/design/jmWnnfdCipxqiQF39Tdb0S/IQRAQUEST?node-id=542-68353&t=O1w7ozri9pYud8IO-0
 * Export: Fund Account Modal with exact payment interface design
 * 
 * EXACT SPECS FROM FIGMA:
 * - Two-column layout: Payment Methods (left) and Card Details (right)
 * - Credit/Debit Card, Bank Transfer, PayPal options
 * - Card number, expiration, CVV input fields
 * - Mastercard, VISA, Apple Pay logos
 * - Make Payment and Cancel buttons
 */
import React, { useState, useEffect, useRef } from 'react';
import { X, CreditCard, ArrowLeftRight, CreditCard as CardIcon } from 'lucide-react';
import { CardTypeIcons, SingleCardIcon } from '../icons/CardTypeIcons';
import { toast } from 'sonner';
import { router } from '@inertiajs/react';

// Declare Stripe types
declare global {
    interface Window {
        Stripe: any;
    }
}

interface PaymentMethod {
    id: string;
    type: 'credit_card' | 'bank_transfer' | 'paypal';
    name: string;
    icon: React.ReactNode;
}

interface FundAccountModalProps {
    isOpen: boolean;
    onClose: () => void;
    onPayment: (paymentData: any) => void;
    amount?: number;
    currency?: string;
    user?: {
        id: number;
        name: string;
        email: string;
        country: string;
    };
}

export default function FundAccountModal({
    isOpen,
    onClose,
    onPayment,
    amount = 0,
    currency = '₦',
    user
}: FundAccountModalProps) {
    const [selectedPaymentMethod, setSelectedPaymentMethod] = useState<'credit_card' | 'bank_transfer' | 'paypal'>('credit_card');
    const [fundingAmount, setFundingAmount] = useState(amount > 0 ? amount.toString() : '');
    const [rememberCard, setRememberCard] = useState(false);
    const [isLoading, setIsLoading] = useState(false);
    const [stripe, setStripe] = useState<any>(null);
    const [elements, setElements] = useState<any>(null);
    const [publishableKey, setPublishableKey] = useState<string>('');
    const cardElementRef = useRef<HTMLDivElement>(null);
    const cardElement = useRef<any>(null);

    // Initialize Stripe
    useEffect(() => {
        const initializeStripe = async () => {
            try {
                // Determine API endpoint based on user role
                const isGuardian = window.location.pathname.includes('/guardian/');
                const endpoint = isGuardian ? '/guardian/payment/publishable-key' : '/student/payment/publishable-key';
                
                // Get publishable key from backend
                const response = await window.axios.get(endpoint);
                const key = response.data.publishable_key;
                setPublishableKey(key);

                // Initialize Stripe
                if (window.Stripe && key) {
                    const stripeInstance = window.Stripe(key);
                    setStripe(stripeInstance);
                    
                    // Create Elements
                    const elementsInstance = stripeInstance.elements();
                    setElements(elementsInstance);
                }
            } catch (error) {
                console.error('Failed to initialize Stripe:', error);
                toast.error('Failed to initialize payment system');
            }
        };

        if (isOpen) {
            initializeStripe();
        }
    }, [isOpen]);

    // Create card element when elements is ready
    useEffect(() => {
        if (elements && cardElementRef.current && selectedPaymentMethod === 'credit_card') {
            // Destroy existing card element if it exists
            if (cardElement.current) {
                cardElement.current.destroy();
                cardElement.current = null;
            }
            
            // Clear the container
            if (cardElementRef.current) {
                cardElementRef.current.innerHTML = '';
            }
            
            // Small delay to ensure DOM is ready
            setTimeout(() => {
                if (cardElementRef.current && elements) {
                    // Create new card element
                    const cardEl = elements.create('card', {
                        hidePostalCode: true, // Disable postal code validation
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#424770',
                                fontFamily: '"Inter", sans-serif',
                                '::placeholder': {
                                    color: '#aab7c4',
                                },
                            },
                            invalid: {
                                color: '#9e2146',
                                iconColor: '#9e2146',
                            },
                        },
                    });
                    
                    cardEl.mount(cardElementRef.current);
                    cardElement.current = cardEl;
                }
            }, 100);
        } else if (selectedPaymentMethod !== 'credit_card' && cardElement.current) {
            // Destroy card element when switching away from credit card
            cardElement.current.destroy();
            cardElement.current = null;
        }
    }, [elements, selectedPaymentMethod]);

    // Cleanup function to destroy card element when component unmounts
    useEffect(() => {
        return () => {
            if (cardElement.current) {
                cardElement.current.destroy();
                cardElement.current = null;
            }
        };
    }, []);
    
    if (!isOpen) return null;

    const paymentMethods: PaymentMethod[] = [
        {
            id: 'credit_card',
            type: 'credit_card',
            name: 'Credit/Debit Card',
            icon: <CardIcon className="w-5 h-5" />
        },
        {
            id: 'bank_transfer',
            type: 'bank_transfer',
            name: 'Bank Transfer',
            icon: <ArrowLeftRight className="w-5 h-5" />
        },
        {
            id: 'paypal',
            type: 'paypal',
            name: 'Paypal',
            icon: <div className="w-5 h-5 bg-blue-600 rounded-sm flex items-center justify-center text-white text-xs font-bold">P</div>
        }
    ];

    const handlePaymentMethodSelect = (methodType: 'credit_card' | 'bank_transfer' | 'paypal') => {
        setSelectedPaymentMethod(methodType);
        
        // Show toast for unavailable methods
        if (methodType === 'bank_transfer' || methodType === 'paypal') {
            toast.error(`${methodType === 'bank_transfer' ? 'Bank Transfer' : 'PayPal'} payment method is not available yet. Please use Credit/Debit Card.`);
        }
    };

    // Validation function to check if all required fields are filled
    const isFormValid = () => {
        if (selectedPaymentMethod !== 'credit_card') return false;
        
        // Check if funding amount is valid
        const amount = parseFloat(fundingAmount);
        if (!fundingAmount.trim() || isNaN(amount) || amount < 1000) {
            return false;
        }
        
        // Check if Stripe is initialized
        if (!stripe || !elements || !cardElement.current) {
            return false;
        }
        
        return true;
    };

    const handleMakePayment = async () => {
        if (!isFormValid()) return;
        
        setIsLoading(true);
        try {
            const paymentAmount = parseFloat(fundingAmount);
            
            console.log('Creating payment method...');
            
            // Create payment method token
            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement.current,
                billing_details: {
                    address: {
                        country: user?.country || 'NG'
                    }
                },
            });

            if (error) {
                console.error('Payment method error:', error);
                toast.error(error.message || 'Payment method creation failed');
                return;
            }

            console.log('Payment method created:', paymentMethod.id);
            
            // Determine API endpoint based on user role
            const isGuardian = window.location.pathname.includes('/guardian/');
            const endpoint = isGuardian ? '/guardian/payment/fund-wallet' : '/student/payment/fund-wallet';
            
            const response = await window.axios.post(endpoint, {
                amount: paymentAmount,
                gateway: 'stripe',
                payment_method_id: paymentMethod.id,
                rememberCard: rememberCard
            });

            if (response.data.success) {
                toast.success(response.data.message, {
                    duration: 5000,
                });
                
                // Call the onPayment callback with success data
                onPayment({
                    success: true,
                    transactionId: response.data.data.transaction_id,
                    amount: response.data.data.amount,
                    newBalance: response.data.data.new_balance
                });
                
                // Reload page data to update wallet balance in header
                router.reload({ only: ['auth'] });
                
                // Close modal after successful payment
                onClose();
            } else {
                toast.error(response.data.message, {
                    duration: 5000,
                });
            }
        } catch (error: any) {
            console.error('Payment error:', error);
            console.error('Error response:', error.response);
            console.error('Error message:', error.message);
            
            if (error.response?.data?.message) {
                toast.error(error.response.data.message, {
                    duration: 5000,
                });
            } else if (error.response?.data?.errors) {
                // Handle validation errors
                const errors = error.response.data.errors;
                const firstError = Object.values(errors)[0];
                toast.error(Array.isArray(firstError) ? firstError[0] : firstError, {
                    duration: 5000,
                });
            } else {
                toast.error('Payment failed. Please try again.', {
                    duration: 5000,
                });
            }
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-2 sm:p-4">
            <div className="bg-white rounded-2xl p-4 sm:p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto shadow-2xl">
                {/* Header */}
                <div className="flex items-center justify-between mb-4 sm:mb-6">
                    <h2 className="text-lg sm:text-xl font-semibold text-gray-900">Fund Account</h2>
                    <button
                        onClick={onClose}
                        className="p-1 hover:bg-gray-100 rounded-lg transition-colors"
                    >
                        <X className="w-5 h-5 text-gray-500" />
                    </button>
                </div>

                {/* Amount Input */}
                <div className="mb-4 sm:mb-6">
                    <label className="block text-sm font-medium text-gray-700 mb-2">
                        Amount to Fund
                    </label>
                    <div className="relative">
                        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <span className="text-gray-500 text-base sm:text-lg">{currency}</span>
                        </div>
                        <input
                            type="number"
                            value={fundingAmount}
                            onChange={(e) => setFundingAmount(e.target.value)}
                            placeholder="Enter amount"
                            min="1000"
                            max="1000000"
                            className="w-full pl-8 sm:pl-10 pr-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-[#2C7870] focus:border-transparent text-sm sm:text-base"
                        />
                    </div>
                    <p className="text-xs text-gray-500 mt-1">Minimum: ₦1,000, Maximum: ₦1,000,000</p>
                </div>

                {/* Two-Column Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6 lg:gap-8">
                    {/* Left Column - Payment Methods */}
                    <div className="lg:col-span-1">
                        <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">Payment Methods:</h3>
                        <div className="space-y-2 sm:space-y-3">
                            {paymentMethods.map((method) => (
                                <div
                                    key={method.id}
                                    onClick={() => handlePaymentMethodSelect(method.type)}
                                    className={`flex items-center gap-2 sm:gap-3 p-3 sm:p-4 rounded-lg cursor-pointer transition-colors border-2 ${selectedPaymentMethod === method.type
                                        ? 'bg-green-50 border-green-600'
                                        : 'bg-white border-gray-200 hover:bg-gray-50'
                                        }`}
                                >
                                    <div className="text-gray-600 flex-shrink-0">
                                        {method.icon}
                                    </div>
                                    <span className="font-medium text-gray-900 text-sm sm:text-base">{method.name}</span>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Right Column - Payment Details */}
                    <div className="lg:col-span-2">
                        <h3 className="text-base sm:text-lg font-bold text-gray-900 mb-3 sm:mb-4">
                            {selectedPaymentMethod === 'credit_card'
                                ? 'Enter your card details to pay'
                                : selectedPaymentMethod === 'bank_transfer'
                                    ? 'Bank Transfer Details'
                                    : 'PayPal Payment Details'
                            }
                        </h3>

                        {/* Payment Details Container */}
                        <div className="bg-white border border-gray-200 rounded-lg p-4 sm:p-6 shadow-sm">
                            {selectedPaymentMethod === 'credit_card' ? (
                                <>
                                    {/* Stripe Card Element */}
                                    <div className="mb-3 sm:mb-4">
                                        <label className="block text-xs font-bold text-gray-700 mb-2 uppercase">
                                            CARD DETAILS
                                        </label>
                                        <div 
                                            ref={cardElementRef}
                                            className="w-full px-3 sm:px-4 py-2 sm:py-3 border border-gray-300 rounded-lg focus-within:ring-2 focus-within:ring-[#2C7870] focus-within:border-transparent"
                                        />
                                    </div>

                                    {/* Remember Card */}
                                    <div className="mb-4 sm:mb-6">
                                        <label className="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                checked={rememberCard}
                                                onChange={(e) => setRememberCard(e.target.checked)}
                                                className="w-4 h-4 text-[#2C7870] border-gray-300 rounded focus:ring-[#2C7870]"
                                            />
                                            <span className="text-xs sm:text-sm text-gray-700">Remember this card next time</span>
                                        </label>
                                    </div>

                                    {/* Action Buttons */}
                                    <div className="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                        <button
                                            onClick={handleMakePayment}
                                            disabled={isLoading || !isFormValid()}
                                            className="flex-1 bg-[#2C7870] hover:bg-[#236158] disabled:bg-gray-300 disabled:cursor-not-allowed text-white py-2 sm:py-3 px-4 rounded-full font-medium transition-colors text-sm sm:text-base"
                                        >
                                            {isLoading ? 'Processing...' : 'Make Payment'}
                                        </button>
                                        <button
                                            onClick={onClose}
                                            className="flex-1 sm:flex-none bg-white border border-[#2C7870] text-[#2C7870] hover:bg-[#2C7870] hover:text-white py-2 sm:py-3 px-4 rounded-full font-medium transition-colors text-sm sm:text-base"
                                        >
                                            Cancel
                                        </button>
                                    </div>
                                </>
                            ) : (
                                <>
                                    {/* Unavailable Payment Method Message */}
                                    <div className="text-center py-6 sm:py-8">
                                        <div className="w-12 h-12 sm:w-16 sm:h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-3 sm:mb-4">
                                            <div className="text-gray-400 text-xl sm:text-2xl">
                                                {selectedPaymentMethod === 'bank_transfer' ? '🏦' : '💳'}
                                            </div>
                                        </div>
                                        <h4 className="text-base sm:text-lg font-semibold text-gray-900 mb-2">
                                            {selectedPaymentMethod === 'bank_transfer' ? 'Bank Transfer' : 'PayPal'} Not Available
                                        </h4>
                                        <p className="text-sm sm:text-base text-gray-600 mb-4 sm:mb-6 px-2">
                                            {selectedPaymentMethod === 'bank_transfer'
                                                ? 'Bank transfer payment method is not available yet. Please use Credit/Debit Card for now.'
                                                : 'PayPal payment method is not available yet. Please use Credit/Debit Card for now.'
                                            }
                                        </p>
                                        <div className="flex flex-col sm:flex-row gap-2 sm:gap-3 justify-center">
                                            <button
                                                onClick={() => setSelectedPaymentMethod('credit_card')}
                                                className="bg-[#2C7870] hover:bg-[#236158] text-white py-2 sm:py-3 px-4 sm:px-6 rounded-lg font-medium transition-colors text-sm sm:text-base"
                                            >
                                                Use Credit/Debit Card
                                            </button>
                                            <button
                                                onClick={onClose}
                                                className="bg-white border border-[#2C7870] text-[#2C7870] hover:bg-[#2C7870] hover:text-white py-2 sm:py-3 px-4 sm:px-6 rounded-lg font-medium transition-colors text-sm sm:text-base"
                                            >
                                                Cancel
                                            </button>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}