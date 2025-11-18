import { useState, useEffect, useRef } from 'react';
import { Button } from '@/components/ui/button';
import { router } from '@inertiajs/react';
import { toast } from 'sonner';

// Declare Stripe types
declare global {
    interface Window {
        Stripe: any;
    }
}

interface PaymentMethod {
    id: number;
    type: string;
    name: string;
    card_brand?: string;
    card_number_prefix?: string;
    card_number_middle?: string;
    last_four?: string;
    bank_name?: string;
    account_name?: string;
    exp_month?: number;
    exp_year?: number;
    stripe_payment_method_id?: string;
}

interface EditCreditCardFormProps {
    paymentMethod: PaymentMethod;
    onCancel: () => void;
    onSuccess: () => void;
    onAddNew?: () => void;
}

// Helper function to get card brand icon - Using colored logos from CDN
const getCardBrandIcon = (brand: string) => {
    const brandLower = brand?.toLowerCase() || 'unknown';

    const brandLogos: Record<string, string> = {
        'visa': 'https://www.brandeps.com/logo-download/V/Visa-logo-01.svg',
        'mastercard': 'https://www.brandeps.com/logo-download/M/Mastercard-logo-01.svg',
        'amex': 'https://www.brandeps.com/logo-download/A/American-Express-logo-01.svg',
        'discover': 'https://www.brandeps.com/logo-download/D/Discover-logo-01.svg',
        'diners': 'https://www.brandeps.com/logo-download/D/Diners-Club-logo-01.svg',
        'jcb': 'https://www.brandeps.com/logo-download/J/JCB-logo-01.svg',
        'unionpay': 'https://www.brandeps.com/logo-download/U/UnionPay-logo-01.svg',
    };

    const logoUrl = brandLogos[brandLower];

    return (
        <img
            src={logoUrl || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666"%3E%3Crect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/%3E%3Cline x1="2" y1="10" x2="22" y2="10" stroke-width="2"/%3E%3C/svg%3E'}
            alt={brand}
            className="w-12 h-8 object-contain"
            onError={(e) => {
                e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666"%3E%3Crect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/%3E%3Cline x1="2" y1="10" x2="22" y2="10" stroke-width="2"/%3E%3C/svg%3E';
            }}
        />
    );
};

// Helper function to get card brand display name
const getCardBrandName = (brand: string): string => {
    switch (brand?.toLowerCase()) {
        case 'visa':
            return 'Visa';
        case 'mastercard':
            return 'Mastercard';
        case 'amex':
        case 'american express':
            return 'American Express';
        case 'discover':
            return 'Discover';
        case 'verve':
            return 'Verve';
        case 'diners':
            return 'Diners Club';
        case 'jcb':
            return 'JCB';
        case 'unionpay':
            return 'UnionPay';
        default:
            return 'Card';
    }
};

export default function EditCreditCardForm({ paymentMethod, onCancel, onSuccess, onAddNew }: EditCreditCardFormProps) {
    const [isEditMode, setIsEditMode] = useState(false);
    const [stripe, setStripe] = useState<any>(null);
    const [elements, setElements] = useState<any>(null);
    const [stripeLoading, setStripeLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [rememberCard, setRememberCard] = useState(true);
    const [validationError, setValidationError] = useState<string | null>(null);
    const [isFormValid, setIsFormValid] = useState(false);

    // Refs for Stripe elements
    const cardNumberRef = useRef<HTMLDivElement | null>(null);
    const cardExpiryRef = useRef<HTMLDivElement | null>(null);
    const cardCvcRef = useRef<HTMLDivElement | null>(null);
    const cardNumberElement = useRef<any>(null);
    const cardExpiryElement = useRef<any>(null);
    const cardCvcElement = useRef<any>(null);

    // Stripe element styling
    const elementStyle = {
        base: {
            fontSize: '16px',
            color: '#1E293B',
            fontFamily: '"Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            fontWeight: '400',
            lineHeight: '24px',
            '::placeholder': {
                color: '#CBD5E1',
            },
        },
        invalid: {
            color: '#EF4444',
            iconColor: '#EF4444',
        },
    };

    // Initialize Stripe when in edit mode
    useEffect(() => {
        if (!isEditMode) return;

        const initializeStripe = async () => {
            try {
                // Wait for Stripe.js to be loaded
                let retries = 0;
                const maxRetries = 30;

                while (!window.Stripe && retries < maxRetries) {
                    await new Promise(resolve => setTimeout(resolve, 300));
                    retries++;
                }

                if (!window.Stripe) {
                    throw new Error('Stripe.js failed to load. Please refresh the page.');
                }

                // Get publishable key - use guardian endpoint
                const response = await fetch('/guardian/payment/publishable-key');
                const data = await response.json();
                const key = data.publishable_key;

                if (!key) {
                    throw new Error('Invalid publishable key received');
                }

                // Initialize Stripe
                const stripeInstance = window.Stripe(key);
                setStripe(stripeInstance);

                // Create Elements
                const elementsInstance = stripeInstance.elements();
                setElements(elementsInstance);

                setStripeLoading(false);
            } catch (error: any) {
                console.error('Failed to initialize Stripe:', error);
                toast.error(error.message || 'Failed to initialize payment system');
                setStripeLoading(false);
            }
        };

        initializeStripe();
    }, [isEditMode]);

    // Mount Stripe elements when in edit mode
    useEffect(() => {
        if (!isEditMode || !elements || !cardNumberRef.current || !cardExpiryRef.current || !cardCvcRef.current) return;

        const cleanup = () => {
            if (cardNumberElement.current) {
                cardNumberElement.current.destroy();
                cardNumberElement.current = null;
            }
            if (cardExpiryElement.current) {
                cardExpiryElement.current.destroy();
                cardExpiryElement.current = null;
            }
            if (cardCvcElement.current) {
                cardCvcElement.current.destroy();
                cardCvcElement.current = null;
            }
        };

        cleanup();

        requestAnimationFrame(() => {
            if (!cardNumberRef.current || !cardExpiryRef.current || !cardCvcRef.current || !elements) return;

            try {
                // Create Card Number Element
                const cardNumberEl = elements.create('cardNumber', {
                    style: elementStyle,
                    showIcon: true,
                });
                cardNumberEl.mount(cardNumberRef.current);
                cardNumberElement.current = cardNumberEl;

                // Create Card Expiry Element
                const cardExpiryEl = elements.create('cardExpiry', {
                    style: elementStyle,
                });
                cardExpiryEl.mount(cardExpiryRef.current);
                cardExpiryElement.current = cardExpiryEl;

                // Create Card CVC Element
                const cardCvcEl = elements.create('cardCvc', {
                    style: elementStyle,
                });
                cardCvcEl.mount(cardCvcRef.current);
                cardCvcElement.current = cardCvcEl;

                // Add event listeners
                const handleChange = (event: any) => {
                    if (event.error) {
                        setValidationError(event.error.message);
                        setIsFormValid(false);
                    } else {
                        setValidationError(null);
                        setIsFormValid(event.complete);
                    }
                };

                cardNumberEl.on('change', handleChange);
                cardExpiryEl.on('change', handleChange);
                cardCvcEl.on('change', handleChange);
            } catch (error) {
                console.error('Failed to create Stripe elements:', error);
            }
        });

        return cleanup;
    }, [elements, isEditMode]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!stripe || !cardNumberElement.current) {
            toast.error('Stripe has not loaded yet. Please try again.');
            return;
        }

        setIsSubmitting(true);
        const loadingToast = toast.loading('Updating credit card...');

        try {
            // Create payment method with Stripe
            const { error, paymentMethod: newPaymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardNumberElement.current,
            });

            if (error) {
                toast.dismiss(loadingToast);
                toast.error(error.message || 'Failed to validate card');
                setIsSubmitting(false);
                return;
            }

            // Get user's name for account_name
            const userName = (window as any).Laravel?.user?.name || newPaymentMethod.billing_details?.name || paymentMethod.account_name || 'Card Holder';

            // Update payment method in backend - use guardian endpoint
            const paymentMethodData = {
                type: 'card',
                name: `Card ending in ${newPaymentMethod.card?.last4}`,
                stripe_payment_method_id: newPaymentMethod.id,
                card_brand: newPaymentMethod.card?.brand,
                last_four: newPaymentMethod.card?.last4,
                exp_month: newPaymentMethod.card?.exp_month,
                exp_year: newPaymentMethod.card?.exp_year,
                account_name: userName,
                remember_card: rememberCard,
                currency: 'NGN'
            };

            console.log('Sending payment method data:', paymentMethodData);

            router.put(`/guardian/payment/methods/${paymentMethod.id}`, paymentMethodData, {
                preserveScroll: true,
                onSuccess: async () => {
                    toast.dismiss(loadingToast);
                    toast.success('Credit card updated successfully');
                    setIsEditMode(false);
                    // Call onSuccess to refresh data
                    await onSuccess();
                    // Force a small delay to ensure state updates
                    setTimeout(() => {
                        setIsSubmitting(false);
                    }, 100);
                },
                onError: (errors) => {
                    toast.dismiss(loadingToast);
                    console.error('Validation errors:', errors);
                    toast.error(errors.error as string || 'Failed to update credit card. Please try again.');
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    toast.dismiss(loadingToast);
                }
            });
        } catch (error) {
            toast.dismiss(loadingToast);
            console.error('Error updating credit card:', error);
            toast.error('An unexpected error occurred. Please try again.');
            setIsSubmitting(false);
        }
    };

    return (
        <div className="bg-white rounded-2xl p-10">
            {/* Header with Edit button */}
            <div className="flex items-center justify-between mb-8">
                <h2 className="text-xl font-semibold text-gray-700">
                    Bank Card
                </h2>
                <button
                    type="button"
                    onClick={() => setIsEditMode(!isEditMode)}
                    disabled={isSubmitting}
                    className="text-[#2C7870] hover:text-[#236158] font-medium transition-colors disabled:opacity-50"
                >
                    {isEditMode ? 'Cancel' : 'Edit'}
                </button>
            </div>

            {!isEditMode ? (
                /* Read-only view */
                <div className="space-y-8">
                    {/* Card Display with Logo */}
                    <div className="flex items-center gap-4">
                        {/* Card Brand Logo */}
                        <div className="flex-shrink-0">
                            {getCardBrandIcon(paymentMethod.card_brand || '')}
                        </div>

                        <div className="flex-1">
                            {/* Card Brand Name */}
                            <p className="text-lg font-medium text-gray-900">
                                {getCardBrandName(paymentMethod.card_brand || '')}
                            </p>
                            {/* Card Holder and Last 4 */}
                            <p className="text-gray-500">
                                {paymentMethod.account_name || 'Card Holder'} | **** **** **** {paymentMethod.last_four || '****'}
                            </p>
                        </div>
                    </div>
                </div>
            ) : (
                /* Edit mode */
                <form onSubmit={handleSubmit}>
                    {stripeLoading ? (
                        <div className="text-center py-8">
                            <p className="text-gray-500">Loading payment form...</p>
                        </div>
                    ) : (
                        <div className="space-y-8">
                            {/* Card Number Field */}
                            <div>
                                <label className="block text-lg font-medium text-gray-600 mb-4">
                                    Card Number
                                </label>
                                <div
                                    ref={cardNumberRef}
                                    className="w-full px-5 py-4 border border-gray-200 rounded-2xl bg-gray-100 focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                />
                            </div>

                            {/* Expiry and CVV Grid */}
                            <div className="grid grid-cols-2 gap-6">
                                {/* Valid Till */}
                                <div>
                                    <label className="block text-lg font-medium text-gray-600 mb-4">
                                        Valid Till
                                    </label>
                                    <div
                                        ref={cardExpiryRef}
                                        className="w-full px-5 py-4 border border-gray-200 rounded-2xl bg-gray-100 focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                    />
                                </div>

                                {/* CVV */}
                                <div>
                                    <label className="block text-lg font-medium text-gray-600 mb-4">
                                        CVV
                                    </label>
                                    <div
                                        ref={cardCvcRef}
                                        className="w-full px-5 py-4 border border-gray-200 rounded-2xl bg-gray-100 focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                    />
                                </div>
                            </div>

                            {/* Validation Error */}
                            {validationError && (
                                <div className="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p className="text-sm text-red-600">{validationError}</p>
                                </div>
                            )}

                            {/* Remember Card Checkbox */}
                            <div>
                                <label className="flex items-center gap-3 cursor-pointer group">
                                    <div className="relative flex items-center justify-center">
                                        <input
                                            type="checkbox"
                                            checked={rememberCard}
                                            onChange={(e) => setRememberCard(e.target.checked)}
                                            className="w-6 h-6 text-[#2C7870] border-2 border-gray-300 rounded-md focus:ring-2 focus:ring-[#2C7870] focus:ring-offset-2 cursor-pointer transition-all appearance-none checked:bg-[#2C7870] checked:border-[#2C7870]"
                                        />
                                        {rememberCard && (
                                            <svg className="w-4 h-4 text-white absolute pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                            </svg>
                                        )}
                                    </div>
                                    <span className="text-gray-600 text-base font-normal select-none">
                                        Remember this card next time
                                    </span>
                                </label>
                            </div>

                            {/* Security Note */}
                            <div className="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <p className="text-sm text-blue-900">
                                    <strong>Security Note:</strong> For your security, you need to re-enter your card details to update your payment method.
                                </p>
                            </div>

                            {/* Submit Button */}
                            <div className="pt-4">
                                <Button
                                    type="submit"
                                    disabled={isSubmitting || !isFormValid || !stripe}
                                    className="bg-[#2C7870] hover:bg-[#236158] text-white px-10 py-3 rounded-2xl font-medium h-auto"
                                >
                                    {isSubmitting ? (
                                        <div className="flex items-center justify-center space-x-2">
                                            <div className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                                            <span>Saving...</span>
                                        </div>
                                    ) : (
                                        'Save Changes'
                                    )}
                                </Button>
                            </div>
                        </div>
                    )}
                </form>
            )}

            {/* Add New Payment Button */}
            <div className="mt-12 text-center">
                <button
                    type="button"
                    onClick={onAddNew || onCancel}
                    className="text-[#2C7870] hover:text-[#236158] font-medium transition-colors"
                >
                    Add New Payment
                </button>
            </div>
        </div>
    );
}

