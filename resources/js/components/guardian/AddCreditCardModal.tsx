import { useState, useEffect, useRef } from 'react';
import { X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { router, usePage } from '@inertiajs/react';
import { toast } from 'sonner';
import { VisaCardIcon } from '@/components/icons/visa-card-icon';
import { MasterCardIcon } from '@/components/icons/master-card-icon';
import { AmericanExpressIconProps } from '@/components/icons/american-express-icon';
import { DiscoverIconProps } from '@/components/icons/discover-icon';
import { CreditCardIcon } from '@/components/icons/credit-card-icon';

// Declare Stripe types
declare global {
    interface Window {
        Stripe: any;
    }
}

interface AddCreditCardModalProps {
    isOpen: boolean;
    onClose: () => void;
    onBack: () => void;
}

// Card brand logo component - Using colored logos from CDN
const CardBrandIcon = ({ brand }: { brand: string }) => {
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
            className="w-8 h-6 object-contain"
            onError={(e) => {
                e.currentTarget.src = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="%23666"%3E%3Crect x="2" y="5" width="20" height="14" rx="2" stroke-width="2"/%3E%3Cline x1="2" y1="10" x2="22" y2="10" stroke-width="2"/%3E%3C/svg%3E';
            }}
        />
    );
};

// Main modal component
export default function AddCreditCardModal({ isOpen, onClose, onBack }: AddCreditCardModalProps) {
    const { props } = usePage();
    const user = (props as any).auth?.user;
    
    const [stripe, setStripe] = useState<any>(null);
    const [elements, setElements] = useState<any>(null);
    const [stripeLoading, setStripeLoading] = useState(true);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [rememberCard, setRememberCard] = useState(true);
    const [cardBrand, setCardBrand] = useState<string>('unknown');
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

    // Initialize Stripe
    useEffect(() => {
        if (!isOpen) return;

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
                const response = await window.axios.get('/guardian/payment/publishable-key');
                const key = response.data.publishable_key;

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
    }, [isOpen]);

    // Mount Stripe elements
    useEffect(() => {
        if (!elements || !cardNumberRef.current || !cardExpiryRef.current || !cardCvcRef.current) return;

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

                const handleCardNumberChange = (event: any) => {
                    handleChange(event);
                    if (event.brand) {
                        setCardBrand(event.brand);
                    }
                };

                cardNumberEl.on('change', handleCardNumberChange);
                cardExpiryEl.on('change', handleChange);
                cardCvcEl.on('change', handleChange);
            } catch (error) {
                console.error('Failed to create Stripe elements:', error);
            }
        });

        return cleanup;
    }, [elements]);

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!stripe || !cardNumberElement.current) {
            toast.error('Stripe has not loaded yet. Please try again.');
            return;
        }

        setIsSubmitting(true);
        const loadingToast = toast.loading('Adding credit card...');

        try {
            // Create payment method with Stripe
            const { error, paymentMethod } = await stripe.createPaymentMethod({
                type: 'card',
                card: cardNumberElement.current,
            });

            if (error) {
                toast.dismiss(loadingToast);
                toast.error(error.message || 'Failed to validate card');
                setIsSubmitting(false);
                return;
            }

            // Get card details from Stripe
            const cardBrand = paymentMethod.card?.brand || 'card';
            const last4 = paymentMethod.card?.last4 || '****';
            
            // Get user's name from page props or Stripe billing details
            const userName = user?.name || paymentMethod.billing_details?.name || 'Card Holder';
            
            // Log what Stripe returns
            console.log('Stripe Payment Method:', {
                brand: paymentMethod.card?.brand,
                last4: paymentMethod.card?.last4,
                exp_month: paymentMethod.card?.exp_month,
                exp_year: paymentMethod.card?.exp_year,
            });
            
            // Save payment method to backend - use guardian endpoint
            const paymentMethodData = {
                type: 'card',
                name: `Card ending in ${paymentMethod.card?.last4}`,
                stripe_payment_method_id: paymentMethod.id,
                card_brand: paymentMethod.card?.brand,
                last_four: paymentMethod.card?.last4,
                exp_month: paymentMethod.card?.exp_month,
                exp_year: paymentMethod.card?.exp_year,
                account_name: userName,
                remember_card: rememberCard,
                currency: 'NGN'
            };
            
            console.log('Sending to backend:', paymentMethodData);

            router.post('/guardian/payment/methods', paymentMethodData, {
                preserveScroll: true,
                onSuccess: () => {
                    toast.dismiss(loadingToast);
                    toast.success('Credit card added successfully');
                    onClose();
                },
                onError: (errors) => {
                    toast.dismiss(loadingToast);
                    console.error('Validation errors:', errors);
                    toast.error(errors.error as string || 'Failed to add credit card. Please try again.');
                },
                onFinish: () => {
                    toast.dismiss(loadingToast);
                    setIsSubmitting(false);
                }
            });
        } catch (error) {
            toast.dismiss(loadingToast);
            console.error('Error adding credit card:', error);
            toast.error('An unexpected error occurred. Please try again.');
            setIsSubmitting(false);
        }
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/30 backdrop-blur-sm flex items-center justify-center z-50 p-4 sm:p-6">
            <div className="bg-[#F8FAFC] rounded-2xl w-full max-w-3xl mx-4 p-8">
                {/* Header */}
                <div className="flex items-start justify-between mb-6">
                    <div>
                        <h2 className="text-3xl font-bold text-gray-900 mb-2">
                            Add Credit/Debit Card
                        </h2>
                        <p className="text-gray-500">
                            Securely save your card for quick payments
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600 transition-colors"
                    >
                        <X className="w-6 h-6" />
                    </button>
                </div>

                {stripeLoading ? (
                    <div className="bg-white rounded-[32px] p-10 shadow-[0_4px_24px_rgba(0,0,0,0.08)] text-center">
                        <p className="text-gray-500">Loading payment form...</p>
                    </div>
                ) : (
                    <form onSubmit={handleSubmit}>
                        {/* Header */}
                        <h3 className="text-[#1E293B] text-2xl font-normal mb-4">
                            Enter your card details
                        </h3>

                        {/* White Card Container */}
                        <div className="bg-white rounded-[32px] p-10 shadow-[0_4px_24px_rgba(0,0,0,0.08)]">
                            {/* Card Number Field */}
                            <div className="mb-4">
                                <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                                    CARD NUMBER
                                </label>
                                <div
                                    ref={cardNumberRef}
                                    className="w-full px-3 py-3 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                />
                            </div>

                            {/* Expiry and CVV Grid */}
                            <div className="grid grid-cols-2 gap-6 mb-8">
                                {/* Valid Till */}
                                <div>
                                    <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                                        VALID TILL
                                    </label>
                                    <div className="relative">
                                        <div className="absolute left-5 top-1/2 -translate-y-1/2 z-10 pointer-events-none">
                                            <CardBrandIcon brand={cardBrand} />
                                        </div>
                                        <div
                                            ref={cardExpiryRef}
                                            className="w-full pl-16 pr-5 py-2 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                        />
                                    </div>
                                </div>

                                {/* CVV */}
                                <div>
                                    <label className="block text-[#64748B] text-sm font-semibold mb-3 uppercase tracking-wider">
                                        CVV
                                    </label>
                                    <div className="relative">
                                        <div className="absolute left-5 top-1/2 -translate-y-1/2 z-10 pointer-events-none">
                                            <CardBrandIcon brand={cardBrand} />
                                        </div>
                                        <div
                                            ref={cardCvcRef}
                                            className="w-full pl-16 pr-5 py-2 border border-[#E2E8F0] rounded-xl bg-[#F8FAFC] focus-within:ring-2 focus-within:ring-[#2C7870]/20 focus-within:border-[#2C7870] transition-all"
                                        />
                                    </div>
                                </div>
                            </div>

                            {/* Validation Error */}
                            {validationError && (
                                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <p className="text-sm text-red-600">{validationError}</p>
                                </div>
                            )}

                            {/* Remember Card Checkbox */}
                            <div className="mb-6">
                                <label className="flex items-center gap-3 cursor-pointer group">
                                    <div className="relative flex items-center justify-center">
                                        <input
                                            type="checkbox"
                                            checked={rememberCard}
                                            onChange={(e) => setRememberCard(e.target.checked)}
                                            className="w-6 h-6 text-[#2C7870] border-2 border-[#CBD5E1] rounded-md focus:ring-2 focus:ring-[#2C7870] focus:ring-offset-2 cursor-pointer transition-all appearance-none checked:bg-[#2C7870] checked:border-[#2C7870]"
                                        />
                                        {rememberCard && (
                                            <svg className="w-4 h-4 text-white absolute pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={3} d="M5 13l4 4L19 7" />
                                            </svg>
                                        )}
                                    </div>
                                    <span className="text-[#94A3B8] text-base font-normal select-none">
                                        Remember this card next time
                                    </span>
                                </label>
                            </div>

                            {/* Divider */}
                            <div className="border-t border-[#E2E8F0] mb-8"></div>

                            {/* Action Buttons */}
                            <div className="flex items-center gap-4">
                                <Button
                                    type="submit"
                                    disabled={isSubmitting || !isFormValid || !stripe}
                                    className="bg-[#2C7870] hover:bg-[#236158] disabled:bg-[#CBD5E1] disabled:cursor-not-allowed text-white py-4 px-10 rounded-full font-semibold transition-all text-base shadow-sm hover:shadow-md disabled:shadow-none"
                                >
                                    {isSubmitting ? 'Processing...' : 'Add Card'}
                                </Button>
                                <Button
                                    type="button"
                                    onClick={onBack}
                                    disabled={isSubmitting}
                                    className="bg-white border-2 border-[#2C7870] text-[#2C7870] hover:bg-[#F0F9FF] py-4 px-10 rounded-full font-semibold transition-all text-base disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    Cancel
                                </Button>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </div>
    );
}

